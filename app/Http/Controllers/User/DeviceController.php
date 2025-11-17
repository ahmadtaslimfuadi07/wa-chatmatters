<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Reply;
use App\Models\Smstransaction;
use App\Models\Template;
use App\Models\User;
use App\Models\Downline;
use App\Models\Webhooks;
use App\Models\App;
use DB;
use Auth;
use Http;
use Session;
use Str;
use Carbon\Carbon;
use App\Traits\Whatsapp;
class DeviceController extends Controller
{
    use Whatsapp;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Device::where('user_id', Auth::id())->withCount('smstransaction');
        // Apply filters
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('phone') && !empty($request->phone)) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }
        if ($request->has('status') && isset($request->status)) {
            $query->where('status', $request->status);
        }
        if ($request->has('remark') && !empty($request->remark)) {
            $query->where('remark', 'like', '%' . $request->remark . '%');
        }

        // Apply ordering
        $query->orderBy('status', 'desc')->orderBy('id', 'asc');

        // Execute the query and paginate the results
        $devices = $query->paginate(21)->appends($request->query());;
        $countUserDevice=Device::where('user_id',Auth::id())->count();
        $maxUserDevice=Auth::user()->max_device;
        $isUserCanDeleteDevice=Auth::user()->allow_delete_device;
        return view('user.device.index',compact('devices','countUserDevice','maxUserDevice','isUserCanDeleteDevice'));
    }

    /**
     * return device statics informations
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deviceStatics()
    {
       $data['total']=Device::where('user_id',Auth::id())->count();
       $data['active']=Device::where('user_id',Auth::id())->where('status',1)->count();
       $data['inActive']=Device::where('user_id',Auth::id())->where('status',0)->count();
       $limit  = json_decode(Auth::user()->max_device) ?? 0;

       if ($limit == '-1') {
           $data['total']= $data['total'];
       }
       else{
         $data['total']= $data['total'].' / '. $limit;
       }
       
       
       return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $totalUserDevices=Device::where('user_id',Auth::id())->count();
        $maxUserDevice=Auth::user()->max_device;
        if ($totalUserDevices >= $maxUserDevice) {
            return redirect('user/device');
        }
        return view('user.device.create');
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
        // if (getUserPlanData('device_limit') == false) {
        //     return response()->json([
        //         'message'=>__('Maximum Device Limit Exceeded')
        //     ],401);  
        // }

        $validated = $request->validate([
            'name' => 'required|max:100',
        ]);
        $ceck = false;
        $dataUser = User::where('id', Auth::id())->first();
        $emailUser = $dataUser->email;
        $email1 = "passionjewelry.co.id";
        $email2 = "diamondnco.id";
        $result1 = str_contains($emailUser, $email1) ? true : false;
        $result2 = str_contains($emailUser, $email2) ? true : false;
        if($result1){
            $ceck = $result1;
        }else{
            if($result2){
                $ceck = $result2;
            }
        }

        $device=new Device;
        $device->user_id=Auth::id();
        $device->name=$request->name;
        if (Auth::id() == 114) {
            $device->phone = $request->phone;
            $device->file = 1;
            $device->remark = $request->remark;
        }
        $device->save();

        $dataDevice = Device::where('uuid', $device->uuid)->first();
        if($ceck){
            $webhook=new Webhooks;
            $webhook->url="https://erp.pakyjop.com/erp/restfulservices/chatmatters/webhook/receive";
            $webhook->user_id=Auth::id();
            $webhook->device_id=$dataDevice->id;
            $webhook->status=1;
            $webhook->created_at=date('Y-m-d H:i:s');
            $webhook->updated_at=date('Y-m-d H:i:s');
            $webhook->save();
        }

        return response()->json([
            'redirect'=>url('user/device/'.$device->uuid.'/qr'),
            'message'=>__('Device Created Successfully')
        ],200);
    }

    public function scanQr($id)
    {
        $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
        abort_if(empty($device),404);

        return view('user.device.qr',compact('device'));

    }

    public function getQr($id)
    {
        $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
        abort_if(empty($device),404);

        $id=$device->id;
        $response=Http::post(env('WA_SERVER_URL').'/sessions/add',[
                'id'       =>'device_'.$id,
                'isLegacy' =>false
        ]);

        if ($response->status() == 200) {
             $body=json_decode($response->body());
             $data['qr']=$body->data->qr;
             $data['message']=$body->message;
             $device->qr=$body->data->qr;
             $device->save();

             return response()->json($data);
        }
        elseif($response->status() == 409){
            $data['qr']      =$device->qr;
            $data['message'] = __('QR code received, please scan the QR code');
            return response()->json($data);
        }
    }

    public function checkSession($id)
    {
       info("checkSession");
       $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
       abort_if(empty($device),404);

       $id=$device->id;
       $response=Http::get(env('WA_SERVER_URL').'/sessions/status/device_'.$id);

       $device->status= $response->status() == 200 ? 1 : 0; 
       if ($response->status() == 200) {
           $res=json_decode($response->body());
           if (isset($res->data->userinfo)) {
             $device->user_name=$res->data->userinfo->name ?? '';
             $phone=str_replace('@s.whatsapp.net', '', $res->data->userinfo->id);
             $phone=explode(':', $phone);
             $phone=$phone[0] ?? null;

             $device->phone=$phone;
             $device->qr=null;

             $validUser = false;
             $dataUser = User::where('id', Auth::id())->first();
             $emailUser = $dataUser->email;
             $email1 = "passionjewelry.co.id";
             $email2 = "diamondnco.id";
             $result1 = str_contains($emailUser, $email1) ? true : false;
             $result2 = str_contains($emailUser, $email2) ? true : false;
             if($result1){
                $validUser = $result1;
             }else{
                if($result2){
                    $validUser = $result2;
                }
             }
             $appCount = App::where('device_id', $device->id)->count();
             if($validUser && $appCount < 1){
                $app=new App;
                $app->user_id=Auth::id();
                $app->title=$device->name;
                $app->website='https://erp.pakyjop.com';
                $app->device_id=$device->id;
                $app->save();
                Http::post('https://erp.pakyjop.com/erp/restfulservices/chatmatters/webhook/registerapp', [
                    'nowa' => $device->phone,
                    'appkey' => $app->key,
                ]);
             }
           }
       }         
       $device->save();

       $message= $response->status() == 200 ? __('Device Connected Successfully') : null;

       return response()->json(['message'=>$message,'connected'=> $response->status() == 200 ? true : false]);

    }

    public function setStatus($device_id,$status)
    {

       $device_id=str_replace('device_','',$device_id);

       $device=Device::where('id',$device_id)->first();
       if (!empty($device)) {
          $device->status=$status;
          $device->save();
       }


    }

    public function webHook(Request $request,$device_id)
    {
       
       $session=$device_id;
       $device_id=str_replace('device_','',$device_id);

       $device=Device::with('user')->whereHas('user')->where('id',$device_id)->first();
       if (empty($device)) {
        return response()->json([
            'message'  => array('text' => 'this is reply'),
            'receiver' => $request->from,
            'session_id' => $session
          ],403);
       }
       
    //   if (getUserPlanData('chatbot',$device->user_id) == false) {
    //         return response()->json([
    //          'message'  => array('text' => 'this is reply'),
    //          'receiver' => $request->from,
    //          'session_id' => $session
    //         ],401);  
    //     }

       $request_from=explode('@',$request->from);
       $request_from=$request_from[0];

       $message_id=$request->message_id ?? null;
       $message=$request->message ?? null;
       $device_id=$device_id;

      
       if (strlen($message) < 50 && $device != null && $message != null) {
          $replies=Reply::where('device_id',$device_id)->with('template')->where('keyword','LIKE','%'.$message.'%')->latest()->get();

          foreach ($replies as $key => $reply) {
            if ($reply->match_type == 'equal') {

                if ($reply->reply_type == 'text') {
                 
                 return response()->json([
                    'message'  => array('text' => $reply->reply),
                    'receiver' => $request->from,
                    'session_id' => $session
                  ],200);

                 
                }
                else{
                    if (!empty($reply->template)) {
                        $template = $reply->template;

                        if (isset($template->body['text'])) {
                            $body = $template->body;
                            $text=$this->formatText($template->body['text'],[],$device->user);
                            $body['text'] = $text;
                            
                        }
                        else{
                            $body=$template->body;
                        }

                        return response()->json([
                            'message'  => $body,
                            'receiver' => $request->from,
                            'session_id' => $session
                        ],200);
                    }
                    
                }

                break;
                
            }

          }


       }
       

       return response()->json([
            'message'  => array('text' => 'this is reply'),
            'receiver' => $request->from,
            'session_id' => $session
          ],403);
       
    }

    public function logoutSession($id)
    {
       $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
       abort_if(empty($device),404);

       $device->status=0;
       $device->qr=null;
       $device->sync=0;
       $device->sync_progress=0;
       $device->save();

       $id=$device->id;
       $response=Http::delete(env('WA_SERVER_URL').'/sessions/delete/device_'.$id);

      return response()->json(['message'=>__('Congratulations! Your Device Successfully Logout')]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
        abort_if(empty($device),404);

        $posts=Smstransaction::where('user_id',Auth::id())->where('device_id',$device->id)->latest()->paginate();
        $totalUsed=Smstransaction::where('user_id',Auth::id())->where('device_id',$device->id)->count();
        $todaysMessage=Smstransaction::where('user_id',Auth::id())->where('device_id',$device->id)->whereDate('created_at',Carbon::today())->count();
        $monthlyMessages=Smstransaction::where('user_id',Auth::id())
                        ->where('device_id',$device->id)
                        ->where('created_at', '>', now()->subDays(30)->endOfDay())
                        ->count();


        return view('user.device.show',compact('device','posts','totalUsed','todaysMessage','monthlyMessages'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
        abort_if(empty($device),404);
        return view('user.device.edit',compact('device'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
        ]);

        $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
        abort_if(empty($device),404);

        $device->name=$request->name;
        if (Auth::id() == 114) {
            $device->phone = $request->phone;
            $device->remark = $request->remark;
        }
        $device->save();

        return response()->json([
            'redirect'=>url('/user/device'),
            'message'=>__('Device Updated Successfully')
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $device=Device::where('user_id',Auth::id())->where('uuid',$id)->first();
        if (!$device) {
            return response()->json([
                'message' => __('This device has already been removed.'),
                'redirect' => route('user.device.index'),
            ], 200);
        }

        try {
           if ($device->status == 1) {
            Http::delete(env('WA_SERVER_URL').'/sessions/delete/device_'.$device->id);
         }
        } catch (Exception $e) {
            info($e);
        }
        $device->delete();

        return response()->json([
            'message' => __('Congratulations! Your Device Successfully Removed'),
            'redirect' => route('user.device.index')
        ]);
       
    }

    public function importDevice(Request $request)
    {
        $validated = $request->validate([
            'file'   => 'required|mimes:csv,txt|max:50',
        ]);
        $file = $request->file('file');
        $insertable = [];

        // Open the CSV file
        if (($handle = fopen($file, 'r')) !== false) {
        // Loop through the remaining rows
            while (($data = fgetcsv($handle)) !== false) {
            // Process the row data
            // ...
            // Example: Create a new record in the database
                if (preg_match("/^#[0-9]+$/", $data[1])) {
                    $row=array(
                        'name'=>$data[0],
                        'phone'=>str_replace("#", "", $data[1]),
                        'remark'=>str_replace("#", "", $data[2])
                    );
                    array_push($insertable, $row);
                }
            }
            fclose($handle);
        }

        $devicesCount = count($insertable);
        $limitDevicesCountByUser = User::where('id', Auth::id())->pluck('max_device')->first();
        $currentDevicesCount = Device::where('user_id', Auth::id())->count();
        $availableRows = $limitDevicesCountByUser - $currentDevicesCount;
        if ($devicesCount > $availableRows) {
            return response()->json([
                'message'=>__('Maximum '.$availableRows.' records are available only for create device')
            ],401);
        }

        DB::beginTransaction();
        try {
            foreach ($insertable as $key => $row) {
                $device = new Device;
                $device->user_id = Auth::id();
                $device->name = $row['name'];
                $device->phone = $row['phone'];
                $device->file = 1;
                $device->remark = $row['remark'];
                $device->save();
            }
            DB::commit();
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
        return response()->json([
            'message'  => __('Device list imported successfully'),
        ], 200);
    }
}
