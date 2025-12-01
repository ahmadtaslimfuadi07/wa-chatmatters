<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\User;
use App\Models\Device;
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
        \Log::info('Contact webhook received', [
            'device_id' => $device_id,
            'type' => $request->type,
            'contacts' => $request->contacts,
            'full_request' => $request->all()
        ]);

        return response()->json([
            'code' => 200,
            'message' => 'Webhook received successfully',
        ], 200);
    }
}
