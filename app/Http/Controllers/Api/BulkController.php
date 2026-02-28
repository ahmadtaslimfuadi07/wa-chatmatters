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
     * Merge two contacts - keep the one with higher ID (more recent)
     * and merge data from the other contact
     *
     * @param Contact $contact1
     * @param Contact $contact2
     * @param Device $device
     * @param string $reason The reason/trigger for the merge (e.g., 'phone', 'lid', 'device_phone', 'device_lid')
     * @return Contact The merged contact that was kept
     */
    private function mergeContacts($contact1, $contact2, $device, $reason = 'unknown')
    {
        // Determine which contact is more recent (higher ID = created later)
        $keepContact = $contact1->id > $contact2->id ? $contact1 : $contact2;
        $mergeContact = $contact1->id > $contact2->id ? $contact2 : $contact1;

        info('Merging contacts', [
            'merge_reason' => $reason,
            'keep_contact_id' => $keepContact->id,
            'merge_contact_id' => $mergeContact->id,
            'keep_phone' => $keepContact->phone,
            'merge_phone' => $mergeContact->phone,
            'keep_lid' => $keepContact->lid,
            'merge_lid' => $mergeContact->lid,
        ]);

        // Merge data: fill empty fields in keepContact with data from mergeContact
        if (empty($keepContact->phone) && !empty($mergeContact->phone)) {
            $keepContact->phone = $mergeContact->phone;
        }
        if (empty($keepContact->device_phone) && !empty($mergeContact->device_phone)) {
            $keepContact->device_phone = $mergeContact->device_phone;
        } elseif (empty($keepContact->device_phone) && !empty($keepContact->phone)) {
            $keepContact->device_phone = $device->id . '_' . $keepContact->phone;
        }

        if (empty($keepContact->lid) && !empty($mergeContact->lid)) {
            $keepContact->lid = $mergeContact->lid;
        }
        if (empty($keepContact->device_lid) && !empty($mergeContact->device_lid)) {
            $keepContact->device_lid = $mergeContact->device_lid;
        } elseif (empty($keepContact->device_lid) && !empty($keepContact->lid)) {
            $keepContact->device_lid = $device->id . '_' . $keepContact->lid;
        }

        if (empty($keepContact->name) && !empty($mergeContact->name)) {
            $keepContact->name = $mergeContact->name;
        }

        try {
            // Update all chats pointing to the merge contact to point to the keep contact
            Chats::where('contact_id', $mergeContact->id)->update(['contact_id' => $keepContact->id]);

            // Delete the merged contact
            $mergeContact->delete();

            // Save the kept contact
            $keepContact->save();

            info('Contact merge successful', [
                'merge_reason' => $reason,
                'contact_id' => $keepContact->id,
                'phone' => $keepContact->phone,
                'device_phone' => $keepContact->device_phone,
                'lid' => $keepContact->lid,
                'device_lid' => $keepContact->device_lid,
            ]);
        } catch (\Exception $e) {
            info('Contact merge failed', [
                'merge_reason' => $reason,
                'error' => $e->getMessage(),
                'keep_contact_id' => $keepContact->id ?? null,
                'merge_contact_id' => $mergeContact->id ?? null,
                'exception' => $e
            ]);
            // Return the original contact if merge fails
            // This ensures we don't lose the contact reference
        }

        return $keepContact;
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
            $device->sync=0;
            $device->sync_progress=0;
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
        $message_timestamp = $request->other['messageTimestamp'] ?? $current_timestamp;
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
            // Order by: 1) contacts with both phone AND lid (most complete)
            //           2) most recently updated
            //           3) highest ID (newest)
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
                ->orderByRaw('(phone IS NOT NULL AND lid IS NOT NULL) DESC')
                ->orderBy('updated_at', 'desc')
                ->orderBy('id', 'desc')
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
                // Check if we need to merge with another contact due to unique constraints
                $needsMerge = false;
                $otherContact = null;

                // If trying to add phone, check if another contact already has this phone
                if (!empty($request_from) && empty($contact->phone)) {
                    $otherContact = Contact::where('user_id', $device->user_id)
                        ->where('device_id', $device->id)
                        ->where('phone', $request_from)
                        ->where('id', '!=', $contact->id)
                        ->first();
                    $needsMerge = !empty($otherContact);
                }

                // If trying to add lid, check if another contact already has this lid
                if (!empty($lid) && empty($contact->lid) && !$needsMerge) {
                    $otherContact = Contact::where('user_id', $device->user_id)
                        ->where('device_id', $device->id)
                        ->where('lid', $lid)
                        ->where('id', '!=', $contact->id)
                        ->first();
                    $needsMerge = !empty($otherContact);
                }

                if ($needsMerge && $otherContact) {
                    // MERGE: Combine both contacts into one using helper method
                    $contact = $this->mergeContacts($contact, $otherContact, $device, 'phone_or_lid_duplicate');
                    $is_exist = $contact;
                } else {
                    // Normal update without merge
                    $contactUpdated = false;

                    // Update phone if needed
                    if (!empty($request_from) && empty($contact->phone)) {
                        $contact->phone = $request_from;
                        $contact->device_phone = $device->id . '_' . $request_from;
                        $contactUpdated = true;
                    } elseif (!empty($contact->phone) && empty($contact->device_phone)) {
                        // Fix: phone exists but device_phone is null
                        // Check if another contact already has this device_phone
                        $expectedDevicePhone = $device->id . '_' . $contact->phone;
                        $duplicateByDevicePhone = Contact::where('user_id', $device->user_id)
                            ->where('device_id', $device->id)
                            ->where('device_phone', $expectedDevicePhone)
                            ->where('id', '!=', $contact->id)
                            ->first();

                        if ($duplicateByDevicePhone) {
                            // Merge using helper method
                            $contact = $this->mergeContacts($contact, $duplicateByDevicePhone, $device, 'device_phone_duplicate');
                            $is_exist = $contact;
                        } else {
                            $contact->device_phone = $expectedDevicePhone;
                            $contactUpdated = true;
                        }
                    }

                    // Update lid if needed
                    if (!empty($lid) && empty($contact->lid)) {
                        $contact->lid = $lid;
                        $contact->device_lid = $device->id . '_' . $lid;
                        $contactUpdated = true;
                    } elseif (!empty($contact->lid) && empty($contact->device_lid)) {
                        // Fix: lid exists but device_lid is null
                        // Check if another contact already has this device_lid
                        $expectedDeviceLid = $device->id . '_' . $contact->lid;
                        $duplicateByDeviceLid = Contact::where('user_id', $device->user_id)
                            ->where('device_id', $device->id)
                            ->where('device_lid', $expectedDeviceLid)
                            ->where('id', '!=', $contact->id)
                            ->first();

                        if ($duplicateByDeviceLid) {
                            // Merge using helper method
                            $contact = $this->mergeContacts($contact, $duplicateByDeviceLid, $device, 'device_lid_duplicate');
                            $is_exist = $contact;
                        } else {
                            $contact->device_lid = $expectedDeviceLid;
                            $contactUpdated = true;
                        }
                    }

                    // Only save if something changed
                    if ($contactUpdated) {
                        try {
                            $contact->save();
                        } catch (\Exception $e) {
                            // Log the error but continue with existing contact data
                            info('Contact save failed', [
                                'error' => $e->getMessage(),
                                'contact_id' => $contact->id ?? null,
                                'phone' => $request_from ?? null,
                                'lid' => $lid ?? null,
                                'device_id' => $device->id ?? null,
                                'request' => $request->all(),
                                'exception' => $e
                            ]);
                        }
                    }

                    $is_exist = $contact;
                }
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
                    'timestamp' => $message_timestamp,
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
            info('Contact/Chat insert failed', [
                'error' => $e->getMessage(),
                'device_id' => $device->id ?? null,
                'message_id' => $request->messageId ?? null,
                'from' => $request_from ?? null,
                'contact_id' => $contact_id ?? null,
                'request' => $request->all(),
                'exception' => $e
            ]);
        }

        //START WEBHOOK SEND
        try {
            $webhook = Webhooks::where('device_id', $device_id)->where('status', 1)->get();
            $body = [
                'messageId' => $message_id,
                'fromMe' => $request->fromMe,
                'from' => $request_from ?? ($contact->phone ?? null),
                'lid' => $lid ?? ($contact->lid ?? null),
                'to' => $device->phone,
                'timestamp' => $message_timestamp,
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
                $webhooklogs->original_request = json_encode($request->all(), true);
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
            $replies = Reply::where('device_id', $device_id)
                ->with('template')
                ->where(function ($query) use ($message) {
                    // equal: keyword harus sama persis dengan pesan masuk
                    $query->where(function ($q) use ($message) {
                        $q->where('match_type', 'equal')->where('keyword', $message);
                    })
                    // like: pesan masuk mengandung keyword
                    ->orWhere(function ($q) use ($message) {
                        $q->where('match_type', 'like')->whereRaw("? LIKE CONCAT('%', keyword, '%')", [$message]);
                    });
                })
                ->latest()
                ->get();
            foreach ($replies as $key => $reply) {
                // if ($reply->match_type == 'equal') {

                if ($reply->reply_type == 'text') {

                    $logs['user_id'] = $device->user_id;
                    $logs['device_id'] = $device->id;
                    $logs['from'] = $device->phone ?? null;
                    $logs['to'] = $request_from;
                    $logs['type'] = 'chatbot';
                    $this->saveLog($logs);
                    $data['message'] = $reply->reply;
                    $response= $this->messageSend($data,$device->id,$request_from,'plain-text');

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
                        $response= $this->messageSend($body,$device->id,$request_from,'text-with-template',true);

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

    // OLD IMPLEMENTATION - COMMENTED OUT FOR REFERENCE
    // public function batchWebHook(Request $request, $device_id)
    // {
    //     ... old code ...
    // }

    /**
     * New batch webhook implementation
     * Handles WhatsApp history sync with improved logic based on processHistoryMessage analysis
     *
     * Processing order:
     * 1. Extract phone ↔ LID mappings from chats array
     * 2. Process contacts using mappings to store both phone AND lid when available
     * 3. Process messages and link to contacts
     *
     * Supports sync types:
     * - SyncType 0 (INITIAL_BOOTSTRAP): Initial contacts and messages
     * - SyncType 3 (FULL/ON_DEMAND): Progressive sync with progress tracking
     * - SyncType 4 (PUSH_NAME): Contact name updates
     *
     * @param Request $request
     * @param string $device_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchWebHook(Request $request, $device_id)
    {
        // ============================================
        // 1. INITIALIZATION & VALIDATION
        // ============================================
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
            // ============================================
            // 2. BUILD PHONE ↔ LID MAPPING FROM CHATS
            // ============================================
            // Extract mappings from chats array to enrich contact data
            // This provides the phone ↔ LID relationship

            $phoneToLidMap = [];
            $lidToPhoneMap = [];

            if (!empty($request->chats)) {
                foreach ($request->chats as $chat) {
                    $chatId = $chat['id'] ?? '';
                    $pnJid = $chat['pnJid'] ?? null;
                    $accountLid = $chat['accountLid'] ?? null;

                    // Skip group chats
                    if (str_contains($chatId, '@g.us')) {
                        continue;
                    }

                    // Skip system contact
                    if ($chatId === '0@s.whatsapp.net') {
                        continue;
                    }

                    // Extract phone → LID mapping
                    if (str_contains($chatId, '@s.whatsapp.net') && $accountLid) {
                        $phone = strtok($chatId, '@');
                        $lid = strtok($accountLid, '@');

                        if ($phone && $lid) {
                            $phoneToLidMap[$phone] = $lid;
                        }
                    }

                    // Extract LID → phone mapping
                    if (str_contains($chatId, '@lid') && $pnJid) {
                        $lid = strtok($chatId, '@');
                        $phone = strtok($pnJid, '@');

                        if ($phone && $lid) {
                            $lidToPhoneMap[$lid] = $phone;
                        }
                    }
                }
            }

            // ============================================
            // 3. PROCESS CONTACTS
            // ============================================
            // Contacts can exist independently (e.g., SyncType 4 PUSH_NAME)
            // Use mappings from chats to enrich contact data with phone ↔ LID

            if (!empty($request->contacts)) {
                $contactsToInsert = [];
                $allDevicePhones = [];
                $allDeviceLids = [];
                $contactDataMap = [];

                // Build contact data from request
                foreach ($request->contacts as $contact) {
                    $phone = null;
                    $lid = null;
                    $contactId = $contact['id'] ?? '';

                    // Skip group chats
                    if (str_contains($contactId, '@g.us')) {
                        continue;
                    }

                    // Parse contact ID to extract phone/lid
                    if (str_contains($contactId, '@s.whatsapp.net')) {
                        $phone = strtok($contactId, '@');
                        // Use mapping from chats to get LID
                        $lid = $phoneToLidMap[$phone] ?? null;
                    } elseif (str_contains($contactId, '@lid')) {
                        $lid = strtok($contactId, '@');
                        // Use mapping from chats to get phone
                        $phone = $lidToPhoneMap[$lid] ?? null;
                    } else {
                        continue; // Skip unknown format
                    }

                    // Build device composite keys
                    $devicePhone = $phone ? $device->id . "_" . $phone : null;
                    $deviceLid = $lid ? $device->id . "_" . $lid : null;

                    if ($devicePhone) {
                        $allDevicePhones[] = $devicePhone;
                    }
                    if ($deviceLid) {
                        $allDeviceLids[] = $deviceLid;
                    }

                    $contactDataMap[] = [
                        'phone' => $phone,
                        'lid' => $lid,
                        'devicePhone' => $devicePhone,
                        'deviceLid' => $deviceLid,
                        'name' => $contact['name'] ?? null,
                        'notify' => $contact['notify'] ?? null, // From SyncType 4
                    ];
                }

                // Bulk fetch existing contacts
                if (!empty($allDevicePhones) || !empty($allDeviceLids)) {
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

                    // Create lookup maps
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

                    // Process contacts: update existing or prepare for insert
                    $seenInBatch = [];
                    foreach ($contactDataMap as $contactData) {
                        $batchKey = $contactData['devicePhone'] ?? $contactData['deviceLid'];

                        if (!$batchKey || isset($seenInBatch[$batchKey])) {
                            continue; // Skip duplicates
                        }
                        $seenInBatch[$batchKey] = true;

                        // Find existing contact
                        $existing = null;
                        if ($contactData['devicePhone'] && isset($existingByDevicePhone[$contactData['devicePhone']])) {
                            $existing = $existingByDevicePhone[$contactData['devicePhone']];
                        } elseif ($contactData['deviceLid'] && isset($existingByDeviceLid[$contactData['deviceLid']])) {
                            $existing = $existingByDeviceLid[$contactData['deviceLid']];
                        }

                        if ($existing) {
                            // Update existing - only fill empty fields
                            $updateData = ['updated_at' => now()];

                            if (!empty($contactData['phone']) && empty($existing->phone)) {
                                $updateData['phone'] = $contactData['phone'];
                            }
                            if (!empty($contactData['lid']) && empty($existing->lid)) {
                                $updateData['lid'] = $contactData['lid'];
                            }
                            if (!empty($contactData['devicePhone']) && empty($existing->device_phone)) {
                                $updateData['device_phone'] = $contactData['devicePhone'];
                            }
                            if (!empty($contactData['deviceLid']) && empty($existing->device_lid)) {
                                $updateData['device_lid'] = $contactData['deviceLid'];
                            }
                            if (!empty($contactData['name']) && empty($existing->name)) {
                                $updateData['name'] = $contactData['name'];
                            }
                            if (!empty($contactData['notify']) && empty($existing->name)) {
                                // notify field updates name (from PUSH_NAME sync)
                                $updateData['name'] = $contactData['notify'];
                            }

                            if (count($updateData) > 1) {
                                $existing->update($updateData);
                            }
                        } else {
                            // New contact - prepare for bulk insert
                            $contactsToInsert[] = [
                                'user_id' => $device->user_id,
                                'device_id' => $device->id,
                                'phone' => $contactData['phone'],
                                'lid' => $contactData['lid'],
                                'device_phone' => $contactData['devicePhone'],
                                'device_lid' => $contactData['deviceLid'],
                                'name' => $contactData['name'] ?? $contactData['notify'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    // Bulk insert new contacts
                    if (!empty($contactsToInsert)) {
                        Contact::insert($contactsToInsert);
                    }
                }
            }

            // ============================================
            // 4. PROCESS MESSAGES (stored in chats table)
            // ============================================
            // Messages need contact_id FK, so contacts must be processed first

            if (!empty($request->messages)) {
                $messagesToUpsert = [];
                $allDevicePhones = [];
                $allDeviceLids = [];
                $messageDataMap = [];

                // Extract message data
                foreach ($request->messages as $msg) {
                    $key = $msg['key'] ?? [];
                    $remoteJid = $key['remoteJid'] ?? '';
                    $messageId = $key['id'] ?? '';
                    $fromMe = $key['fromMe'] ?? false;

                    // Skip group chat messages
                    if (str_contains($remoteJid, '@g.us')) {
                        continue;
                    }

                    // Parse remoteJid to get phone or lid
                    $phone = null;
                    $lid = null;

                    if (str_contains($remoteJid, '@s.whatsapp.net')) {
                        $phone = strtok($remoteJid, '@');
                    } elseif (str_contains($remoteJid, '@lid')) {
                        $lid = strtok($remoteJid, '@');
                    } else {
                        continue;
                    }

                    // Validate phone/lid
                    if ($phone !== null && (empty($phone) || $phone === '0' || !is_numeric($phone) || strlen($phone) < 4)) {
                        $phone = null;
                    }
                    if ($lid !== null && (empty($lid) || strlen($lid) < 3)) {
                        $lid = null;
                    }

                    if ($phone === null && $lid === null) {
                        continue; // Skip invalid messages
                    }

                    // Build device composite keys
                    $devicePhone = $phone ? $device->id . '_' . $phone : null;
                    $deviceLid = $lid ? $device->id . '_' . $lid : null;

                    if ($devicePhone) {
                        $allDevicePhones[] = $devicePhone;
                    }
                    if ($deviceLid) {
                        $allDeviceLids[] = $deviceLid;
                    }

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

                    // Map status
                    $status = $fromMe ? $this->mapStatusToNumber($msg['status'] ?? null) : 3;

                    $messageDataMap[] = [
                        'messageId' => $messageId,
                        'phone' => $phone,
                        'lid' => $lid,
                        'devicePhone' => $devicePhone,
                        'deviceLid' => $deviceLid,
                        'text' => $text,
                        'fromMe' => $fromMe,
                        'status' => $status,
                        'timestamp' => $msg['messageTimestamp'] ?? now()->timestamp,
                    ];
                }

                // Bulk fetch contacts for all messages
                if (!empty($allDevicePhones) || !empty($allDeviceLids)) {
                    $contactsForMessages = Contact::where('user_id', $device->user_id)
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

                    // Create contact lookup maps
                    $contactByDevicePhone = [];
                    $contactByDeviceLid = [];
                    foreach ($contactsForMessages as $contact) {
                        if ($contact->device_phone) {
                            $contactByDevicePhone[$contact->device_phone] = $contact;
                        }
                        if ($contact->device_lid) {
                            $contactByDeviceLid[$contact->device_lid] = $contact;
                        }
                    }

                    // Map messages to contacts and prepare for upsert
                    $seenMessages = [];
                    foreach ($messageDataMap as $msgData) {
                        $deviceUnicId = $device->id . "_" . $msgData['messageId'];

                        if (isset($seenMessages[$deviceUnicId])) {
                            continue;
                        }
                        $seenMessages[$deviceUnicId] = true;

                        // Find contact
                        $contact = null;
                        if ($msgData['devicePhone'] && isset($contactByDevicePhone[$msgData['devicePhone']])) {
                            $contact = $contactByDevicePhone[$msgData['devicePhone']];
                        } elseif ($msgData['deviceLid'] && isset($contactByDeviceLid[$msgData['deviceLid']])) {
                            $contact = $contactByDeviceLid[$msgData['deviceLid']];
                        }

                        if (!$contact) {
                            continue; // Skip messages without contacts
                        }

                        $messagesToUpsert[] = [
                            'device_unic_id' => $deviceUnicId,
                            'unic_id' => $msgData['messageId'],
                            'user_id' => $device->user_id,
                            'device_id' => $device->id,
                            'contact_id' => $contact->id,
                            'phone' => $contact->phone ?? $msgData['phone'],
                            'message' => $msgData['text'],
                            'fromMe' => $msgData['fromMe'] ? 'true' : 'false',
                            'status' => $msgData['status'],
                            'timestamp' => $msgData['timestamp'],
                            'file' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Bulk upsert messages
                    if (!empty($messagesToUpsert)) {
                        Chats::upsert(
                            $messagesToUpsert,
                            ['device_unic_id'],
                            ['contact_id', 'phone', 'message', 'fromMe', 'status', 'updated_at']
                        );
                    }
                }
            }

            // ============================================
            // 5. TRACK SYNC PROGRESS (for SyncType 3)
            // ============================================
            if ($request->syncType == 3 && isset($request->progress)) {
                // Store progress for progressive sync
                $device->update(['sync_progress' => $request->progress]);
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
        } finally {
            $device->update(['sync' => 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Batch processed successfully',
        ]);
    }
}
