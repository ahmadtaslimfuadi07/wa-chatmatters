<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Contact;
use App\Models\Chats;
use Cache;
use Auth;
use App\Traits\Whatsapp;

class ChatCalling extends Command
{
    use Whatsapp;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:get {deviceID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description get-chat';

    /**
     * Execute the console command.
     */


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info("Start server");
        $device_id = $this->argument('deviceID');
        $device_ = Device::where("id",  $device_id)->where("status", 1)->latest()->get();

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
                            $is_exist = Contact::updateOrCreate(
                                [
                                    'user_id'       => $device->user_id,
                                    'phone'         => $res_data[$i]['number'],
                                    'device_id'     => $device->id,
                                ],
                                [
                                    'user_id'       => $device->user_id,
                                    'phone'         => $res_data[$i]['number'],
                                    'device_id'     => $device->id,
                                    'created_at'    => date('Y-m-d H:i:s'),
                                    'updated_at'    => date('Y-m-d H:i:s'),
                                ]
                            );
                            $contact_id= $is_exist->id;

                            $response_chat = $this->getChatDetails($device->id, $res_data[$i]['number'], 1000, '');
                            if ($response_chat["status"] == 200) {
                                $res_data_chat= $response_chat["data"];
                                if(count($res_data_chat)>0){
                                    for($f=0;$f<count($res_data_chat);$f++){
                                        if($res_data_chat[$f]!=null){
                                            Chats::updateOrCreate(
                                                [
                                                    'user_id'       => $device->user_id,
                                                    'contact_id'    => $contact_id,
                                                    'device_id'     => $device->id,
                                                    'unic_id'       => $res_data_chat[$f]['id'],
                                                ],
                                                [
                                                    'user_id'       => $device->user_id,
                                                    'contact_id'    => $contact_id,
                                                    'device_id'     => $device->id,
                                                    'phone'         => (isset($res_data[$i]['number']))?$res_data[$i]['number']:"",
                                                    'unic_id'       => (isset($res_data_chat[$f]['id']))?$res_data_chat[$f]['id']:"",
                                                    'message'       => (isset($res_data_chat[$f]['message']))?$res_data_chat[$f]['message']:"",
                                                    'fromMe'        => (isset($res_data_chat[$f]['fromMe']))?($res_data_chat[$f]['fromMe']!=null)?$res_data_chat[$f]['fromMe']:"false":"false",
                                                    'timestamp'     => (isset($res_data_chat[$f]['timestamp']))?$res_data_chat[$f]['timestamp']:"",
                                                    'created_at'    => date('Y-m-d H:i:s'),
                                                    'updated_at'    => date('Y-m-d H:i:s'),
                                                ]
                                            );
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
