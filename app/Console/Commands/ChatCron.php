<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Contact;
use App\Models\Chats;
use Cache;
use Auth;
use App\Traits\Whatsapp;

class ChatCron extends Command
{
    use Whatsapp;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:get-chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description get-chat';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $device_ = Device::where("status", 1)->latest()->get();

        info($device_);
        info(count($device_));

        if(count($device_)>0){
            for($i=0;$i<count($device_);$i++){
                $device = $device_[$i];
                $response = Cache::remember(
                    "groups_" . $device->uuid,120,
                    function () use ($device) {
                        return $this->getChats($device->id);
                    }
                );

                if ($response["status"] == 200) {
                    $res_data = $response["data"];
                    if(count($response["data"])>0){
                        for($i=0;$i<count($res_data);$i++){
                            $contact_id= null;
                            $is_exist=Contact::where('user_id',$device->user_id)->where('phone',$res_data[$i]['number'])->where('device_id',$device->id)->first();
                            if (empty($is_exist)) {
                                $contact = new Contact;
                                $contact->user_id = $device->user_id;
                                $contact->phone = $res_data[$i]['number'];
                                $contact->device_id = $device->id;
                                $contact->created_at = date('Y-m-d H:i:s');
                                $contact->updated_at = date('Y-m-d H:i:s');
                                $contact->save();
                                $contact_id = $contact->id;
                            }else{
                                $contact_id = $is_exist->id;
                            }

                            $response_chat = $this->getChatDetails($device->id, $res_data[$i]['number'], 1000, '');
                            if ($response_chat["status"] == 200) {
                                $res_data_chat= $response_chat["data"];
                                if(count($res_data_chat)>0){
                                    for($f=0;$f<count($res_data_chat);$f++){
                                        if($res_data_chat[$f]!=null){
                                            $is_exist_chat=Chats::where('user_id',$device->user_id)->where('device_id',$device->id)->where('contact_id',$contact_id)->where('unic_id', $res_data_chat[$f]['id'])->first();
                                            if (empty($is_exist_chat)) {
                                                $chats = new Chats;
                                                $chats->user_id = $device->user_id;
                                                $chats->contact_id = $contact_id;
                                                $chats->device_id = $device->id;
                                                $chats->phone = (isset($res_data[$i]['number']))?$res_data[$i]['number']:"";
                                                $chats->unic_id = (isset($res_data_chat[$f]['id']))?$res_data_chat[$f]['id']:"";
                                                $chats->message = (isset($res_data_chat[$f]['message']))?$res_data_chat[$f]['message']:"";
                                                $chats->fromMe = (isset($res_data_chat[$f]['fromMe']))?($res_data_chat[$f]['fromMe']!=null)?$res_data_chat[$f]['fromMe']:"false":"false";
                                                $chats->timestamp = (isset($res_data_chat[$f]['timestamp']))?$res_data_chat[$f]['timestamp']:"";
                                                $chats->created_at = date('Y-m-d H:i:s');
                                                $chats->updated_at = date('Y-m-d H:i:s');
                                                $chats->save();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }
        }
    }
}
