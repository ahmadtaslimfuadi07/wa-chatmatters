<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\User;
use App\Models\Device;
use App\Models\Chats;
use Http;
use Auth;
use Str;
use DB;
use Session;
class ContactController extends Controller
{
    /**
     * sent message
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request){
        if($request->user_id==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param user_id is required',
          ],400);
        }

        if($request->device_id==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param device_id is required',
          ],400);
        }

        if($request->phone==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param phone is required',
          ],400);
        }

        $Contact_ = new Contact();
        if($request->user_id!=null){
            $User_ = User::where('id',$request->user_id)->first();
            if($User_==null){
                return response()->json([
                    'code'  => 400,
                    'message'  => 'user_id not found',
                ],400);
            }
            $Contact_->user_id = $request->user_id;
        }
        if($request->device_id!=null){
            $Device_ = Device::where('id',$request->device_id)->first();
            if($Device_==null){
                return response()->json([
                    'code'  => 400,
                    'message'  => 'device_id not found',
                ],400);
            }
            $Contact_->device_id = $request->device_id;
        }
        if($request->name!=null){
            $Contact_->name = $request->name;
        }
        if($request->lid!=null){
            $Contact_->lid = $request->lid;
        }
        $Contact_->phone = $request->phone;
        $Contact_->created_at=date('Y-m-d H:i:s');
        $Contact_->updated_at=date('Y-m-d H:i:s');
        $Contact_->save();
        $Contact_->id;

        return response()->json([
            'code'  => 202,
            'data'  => $Contact_,
            'message'  => 'Succes',
        ],202);
    }

    /**
     * sent message
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request){
        if($request->id==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param id is required',
          ],400);
        }

        try{
            $Contact_=  Contact::where('id',$request->id)->first();
            if($Contact_!=null){
                if($request->user_id!=null){
                    $User_ = User::where('id',$request->user_id)->first();
                    if($User_==null){
                        return response()->json([
                            'code'  => 400,
                            'message'  => 'user_id not found',
                        ],400);
                    }
                    $Contact_->user_id = $request->user_id;
                }
                if($request->device_id!=null){
                    $Device_ = Device::where('id',$request->device_id)->first();
                    if($Device_==null){
                        return response()->json([
                            'code'  => 400,
                            'message'  => 'device_id not found',
                        ],400);
                    }
                    $Contact_->device_id = $request->device_id;
                }
                if($request->name!=null){
                    $Contact_->name = $request->name;
                }
                if($request->lid!=null){
                    $Contact_->lid = $request->lid;
                }

                $Contact_->id = $request->id;
                $Contact_->phone = $request->phone;
                $Contact_->created_at=date('Y-m-d H:i:s');
                $Contact_->updated_at=date('Y-m-d H:i:s');
                $Contact_->save();

                return response()->json([
                    'code'  => 202,
                    'data'  => $Contact_,
                    'message'  => 'Succes',
                ],202);
            }else{
                return response()->json([
                    'code'  => 400,
                    'message'  => 'id not found',
                ],400);
            }
        }catch(Exception $e) {
            return response()->json([
                'code'  => 400,
                'message'  => 'param user_id or device_id not found',
            ],400);
        }
    }

    /**
     * webhook for contact updates from WhatsApp
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $device_id
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request, $device_id)
    {
        // Extract numeric ID from device_id (e.g., "device_1393" -> "1393")
        $numericDeviceId = $device_id;
        if (strpos($device_id, 'device_') === 0) {
            $numericDeviceId = str_replace('device_', '', $device_id);
        }

        // Validate device exists
        $device = Device::where('id', $numericDeviceId)->first();
        if ($device == null) {
            return response()->json([
                'code' => 400,
                'message' => 'device_id not found',
            ], 400);
        }

        // \Log::info('Contact webhook received original', [
        //     'device_id' => $device_id,
        //     'type' => $request->type,
        //     'contacts' => $request->contacts,
        // ]);

        if ($request->type === 'upsert' && is_array($request->contacts)) {
            // \Log::info('Contact webhook received', [
            //     'device_id' => $device_id,
            //     'type' => $request->type,
            //     'contacts_count' => count($request->contacts),
            // ]);

            $processedCount = 0;

            foreach ($request->contacts as $contactData) {
                // Extract phone from jid (e.g., "6282261517492@s.whatsapp.net" -> "6282261517492")
                $phone = null;
                if (isset($contactData['jid'])) {
                    $phone = explode('@', $contactData['jid'])[0];
                }

                // Extract lid phone from lid (e.g., "262779068010559@lid" -> "262779068010559")
                $lidPhone = null;
                if (isset($contactData['lid'])) {
                    $lidPhone = explode('@', $contactData['lid'])[0];
                }

                // Skip contacts without phone or lid (invalid data)
                if (!$phone && !$lidPhone) {
                    // \Log::warning('Contact webhook - skipped invalid contact', [
                    //     'device_id' => $device_id,
                    //     'contact' => $contactData,
                    //     'reason' => 'No phone or lid provided',
                    // ]);
                    continue;
                }

                // Prepare data for upsert
                $dataToUpsert = [
                    'device_id' => $numericDeviceId,
                    'user_id' => $device->user_id,
                    'name' => $contactData['name'] ?? null,
                ];

                // Create composite device_phone and device_lid (e.g., "123_6282261517492")
                if ($phone) {
                    $dataToUpsert['device_phone'] = $numericDeviceId . '_' . $phone;
                    $dataToUpsert['phone'] = $phone; // Store just the number
                }
                if ($lidPhone) {
                    $dataToUpsert['device_lid'] = $numericDeviceId . '_' . $lidPhone;
                    $dataToUpsert['lid'] = $lidPhone; // Store just the number
                }

                // Perform upsert based on unique constraints
                try {
                    // Find ALL related contacts in optimized queries
                    $query = Contact::where('device_id', $numericDeviceId);

                    // Build WHERE conditions to find all matching contacts
                    $query->where(function($q) use ($phone, $lidPhone, $numericDeviceId) {
                        if ($phone) {
                            $compositeDevicePhone = $numericDeviceId . '_' . $phone;
                            $q->orWhere('device_phone', $compositeDevicePhone)
                              ->orWhere('phone', $phone);
                        }

                        if ($lidPhone) {
                            $compositeDeviceLid = $numericDeviceId . '_' . $lidPhone;
                            $q->orWhere('device_lid', $compositeDeviceLid)
                              ->orWhere('lid', $lidPhone);
                        }
                    });

                    $allRelatedContacts = $query->get();

                    if ($allRelatedContacts->isNotEmpty()) {
                        // Use DB transaction for data consistency
                        DB::beginTransaction();

                        try {
                            // Sort by ID descending to get newest first
                            $sortedContacts = $allRelatedContacts->sortByDesc('id')->values();

                            // Keep the newest contact
                            $contactToKeep = $sortedContacts->first();
                            $contactsToDelete = $sortedContacts->slice(1);

                            if ($contactsToDelete->isNotEmpty()) {
                                // Collect all old contact IDs
                                $oldContactIds = $contactsToDelete->pluck('id')->toArray();

                                // Merge data from older contacts into the newest one
                                foreach ($contactsToDelete as $oldContact) {
                                    // Fill in missing fields (check for null, not empty)
                                    if ($contactToKeep->device_phone === null && $oldContact->device_phone !== null) {
                                        $contactToKeep->device_phone = $oldContact->device_phone;
                                    }
                                    if ($contactToKeep->phone === null && $oldContact->phone !== null) {
                                        $contactToKeep->phone = $oldContact->phone;
                                    }
                                    if ($contactToKeep->device_lid === null && $oldContact->device_lid !== null) {
                                        $contactToKeep->device_lid = $oldContact->device_lid;
                                    }
                                    if ($contactToKeep->lid === null && $oldContact->lid !== null) {
                                        $contactToKeep->lid = $oldContact->lid;
                                    }
                                    if ($contactToKeep->name === null && $oldContact->name !== null) {
                                        $contactToKeep->name = $oldContact->name;
                                    }
                                }

                                // Batch update: Update all chats at once
                                Chats::whereIn('contact_id', $oldContactIds)
                                    ->update(['contact_id' => $contactToKeep->id]);

                                // Batch delete: Delete all old contacts at once
                                Contact::whereIn('id', $oldContactIds)->delete();

                                // \Log::info('Contact webhook - merged duplicates', [
                                //     'device_id' => $device_id,
                                //     'kept_contact_id' => $contactToKeep->id,
                                //     'deleted_contact_ids' => $oldContactIds,
                                //     'merged_count' => count($oldContactIds),
                                // ]);
                            }

                            // Update the kept contact with new data
                            foreach ($dataToUpsert as $key => $value) {
                                if ($value !== null) {
                                    $contactToKeep->$key = $value;
                                }
                            }
                            $contactToKeep->save();

                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }
                    } else {
                        // Create new contact
                        Contact::create($dataToUpsert);
                    }

                    $processedCount++;
                } catch (\Exception $e) {
                    // \Log::error('Contact webhook - upsert failed', [
                    //     'device_id' => $device_id,
                    //     'contact' => $contactData,
                    //     'error' => $e->getMessage(),
                    // ]);
                }
            }

            // \Log::info('Contact webhook completed', [
            //     'device_id' => $device_id,
            //     'total_contacts' => count($request->contacts),
            //     'processed_count' => $processedCount,
            //     'failed_count' => count($request->contacts) - $processedCount,
            // ]);

            return response()->json([
                'code' => 200,
                'message' => 'Contacts upserted successfully',
                'processed_count' => $processedCount,
            ], 200);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Webhook received successfully',
        ], 200);
    }
}
