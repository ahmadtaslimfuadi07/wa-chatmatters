<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Smstransaction;
use App\Models\Smstesttransactions;
use App\Http\Requests\Bulkrequest;
use App\Models\User;
use App\Models\App;
use App\Models\Device;
use App\Models\Contact;
use App\Models\Template;
use App\Models\Webhooks;
use App\Models\Reply;
use App\Models\Webhookslogs;
use Carbon\Carbon;
use App\Traits\Whatsapp;
use App\Models\Chats;
use Http;
use Auth;
use Str;
use DB;
use Session;
class BulkController extends Controller
{
    use Whatsapp;

    /**
     * Map WhatsApp status string to numeric value
     *
     * @param mixed $status
     * @return int
     */
    private function mapStatusToNumber($status)
    {
        if (is_null($status)) {
            return null;
        }

        // Status enum mapping
        $statusMap = [
            'ERROR' => 0,
            'PENDING' => 1,
            'SERVER_ACK' => 2,
            'DELIVERY_ACK' => 3,
            'READ' => 4,
            'PLAYED' => 5,
        ];

        // If already a number, validate and return
        if (is_numeric($status)) {
            $status = (int) $status;
            return ($status >= 0 && $status <= 5) ? $status : null;
        }

        // If string, map to number
        $statusUpper = strtoupper(trim($status));
        return $statusMap[$statusUpper] ?? null;
    }


