/* eslint-disable max-depth */
import { rmSync, readdir } from 'fs'
import { join } from 'path'
import pino from 'pino'
import makeWASocket, {
    useMultiFileAuthState,
    Browsers,
    DisconnectReason,
    delay,
    downloadMediaMessage,
    fetchLatestBaileysVersion,
    proto,
    getHistoryMsg,
    downloadAndProcessHistorySyncNotification,
    isJidUser,
    isLidUser,
    jidNormalizedUser,
} from 'baileys'
import { toDataURL } from 'qrcode'
import dirname from './dirname.js'
import response from './response.js'
import axios from 'axios'

const sessions = new Map()
const retries = new Map()

const sessionsDir = (sessionId = '') => {
    return join(dirname, 'sessions', sessionId ? sessionId : '')
}

const isSessionExists = (sessionId) => {
    return sessions.has(sessionId)
}

const shouldReconnect = (sessionId) => {
    let maxRetries = parseInt(process.env.MAX_RETRIES ?? 0)
    let retryCount = retries.get(sessionId) ?? 0
    maxRetries = maxRetries < 1 ? 1 : maxRetries

    if (retryCount < maxRetries) {
        retryCount++
        console.log('Reconnecting...', {
            attempts: retryCount,
            sessionId,
        })
        retries.set(sessionId, retryCount)
        return true
    }

    return false
}

const isUser = (remoteJid) => {
    if (!remoteJid) {
        return false
    }

    return isJidUser(remoteJid) || isLidUser(remoteJid)
}

const createSession = async (sessionId, isLegacy = false, res = null) => {
    const sessionPath = (isLegacy ? 'legacy_' : 'md_') + sessionId + (isLegacy ? '.json' : '')

    const logger = pino({ level: 'warn' })

    let state, saveCreds
    if (!isLegacy) {
        ;({ state, saveCreds } = await useMultiFileAuthState(sessionsDir(sessionPath)))
    }

    const { version } = await fetchLatestBaileysVersion()

    const socketConfig = {
        auth: state,
        version,
        printQRInTerminal: false,
        logger,
        browser: Browsers.ubuntu('Chrome'),
        patchMessageBeforeSending: (message) => {
            const needsPatch = Boolean(message.buttonsMessage || message.listMessage)
            if (needsPatch) {
                message = {
                    viewOnceMessage: {
                        message: {
                            messageContextInfo: {
                                deviceListMetadataVersion: 2,
                                deviceListMetadata: {},
                            },
                            ...message,
                        },
                    },
                }
            }

            return message
        },
    }

    const sock = makeWASocket(socketConfig)

    sessions.set(sessionId, {
        ...sock,
        isLegacy,
    })

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('messaging-history.set', (history) => {
        console.log('messaging-history.set', history)
    })

    sock.ev.on('messages.upsert', async (m) => {
        try {
            const webhookData = {}

            for (const msg of m.messages) {
                if (msg.message?.protocolMessage) {
                    const protocolMessageType = msg.message?.protocolMessage.type
                    if (protocolMessageType === proto.Message.ProtocolMessage.Type.HISTORY_SYNC_NOTIFICATION) {
                        try {
                            const historySyncNotification = getHistoryMsg(msg.message)
                            const res = await downloadAndProcessHistorySyncNotification(historySyncNotification, {})
                            const { chats, contacts, messages, syncType, progress } = res
                            const { success, totalMessages } = await sendBatchWebhook({
                                sessionId,
                                chats,
                                contacts,
                                messages,
                                syncType,
                                progress,
                            })
                            console.log(
                                `History sync ${sessionId}: ${totalMessages} msgs - status: ${
                                    success ? 'success' : 'failed'
                                }`
                            )
                        } catch (error) {
                            console.log('History sync download error (skipping):', error.message)
                            // Continue processing other messages even if this history sync fails
                        }
                    }
                } else {
                    const { remoteJid, fromMe, senderLid, senderPn } = msg.key

                    if (isUser(remoteJid)) {
                        if (msg.status !== undefined) {
                            msg.status = proto.WebMessageInfo.Status[msg.status] ?? msg.status
                        }

                        webhookData.remoteId = msg.key.remoteJid
                        webhookData.remoteIdAlt = senderLid
                        if (isLidUser(remoteJid)) {
                            webhookData.remoteIdAlt = senderPn
                        }

                        webhookData.sessionId = sessionId
                        webhookData.messageId = msg.key.id
                        webhookData.message = msg.message
                        sentWebHook(sessionId, webhookData, fromMe, msg)
                    }
                }
            }
        } catch (e) {
            console.log('messages.upsert error', e)
        }
    })

    sock.ev.on('messages.update', async (updates) => {
        try {
            const statushook = process.env.APP_URL + '/api/chat/update-status'
            for (const update of updates) {
                if (isUser(update.key.remoteJid)) {
                    axios.post(statushook, { update }).catch((err) => {
                        console.log('messages.update error', err)
                    })
                }
            }
        } catch {}
    })

    sock.ev.on('contacts.upsert', async (contacts) => {
        try {
            const contactWebhook = process.env.APP_URL + '/api/contact/webhook/' + sessionId
            axios
                .post(contactWebhook, {
                    type: 'upsert',
                    contacts,
                })
                .catch((err) => {
                    console.log('contacts.upsert error', err)
                })
        } catch (e) {
            console.log('contacts.upsert error', e)
        }
    })

    sock.ev.on('contacts.update', async (contacts) => {
        try {
            const contactWebhook = process.env.APP_URL + '/api/contact/webhook/' + sessionId
            axios
                .post(contactWebhook, {
                    type: 'update',
                    contacts,
                })
                .catch((err) => {
                    console.log('contacts.update error', err)
                })
        } catch (e) {
            console.log('contacts.update error', e)
        }
    })

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect } = update
        const statusCode = lastDisconnect?.error?.output?.statusCode

        if (connection === 'open') {
            retries.delete(sessionId)
        }

        if (connection === 'close') {
            if (statusCode === DisconnectReason.loggedOut || !shouldReconnect(sessionId)) {
                if (res && !res.headersSent) {
                    response(res, 500, false, 'Unable to create session.')
                }

                deleteSession(sessionId, isLegacy)
                return
            }

            setTimeout(
                () => {
                    createSession(sessionId, isLegacy, res)
                },
                statusCode === DisconnectReason.restartRequired ? 0 : parseInt(process.env.RECONNECT_INTERVAL ?? 0)
            )
        }

        if (update.qr) {
            if (res && !res.headersSent) {
                try {
                    const qrDataURL = await toDataURL(update.qr)
                    response(res, 200, true, 'QR code received, please scan the QR code.', {
                        qr: qrDataURL,
                    })
                    return
                } catch {
                    response(res, 500, false, 'Unable to create QR code.')
                }
            }

            try {
                await sock.logout()
            } catch {
            } finally {
                deleteSession(sessionId, isLegacy)
            }
        }
    })
}