    /**
     * sent message
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submitRequest(Bulkrequest $request)
    {

        // dd($request->all());
        $userQuery = User::where('status', 1)->where('will_expire', '>', now())->where('authkey', $request->authkey);
        $userIds = $userQuery->pluck('id');
        $user = $userQuery->first();
        $userCount = $userQuery->count();
        $app = App::where('key', $request->appkey)->whereHas('device')->with('device')->where('status', 1)->first();
        $isValidUserGroup = $userCount > 0 && $app && $app->user_id && in_array($app->user_id, $userIds->toArray());

        if ($user == null || $app == null || !$isValidUserGroup) {
            return response()->json(['error' => 'Invalid Auth and AppKey'], 401);
        }

        if ($isValidUserGroup) {
            $user = User::where('id', $app->user_id)->first();
        }

        // if (getUserPlanData('messages_limit', $user->id) == false) {
        //     return response()->json([
        //         'message'=>__('Maximum Monthly Messages Limit Exceeded')
        //     ],401);
        // }

        if (!empty($request->template_id)) {

            $template = Template::where('user_id', $user->id)->where('uuid', $request->template_id)->where('status', 1)->first();
            if (empty($template)) {
                return response()->json(['error' => 'Template Not Found'], 401);
            }

            if (isset($template->body['text'])) {
                $body = $template->body;
                $text = $this->formatText($template->body['text'], [], $user);
                $text = $this->formatCustomText($text, $request->variables ?? []);
                $body['text'] = $text;
            } else {
                $body = $template->body;
            }
            $type = $template->type;


        } else {

            $text = $this->formatText($request->message);
            if (!empty($request->file)) {


                $explode = explode('.', $request->file);
                $file_type = strtolower(end($explode));

                $extentions = [
                    'jpg' => 'image',
                    'jpeg' => 'image',
                    'png' => 'image',
                    'webp' => 'image',
                    'pdf' => 'document',
                    'docx' => 'document',
                    'xlsx' => 'document',
                    'csv' => 'document',
                    'txt' => 'document'
                ];

                if (!isset($extentions[$file_type])) {
                    $validators['error'] = 'file type should be jpg,jpeg,png,webp,pdf,docx,xlsx,csv,txt';
                    return response()->json($validators, 403);
                }


                $body[$extentions[$file_type]] = ['url' => $request->file];
                $body['caption'] = $text;
                $type = 'text-with-media';
            } else {
                $body['text'] = $text;
                $type = 'plain-text';
            }

        }

        if (!isset($body)) {
            return response()->json(['error' => 'Request Failed'], 401);
        }

        try {
            $response = $this->messageSend($body, $app->device_id, $request->to, $type, true);
            if (is_array($response) && $response['status'] == 200) {

                $logs['user_id'] = $user->id;
                $logs['device_id'] = $app->device_id;
                $logs['app_id'] = $app->id;
                $logs['from'] = $app->device->phone ?? null;
                $logs['to'] = $request->to;
                $logs['template_id'] = $template->id ?? null;
                $logs['type'] = 'from_api';

                $this->saveLog($logs);

                return response()->json([
                    'message_status' => 'Success',
                    'data' => [
                        'from' => $app->device->phone ?? null,
                        'to' => $request->to,
                        'status_code' => 200,
                    ]
                ], 200);
            } else {
                return response()->json(['error' => 'Request Failed'], 401);

            }

        } catch (Exception $e) {

            return response()->json(['error' => 'Request Failed'], 401);
        }

    }


    /**
     * set status device
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function setStatus($device_id, $status)
    {
        $device_id = str_replace('device_', '', $device_id);

        $device = Device::where('id', $device_id)->first();
        if (!empty($device)) {
            $device->status = $status;
            $device->save();

            if ($status == 0) {
                $user_id = $device->user_id;
                $phone = $device->phone;
                $date = Carbon::now()->format('Y-m-d H:i:s');
                $this->inactiveDeviceWebhook($user_id, $phone, $date);
            }
        }
    }

    private function inactiveDeviceWebhook($user_id, $phone, $date)
    {
        $user = User::where('id', $user_id)->first();
        if (!empty($user)) {
            $email = $user->email ?? '';

            $isValidEmail = (
                strpos($email, 'passionjewelry.co.id') !== false ||
                strpos($email, 'diamondnco.id') !== false
            );

            if ($isValidEmail) {
                $data = [
                    'phone' => $phone,
                    'account' => $email,
                    'timestamp' => $date,
                ];

                Http::post('https://erp.pakyjop.com/restfulservices/chatmatters/webhook/notifyInactive', $data);
            }
        }
    }


    /**
     * receive webhook response
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function webHook(Request $request, $device_id)
    {
        $current_timestamp = Carbon::now()->timestamp;
        $mimetype = [
            'application/jpg' => '.jpg',
            'image/jpeg' => '.jpeg',
            'application/png' => '.png',
            'image/png' => '.png',
            'application/webp' => '.webp',
            'image/webp' => '.webp',
            'image/gif' => '.gif',
            'text/plain' => '.txt',
            'application/pdf' => '.pdf',
            'application/vnd.ms-powerpoint' => '.ppt',
            'application/msword' => '.doc',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'video/mp4' => '.mp4',
            'video/3gp' => '.3gp',
            'video/quicktime' => '.mov',
            'text/csv' => '.csv',
            'image/avif' => '.avif',
            'image/heic' => '.heic',
            'image/heif' => '.heif',
            'audio/mp3' => '.mp3',
        ];

        $session = $device_id;
        $device_id = str_replace('device_', '', $device_id);

        $device = Device::with('user')->whereHas('user', function ($query) {
            return $query->where('will_expire', '>', now());
        })->where('id', $device_id)->first();

        // Check if from is LID or JID
        if (strpos($request->from, '@lid') !== false) {
            // from is LID, fromAlt is JID
            $lid = explode('@', $request->from)[0];
            $request_from = $request->fromAlt ? explode('@', $request->fromAlt)[0] : null;
        } else {
            // from is JID, fromAlt might be LID
            $request_from = explode('@', $request->from)[0];
            $lid = ($request->fromAlt && strpos($request->fromAlt, '@lid') !== false)
                ? explode('@', $request->fromAlt)[0]
                : null;
        }

        $message_id = $request->messageId ?? '';
        $message = json_encode($request->message ?? '');
        $message = json_decode($message);

        $device_id = $device_id;

        //MESSAGES TYPE
        $file = null;
        if (isset($message->conversation)) {
            $message = $message->conversation ?? null;
        } elseif (isset($message->extendedTextMessage)) {
            $message = $message->extendedTextMessage->text ?? null;
        } elseif (isset($message->buttonsResponseMessage)) {
            $message = $message->buttonsResponseMessage->selectedDisplayText ?? null;
        } elseif (isset($message->listResponseMessage)) {
            $message = $message->listResponseMessage->title ?? null;
        } elseif (isset($message->imageMessage)) {
            if ($device->file == 1) {
                $extentions = $mimetype[$message->imageMessage->mimetype];
                $message = $message->imageMessage->caption ?? null;
                $response = $this->downloadFileMessage($device_id, $request->other);
                Storage::put('files/images/' . $device->uuid . '/' . $message_id . $extentions, $response['data']);
                $url = Storage::url('files/images/' . $device->uuid . '/' . $message_id . $extentions);
                $file = $url;
                $message = $message;
            } else {
                $message = $message->imageMessage->caption ?? null;
            }
        } elseif (isset($message->documentMessage)) {
            if ($device->file == 1) {
                $extentions = $mimetype[$message->documentMessage->mimetype];
                $message = $message->documentMessage->caption ?? null;
                $response = $this->downloadFileMessage($device_id, $request->other);
                Storage::put('files/document/' . $device->uuid . '/' . $message_id . $extentions, $response['data']);
                $url = Storage::url('files/document/' . $device->uuid . '/' . $message_id . $extentions);
                $file = $url;
                $message = $message;
            } else {
                $message = $message->documentMessage->caption ?? null;
            }
        } elseif (isset($message->documentWithCaptionMessage)) {
            if ($device->file == 1) {
                $extentions = $mimetype[$message->documentWithCaptionMessage->message->documentMessage->mimetype];
                $message = $message->documentWithCaptionMessage->message->documentMessage->caption ?? null;
                $dataRequest = $request->other;
                info($dataRequest['message']['documentWithCaptionMessage']['message']);
                $dataRequest['message'] = $dataRequest['message']['documentWithCaptionMessage']['message'];
                $response = $this->downloadFileMessage($device_id, $dataRequest);
                Storage::put('files/document/' . $device->uuid . '/' . $message_id . $extentions, $response['data']);
                $url = Storage::url('files/document/' . $device->uuid . '/' . $message_id . $extentions);
                $file = $url;
                $message = $message;
            } else {
                $message = $message->documentWithCaptionMessage->message->documentMessage->caption ?? null;
            }
        } elseif (isset($message->videoMessage)) {
            if ($device->file == 1) {
                $extentions = $mimetype[$message->videoMessage->mimetype];
                $message = $message->videoMessage->caption ?? null;
                $response = $this->downloadFileMessage($device_id, $request->other);
                Storage::put('files/video/' . $device->uuid . '/' . $message_id . $extentions, $response['data']);
                $url = Storage::url('files/video/' . $device->uuid . '/' . $message_id . $extentions);
                $file = $url;
                $message = $message;
            } else {
                $message = $message->videoMessage->caption ?? null;
            }
        } else {
            $message = null;
        }

        //INSERT MESSAGES
        try {
            // Find existing contact by any matching identifier
            $contact = Contact::where('user_id', $device->user_id)
                ->where('device_id', $device->id)
                ->where(function ($query) use ($request_from, $lid) {
                    if (!empty($request_from)) {
                        $query->orWhere('phone', $request_from);
                    }
                    if (!empty($lid)) {
                        $query->orWhere('lid', $lid);
                    }
                })
                ->first();

            // Prepare update data (only non-null values)
            $data = [
                'user_id' => $device->user_id,
                'device_id' => $device->id,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Only add phone if provided
            if (!empty($request_from)) {
                $data['phone'] = $request_from;
                $data['device_phone'] = $device->id . '_' . $request_from;
            }

            // Only add lid if provided
            if (!empty($lid)) {
                $data['lid'] = $lid;
                $data['device_lid'] = $device->id . '_' . $lid;
            }

            if ($contact) {
                // Update existing contact (only fields that are not null)
                $contact->update($data);
                $is_exist = $contact;
            } else {
                // Create new contact
                $data['created_at'] = date('Y-m-d H:i:s');
                $is_exist = Contact::create($data);
            }
            $contact_id = $is_exist->id;
            $chatInsert = Chats::updateOrCreate(
                [
                    'device_unic_id' => $device->id . "_" . $request->messageId,
                ],
                [
                    'user_id' => $device->user_id,
                    'contact_id' => $contact_id,
                    'device_id' => $device->id,
                    'phone' => $request_from ?? $is_exist->phone,
                    'file' => $file,
                    'unic_id' => $request->messageId,
                    'device_unic_id' => $device->id . "_" . $request->messageId,
                    'message' => $message,
                    'fromMe' => ($request->fromMe == 1) ? $request->fromMe : 'false',
                    'timestamp' => $current_timestamp,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            if ($request->fromMe == 1 && is_null($chatInsert->status)) {
                $mappedStatus = $this->mapStatusToNumber($request->other['status'] ?? null);
                $chatInsert->update(['status' => $mappedStatus]);
            }

            if (($is_exist->wasRecentlyCreated || is_null($is_exist->name)) && $request->fromMe != 1) {
                $is_exist->update(['name' => $request->other['pushName'] ?? NULL]);
            }
        } catch (Exception $e) {
            info($e);
        }

        //START WEBHOOK SEND
        try {
            $webhook = Webhooks::where('device_id', $device_id)->where('status', 1)->get();
            $body = [
                'messageId' => $message_id,
                'fromMe' => $request->fromMe,
                'from' => $request_from ?? $contact->phone,
                'lid' => $lid ?? $contact->lid,
                'to' => $device->phone,
                'timestamp' => $current_timestamp,
                'datetimes' => date('Y-m-d H:i:s'),
            ];
            if ($message != null) {
                $body['message'] = $message;
            }
            if ($file != null) {
                $body['file'] = $file;
            }
            $client = new \GuzzleHttp\Client(['verify' => false]);
            for ($i = 0; $i < count($webhook); $i++) {
                $response = $client->request('POST', $webhook[$i]->url, ['json' => $body]);
                $statusCode = $response->getStatusCode();
                $content = $response->getBody();
                $webhooklogs = new Webhookslogs;
                $webhooklogs->webhooks_id = $webhook[$i]->id;
                $webhooklogs->request = json_encode($body, true);
                $webhooklogs->response = json_encode(json_decode($content));
                $webhooklogs->response_code = $statusCode;
                $webhooklogs->endpoint = $webhook[$i]->url;
                $webhooklogs->created_at = date('Y-m-d H:i:s');
                $webhooklogs->updated_at = date('Y-m-d H:i:s');
                $webhooklogs->save();
            }
        } catch (Exception $e) {
            info($e);
        }

        //START CHATBOT
        if ($device != null && $message != null && !$request->fromMe) {
            $replies = Reply::where('device_id', $device_id)->with('template')->where('keyword', 'LIKE', '%' . $message . '%')->latest()->get();
            if ($replies->isEmpty()) {
                $messages = explode(' ', $message);
                if (count($messages) < 50) {
                    $replies = Reply::where('device_id', $device_id)->where('match_type', '!=', 'equal')->with('template');
                    $replies = $replies->where(function ($query) use ($messages) {
                        for ($i = 0; $i < count($messages); $i++) {
                            $replies = $query->orWhere("keyword", 'like', '%' . $messages[$i] . '%');
                        }
                    });
                    $replies = $replies->latest()->get();
                }
            }

            foreach ($replies as $key => $reply) {
                // if ($reply->match_type == 'equal') {

                if ($reply->reply_type == 'text') {

                    $logs['user_id'] = $device->user_id;
                    $logs['device_id'] = $device->id;
                    $logs['from'] = $device->phone ?? null;
                    $logs['to'] = $request_from;
                    $logs['type'] = 'chatbot';
                    $this->saveLog($logs);

                    return response()->json([
                        'message' => array('text' => $reply->reply),
                        'receiver' => $request->from,
                        'session_id' => $session
                    ], 200);


                } else {
                    if (!empty($reply->template)) {
                        $template = $reply->template;

                        if (isset($template->body['text'])) {
                            $body = $template->body;
                            $text = $this->formatText($template->body['text'], [], $device->user);
                            $body['text'] = $text;

                        } else {
                            $body = $template->body;
                        }

                        $logs['user_id'] = $device->user_id;
                        $logs['device_id'] = $device->id;
                        $logs['from'] = $device->phone ?? null;
                        $logs['to'] = $request_from;
                        $logs['type'] = 'chatbot';
                        $logs['template_id'] = $template->id ?? null;
                        $this->saveLog($logs);

                        return response()->json([
                            'message' => $body,
                            'receiver' => $request->from,
                            'session_id' => $session
                        ], 200);
                    }
                }
                break;
                // }
            }
        }

        return response()->json([
            'message' => array('text' => null),
            'receiver' => $request->from,
            'session_id' => $session
        ], 403);
    }

    public function batchWebHook(Request $request, $device_id)
    {
        $device_id = str_replace('device_', '', $device_id);

        $device = Device::with('user')
            ->where('id', $device_id)
            ->whereHas('user', fn($q) => $q->where('will_expire', '>', now()))
            ->first();

        if (!$device) {
            return response()->json(['error' => 'Device not found or expired'], 404);
        }

        $device->update(['sync' => 0]);

        DB::beginTransaction();
        try {
            // OPTIMIZATION 1: Bulk contact sync (syncType 4)
            if ($request->syncType == 4 && !empty($request->contacts)) {
                $contactsData = [];
                foreach ($request->contacts as $contactItem) {
                    $phone = strtok($contactItem['id'], '@');
                    $devicePhone = !empty($phone) ? $device->id . "_" . $phone : null;

                    $contactsData[] = [
                        'device_phone' => $devicePhone,
                        'device_lid' => null,
                        'user_id' => $device->user_id,
                        'device_id' => $device->id,
                        'phone' => $phone,
                        'name' => $contactItem['notify'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($contactsData)) {
                    // Fetch all existing contacts by device_phone OR device_lid
                    $allDevicePhones = array_filter(array_column($contactsData, 'device_phone'));
                    $allDeviceLids = array_filter(array_column($contactsData, 'device_lid'));

                    $existingContacts = Contact::where('user_id', $device->user_id)
                        ->where('device_id', $device->id)
                        ->where(function ($query) use ($allDevicePhones, $allDeviceLids) {
                            if (!empty($allDevicePhones)) {
                                $query->orWhereIn('device_phone', $allDevicePhones);
                            }
                            if (!empty($allDeviceLids)) {
                                $query->orWhereIn('device_lid', $allDeviceLids);
                            }
                        })
                        ->get();

                    // Build lookup maps
                    $existingByDevicePhone = [];
                    $existingByDeviceLid = [];
                    foreach ($existingContacts as $contact) {
                        if ($contact->device_phone) {
                            $existingByDevicePhone[$contact->device_phone] = $contact;
                        }
                        if ($contact->device_lid) {
                            $existingByDeviceLid[$contact->device_lid] = $contact;
                        }
                    }

                    $toInsert = [];
                    $seenInBatch = []; // Track contacts we've already processed in this batch

                    foreach ($contactsData as $newData) {
                        $existing = null;

                        // Create a unique key for deduplication within the batch
                        $batchKey = $newData['device_phone'] ?? $newData['device_lid'];

                        // Skip if we already processed this contact in this batch
                        if (isset($seenInBatch[$batchKey])) {
                            continue;
                        }
                        $seenInBatch[$batchKey] = true;

                        // Find existing by device_phone first, then by device_lid
                        if (!empty($newData['device_phone']) && isset($existingByDevicePhone[$newData['device_phone']])) {
                            $existing = $existingByDevicePhone[$newData['device_phone']];
                        } elseif (!empty($newData['device_lid']) && isset($existingByDeviceLid[$newData['device_lid']])) {
                            $existing = $existingByDeviceLid[$newData['device_lid']];
                        }

                        if ($existing) {
                            // Update existing - merge non-null values
                            $updateData = ['updated_at' => now()];
                            if (!empty($newData['phone']) && empty($existing->phone)) {
                                $updateData['phone'] = $newData['phone'];
                            }
                            if (!empty($newData['device_phone']) && empty($existing->device_phone)) {
                                $updateData['device_phone'] = $newData['device_phone'];
                            }
                            if (isset($newData['name']) && !empty($newData['name']) && empty($existing->name)) {
                                $updateData['name'] = $newData['name'];
                            }

                            if (count($updateData) > 1) { // More than just updated_at
                                $existing->update($updateData);
                            }
                        } else {
                            // New contact
                            $toInsert[] = $newData;
                        }
                    }

                    // Bulk insert new contacts
                    if (!empty($toInsert)) {
                        Contact::insert($toInsert);
                    }
                }
            }

            // OPTIMIZATION 2: Bulk chat sync (syncType 0)
            if ($request->syncType == 0 && !empty($request->chats)) {
                $contactsData = [];
                foreach ($request->chats as $chatItem) {
                    if (!isset($chatItem['pnJid'])) {
                        continue;
                    }
                    $phone = strtok($chatItem['pnJid'], '@');
                    $lid = strtok($chatItem['accountLid'], '@');

                    // Build separate composite keys for phone and lid
                    $devicePhone = !empty($phone) ? $device->id . "_" . $phone : null;
                    $deviceLid = !empty($lid) ? $device->id . "_" . $lid : null;

                    $contactsData[] = [
                        'device_phone' => $devicePhone,
                        'device_lid' => $deviceLid,
                        'user_id' => $device->user_id,
                        'device_id' => $device->id,
                        'phone' => $phone,
                        'lid' => $lid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($contactsData)) {
                    // Fetch all existing contacts by device_phone OR device_lid
                    $allDevicePhones = array_filter(array_column($contactsData, 'device_phone'));
                    $allDeviceLids = array_filter(array_column($contactsData, 'device_lid'));

                    $existingContacts = Contact::where('user_id', $device->user_id)
                        ->where('device_id', $device->id)
                        ->where(function ($query) use ($allDevicePhones, $allDeviceLids) {
                            if (!empty($allDevicePhones)) {
                                $query->orWhereIn('device_phone', $allDevicePhones);
                            }
                            if (!empty($allDeviceLids)) {
                                $query->orWhereIn('device_lid', $allDeviceLids);
                            }
                        })
                        ->get();

                    // Build lookup maps by BOTH device_phone and device_lid
                    $existingByDevicePhone = [];
                    $existingByDeviceLid = [];
                    foreach ($existingContacts as $contact) {
                        if ($contact->device_phone) {
                            $existingByDevicePhone[$contact->device_phone] = $contact;
                        }
                        if ($contact->device_lid) {
                            $existingByDeviceLid[$contact->device_lid] = $contact;
                        }
                    }

                    $toInsert = [];
                    $seenInBatch = []; // Track contacts we've already processed in this batch

                    foreach ($contactsData as $newData) {
                        $existing = null;

                        // Create a unique key for deduplication within the batch
                        $batchKey = $newData['device_phone'] ?? $newData['device_lid'];

                        // Skip if we already processed this contact in this batch
                        if (isset($seenInBatch[$batchKey])) {
                            continue;
                        }
                        $seenInBatch[$batchKey] = true;

                        // Find existing by device_phone first, then by device_lid
                        if (!empty($newData['device_phone']) && isset($existingByDevicePhone[$newData['device_phone']])) {
                            $existing = $existingByDevicePhone[$newData['device_phone']];
                        } elseif (!empty($newData['device_lid']) && isset($existingByDeviceLid[$newData['device_lid']])) {
                            $existing = $existingByDeviceLid[$newData['device_lid']];
                        }

                        if ($existing) {
                            // Update existing - merge non-null values
                            $updateData = ['updated_at' => now()];
                            if (!empty($newData['phone']) && empty($existing->phone)) {
                                $updateData['phone'] = $newData['phone'];
                            }
                            if (!empty($newData['lid']) && empty($existing->lid)) {
                                $updateData['lid'] = $newData['lid'];
                            }
                            if (!empty($newData['device_phone']) && empty($existing->device_phone)) {
                                $updateData['device_phone'] = $newData['device_phone'];
                            }
                            if (!empty($newData['device_lid']) && empty($existing->device_lid)) {
                                $updateData['device_lid'] = $newData['device_lid'];
                            }
                            if (isset($newData['name']) && !empty($newData['name']) && empty($existing->name)) {
                                $updateData['name'] = $newData['name'];
                            }

                            if (count($updateData) > 1) { // More than just updated_at
                                $existing->update($updateData);
                            }
                        } else {
                            // New contact
                            $toInsert[] = $newData;
                        }
                    }

                    // Bulk insert new contacts
                    if (!empty($toInsert)) {
                        Contact::insert($toInsert);
                    }
                }
            }

            // OPTIMIZATION 3: Bulk message processing
            if (!empty($request->messages)) {
                // Step 1: Collect all phones and lids from messages
                $allPhones = [];
                $allLids = [];
                $messageDataArray = [];

                foreach ($request->messages as $msg) {
                    if (!isset($msg['message'])) {
                        continue;
                    }
                    $key = $msg['key'] ?? [];
                    $from = $key['remoteJid'] ?? '';

                    // Determine if incoming is LID or phone
                    if (str_contains($from, '@lid')) {
                        $lid = strtok($from, '@');
                        $phone = null;
                    } elseif (str_contains($from, '@s.whatsapp.net')) {
                        $phone = strtok($from, '@');
                        $lid = null;
                    } else {
                        continue;
                    }

                    // Validate phone and LID - skip invalid values like "0"
                    if ($phone !== null && (empty($phone) || $phone === '0' || !is_numeric($phone) || strlen($phone) < 4)) {
                        $phone = null;
                    }
                    if ($lid !== null && (empty($lid) || strlen($lid) < 3)) {
                        $lid = null;
                    }

                    // Skip messages with no valid identifier
                    if ($phone === null && $lid === null) {
                        continue;
                    }

                    // Collect for bulk lookup
                    if ($phone) $allPhones[] = $phone;
                    if ($lid) $allLids[] = $lid;

                    // Extract message text
                    $msgObj = json_decode(json_encode($msg['message'] ?? []));
                    $text = $msgObj->conversation
                        ?? $msgObj->extendedTextMessage->text ?? null
                        ?? $msgObj->buttonsResponseMessage->selectedDisplayText ?? null
                        ?? $msgObj->listResponseMessage->title ?? null
                        ?? $msgObj->imageMessage->caption ?? null
                        ?? $msgObj->documentMessage->caption ?? null
                        ?? $msgObj->documentWithCaptionMessage->message->documentMessage->caption ?? null
                        ?? $msgObj->videoMessage->caption ?? null
                        ?? null;

                    $messageDataArray[] = [
                        'key' => $key,
                        'message' => $text,
                        'timestamp' => $msg['messageTimestamp'] ?? now()->timestamp,
                        'status' => $msg['status'] ?? null,
                        'phone' => $phone,
                        'lid' => $lid,
                    ];
                }

                // Step 2: Build device_phone and device_lid keys for all messages
                $allDevicePhones = [];
                $allDeviceLids = [];
                foreach ($messageDataArray as $data) {
                    if ($data['phone']) {
                        $allDevicePhones[] = $device->id . '_' . $data['phone'];
                    }
                    if ($data['lid']) {
                        $allDeviceLids[] = $device->id . '_' . $data['lid'];
                    }
                }

                // Step 2: Bulk fetch existing contacts by device_phone OR device_lid in ONE query
                $existingContacts = Contact::where('user_id', $device->user_id)
                    ->where('device_id', $device->id)
                    ->where(function ($query) use ($allDevicePhones, $allDeviceLids) {
                        if (!empty($allDevicePhones)) {
                            $query->orWhereIn('device_phone', array_unique($allDevicePhones));
                        }
                        if (!empty($allDeviceLids)) {
                            $query->orWhereIn('device_lid', array_unique($allDeviceLids));
                        }
                    })
                    ->get();

                // Step 3: Build lookup maps for device_phone AND device_lid
                $contactsByDevicePhone = [];
                $contactsByDeviceLid = [];

                foreach ($existingContacts as $contact) {
                    if ($contact->device_phone) {
                        $contactsByDevicePhone[$contact->device_phone] = $contact;
                    }
                    if ($contact->device_lid) {
                        $contactsByDeviceLid[$contact->device_lid] = $contact;
                    }
                }

                // Step 4: Prepare contact data for upsert (handles both new and existing)
                $contactsToUpsert = [];
                $contactKeys = []; // Track unique contacts by device_phone or device_lid

                foreach ($messageDataArray as $data) {
                    $phone = $data['phone'];
                    $lid = $data['lid'];
                    $devicePhone = $phone ? $device->id . '_' . $phone : null;
                    $deviceLid = $lid ? $device->id . '_' . $lid : null;

                    // Use device_phone as primary key, fall back to device_lid
                    $uniqueKey = $devicePhone ?? $deviceLid;

                    // Skip if we already have this contact in the upsert batch
                    if (isset($contactKeys[$uniqueKey])) {
                        continue;
                    }

                    $contactKeys[$uniqueKey] = true;
                    $contactsToUpsert[] = [
                        'user_id' => $device->user_id,
                        'device_id' => $device->id,
                        'phone' => $phone,
                        'lid' => $lid,
                        'device_phone' => $devicePhone,
                        'device_lid' => $deviceLid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Step 5: Bulk upsert contacts (creates new OR updates existing)
                if (!empty($contactsToUpsert)) {
                    // Fetch existing contacts to merge data
                    $allDevicePhones = array_filter(array_column($contactsToUpsert, 'device_phone'));
                    $allDeviceLids = array_filter(array_column($contactsToUpsert, 'device_lid'));

                    $existingForUpsert = Contact::where('user_id', $device->user_id)
                        ->where('device_id', $device->id)
                        ->where(function ($query) use ($allDevicePhones, $allDeviceLids) {
                            if (!empty($allDevicePhones)) {
                                $query->orWhereIn('device_phone', $allDevicePhones);
                            }
                            if (!empty($allDeviceLids)) {
                                $query->orWhereIn('device_lid', $allDeviceLids);
                            }
                        })
                        ->get();

                    // Build lookup maps
                    $existingByPhone = [];
                    $existingByLid = [];
                    foreach ($existingForUpsert as $contact) {
                        if ($contact->device_phone) {
                            $existingByPhone[$contact->device_phone] = $contact;
                        }
                        if ($contact->device_lid) {
                            $existingByLid[$contact->device_lid] = $contact;
                        }
                    }

                    $toInsertNew = [];
                    $seenInBatch = []; // Track contacts we've already processed in this batch

                    foreach ($contactsToUpsert as $newData) {
                        $existing = null;

                        // Create a unique key for deduplication within the batch
                        $batchKey = $newData['device_phone'] ?? $newData['device_lid'];

                        // Skip if we already processed this contact in this batch
                        if (isset($seenInBatch[$batchKey])) {
                            continue;
                        }
                        $seenInBatch[$batchKey] = true;

                        // Find existing by device_phone first, then by device_lid
                        if (!empty($newData['device_phone']) && isset($existingByPhone[$newData['device_phone']])) {
                            $existing = $existingByPhone[$newData['device_phone']];
                        } elseif (!empty($newData['device_lid']) && isset($existingByLid[$newData['device_lid']])) {
                            $existing = $existingByLid[$newData['device_lid']];
                        }

                        if ($existing) {
                            // Update existing - merge non-null values
                            $updateData = ['updated_at' => now()];
                            if (!empty($newData['phone']) && empty($existing->phone)) {
                                $updateData['phone'] = $newData['phone'];
                            }
                            if (!empty($newData['lid']) && empty($existing->lid)) {
                                $updateData['lid'] = $newData['lid'];
                            }
                            if (!empty($newData['device_phone']) && empty($existing->device_phone)) {
                                $updateData['device_phone'] = $newData['device_phone'];
                            }
                            if (!empty($newData['device_lid']) && empty($existing->device_lid)) {
                                $updateData['device_lid'] = $newData['device_lid'];
                            }

                            if (count($updateData) > 1) {
                                $existing->update($updateData);
                            }
                        } else {
                            // New contact
                            $toInsertNew[] = $newData;
                        }
                    }

                    // Bulk insert new contacts
                    if (!empty($toInsertNew)) {
                        Contact::insert($toInsertNew);
                    }
                }

                // Step 6: Re-fetch all contacts to get IDs for chat mapping
                $allDevicePhones = [];
                $allDeviceLids = [];
                foreach ($messageDataArray as $data) {
                    if ($data['phone']) {
                        $allDevicePhones[] = $device->id . '_' . $data['phone'];
                    }
                    if ($data['lid']) {
                        $allDeviceLids[] = $device->id . '_' . $data['lid'];
                    }
                }

                $messageToContactMap = [];
                if (!empty($allDevicePhones) || !empty($allDeviceLids)) {
                    $contacts = Contact::where('user_id', $device->user_id)
                        ->where('device_id', $device->id)
                        ->where(function ($query) use ($allDevicePhones, $allDeviceLids) {
                            if (!empty($allDevicePhones)) {
                                $query->orWhereIn('device_phone', array_unique($allDevicePhones));
                            }
                            if (!empty($allDeviceLids)) {
                                $query->orWhereIn('device_lid', array_unique($allDeviceLids));
                            }
                        })
                        ->get();

                    // Build lookup maps
                    $contactsByDevicePhone = [];
                    $contactsByDeviceLid = [];
                    foreach ($contacts as $contact) {
                        if ($contact->device_phone) {
                            $contactsByDevicePhone[$contact->device_phone] = $contact;
                        }
                        if ($contact->device_lid) {
                            $contactsByDeviceLid[$contact->device_lid] = $contact;
                        }
                    }

                    // Map messages to contacts
                    foreach ($messageDataArray as $index => $data) {
                        $devicePhone = $data['phone'] ? $device->id . '_' . $data['phone'] : null;
                        $deviceLid = $data['lid'] ? $device->id . '_' . $data['lid'] : null;

                        $contact = null;
                        if ($devicePhone && isset($contactsByDevicePhone[$devicePhone])) {
                            $contact = $contactsByDevicePhone[$devicePhone];
                        } elseif ($deviceLid && isset($contactsByDeviceLid[$deviceLid])) {
                            $contact = $contactsByDeviceLid[$deviceLid];
                        }

                        if ($contact) {
                            $messageToContactMap[$index] = $contact;
                        }
                    }
                }

                // Step 7: Prepare chat data for bulk upsert
                $chatsData = [];
                foreach ($messageDataArray as $index => $data) {
                    $contactObj = $messageToContactMap[$index] ?? null;
                    if (!$contactObj || !isset($contactObj->id)) {
                        continue;
                    }

                    $unic = $data['key']['id'] ?? '';
                    $phone = $contactObj->phone ?? $data['phone'];

                    // Map status to number
                    $status = null;
                    if ($data['key']['fromMe']) {
                        $status = $this->mapStatusToNumber($data['status'] ?? null);
                    } else {
                        $status = 3; // DELIVERY_ACK for incoming messages
                    }

                    $chatsData[] = [
                        'device_unic_id' => $device->id . "_" . $unic,
                        'unic_id' => $unic,
                        'user_id' => $device->user_id,
                        'device_id' => $device->id,
                        'contact_id' => $contactObj->id,
                        'phone' => $phone,
                        'message' => $data['message'],
                        'fromMe' => $data['key']['fromMe'] ? 'true' : 'false',
                        'status' => $status,
                        'timestamp' => $data['timestamp'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Step 8: Bulk upsert all chats in ONE query
                if (!empty($chatsData)) {
                    Chats::upsert(
                        $chatsData,
                        ['device_unic_id'],
                        ['contact_id', 'phone', 'message', 'fromMe', 'status', 'updated_at']
                    );
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Batch webhook error', [
                'device_id' => $device_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        $device->update(['sync' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Batch processed successfully',
        ]);
    }
}