const getSession = (sessionId) => {
    return sessions.get(sessionId) ?? null
}

const setDeviceStatus = (sessionId, status) => {
    const statusUrl = process.env.APP_URL + '/api/set-device-status/' + sessionId + '/' + status

    try {
        axios
            .post(statusUrl)
            .then(() => {})
            .catch(() => {})
    } catch {}
}

const sentWebHook = (sessionId, webhookData, fromMe, msg) => {
    const webhookUrl = process.env.APP_URL + '/api/send-webhook/' + sessionId

    try {
        axios
            .post(webhookUrl, {
                fromMe,
                from: webhookData.remoteId,
                fromAlt: webhookData.remoteIdAlt,
                messageId: webhookData.messageId,
                message: webhookData.message,
                other: msg,
            })
            .then(() => {})
            .catch(() => {})
    } catch {}
}

const sendBatchWebhook = async ({ sessionId, chats, contacts, messages, syncType, progress }) => {
    const deviceId = sessionId.replace('device_', '')
    const webhookUrl = process.env.BE_WHATSAPP + '/api/contacts/batch-webhook/' + deviceId

    let success = false

    try {
        const response = await axios.post(webhookUrl, {
            chats,
            contacts,
            messages,
            syncType,
            progress,
        })

        if (response.status >= 200 && response.status < 300) {
            success = response.data?.success || false
        }
    } catch (error) {
        console.log('Batch webhook error:', error.message)
        success = false
    }

    return {
        success,
        totalMessages: messages.length,
    }
}

const deleteSession = (sessionId, isLegacy = false) => {
    const sessionPath = (isLegacy ? 'legacy_' : 'md_') + sessionId + (isLegacy ? '.json' : '')
    const rmOptions = { force: true, recursive: true }

    rmSync(sessionsDir(sessionPath), rmOptions)
    sessions.delete(sessionId)
    retries.delete(sessionId)
    setDeviceStatus(sessionId, 0)
}

const getChatList = async (sessionId, isGroup = false) => {
    const session = getSession(sessionId)

    if (!session) {
        return []
    }

    if (isGroup) {
        try {
            // Fetch all groups the bot is part of
            const groups = await session.groupFetchAllParticipating()
            return Object.values(groups)
        } catch {
            return []
        }
    } else {
        // For individual chats, there's no direct API to fetch all
        // The Laravel backend should track contacts from incoming messages
        // This returns empty array as contacts should be managed in the database
        return []
    }
}

const isExists = async (session, jid, isGroup = false) => {
    try {
        let result
        if (isGroup) {
            result = await session.groupMetadata(jid)
            return Boolean(result.id)
        }

        if (session.isLegacy) {
            result = await session.onWhatsApp(jid)
        } else {
            ;[result] = await session.onWhatsApp(jid)
        }

        return result.exists
    } catch {
        return false
    }
}

const sendMessage = async (session, receiver, message, delayMs = 1000) => {
    try {
        await delay(parseInt(delayMs))
        return session.sendMessage(receiver, message)
    } catch {
        return Promise.reject(null)
    }
}

const formatPhone = (phoneNumber) => {
    if (phoneNumber.includes('@')) {
        return jidNormalizedUser(phoneNumber)
    }

    const cleaned = phoneNumber.replace(/\D/g, '')
    return `${cleaned}@s.whatsapp.net`
}

const formatGroup = (groupId) => {
    if (groupId.endsWith('@g.us')) {
        return groupId
    }

    let cleaned = groupId.replace(/[^\d-]/g, '')
    return (cleaned += '@g.us')
}

const cleanup = () => {
    console.log('Running cleanup before exit.')
}

const init = () => {
    readdir(sessionsDir(), (err, files) => {
        if (err) {
            throw err
        }

        for (const file of files) {
            if (!file.startsWith('md_') && !file.startsWith('legacy_')) {
                continue
            }

            const filename = file.replace('.json', '')
            const isLegacy = filename.split('_', 1)[0] !== 'md'
            const sessionId = filename.substring(isLegacy ? 7 : 3)
            createSession(sessionId, isLegacy)
        }
    })
}

export {
    isSessionExists,
    createSession,
    getSession,
    deleteSession,
    getChatList,
    isExists,
    sendMessage,
    formatPhone,
    formatGroup,
    cleanup,
    init,
    downloadMediaMessage,
}
