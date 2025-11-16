<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Webhooks;
use App\Models\Device;
use App\Models\Reply;
use App\Models\Smstransaction;
use App\Models\Template;
use App\Models\User;
use DB;
use Auth;
use Http;
use Session;
use Carbon\Carbon;
class WebhookController extends Controller
{

    // public function __construct(){
    //     $this->middleware('permission:webhook');
    // }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $devices = Device::where('user_id',Auth::id())->latest()->get();
        $webhook=Webhooks::where('user_id',Auth::id())->latest()->paginate(20);
        return view('user.webhook.index',compact('webhook','devices'));
    }

    /**
     * return device statics informations
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function webhookStatics()
    {
       $data['total']=Webhooks::query()->where('user_id',Auth::id())->count();
       $data['active']=Webhooks::where('status',1)->where('user_id',Auth::id())->count();
       $data['inActive']=Webhooks::where('status',0)->where('user_id',Auth::id())->count();
       $limit  = json_decode(Auth::user()->plan);
       $limit = $limit->device_limit ?? 0;

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
        $devices = Device::where('user_id',Auth::id())->latest()->get();
        return view('user.webhook.create',compact('devices'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $is_exist=Webhooks::where('user_id',Auth::id())->where('device_id',$request->device_id)->where('url',$request->url)->first();
        if (!empty($is_exist)) {
           return response()->json([
                'message'  => __('Opps this webhook url you have already added')
            ], 401);
        }

        $validated = $request->validate([
            'url' => 'required|max:150',
            'status' => 'required',
            'device_id' => 'required',
        ]);

        $webhook=new Webhooks;
        $webhook->url=$request->url;
        $webhook->user_id=Auth::id();
        $webhook->device_id=$request->device_id;
        $webhook->status=$request->status;
        $webhook->created_at=date('Y-m-d H:i:s');
        $webhook->updated_at=date('Y-m-d H:i:s');
        $webhook->save();

        return response()->json([
            'redirect'=>route('user.webhook.index'),
            'message'=>__('Webhook Created Successfully')
        ],200);
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
            'url' => 'required|max:150',
            'status' => 'required',
            'device_id' => 'required',
        ]);

        $is_exist=Webhooks::where('user_id',Auth::id())->where('device_id',$request->device_id)->where('url',$request->url)->where('id','!=',$id)->first();
        if (!empty($is_exist)) {
           return response()->json([
                'message'  => __('Opps this webhook url you have already added')
            ], 401);
        }

        $webhook=  Webhooks::where('user_id',Auth::id())->findorFail($id);
        $webhook->url=$request->url;
        $webhook->device_id=$request->device_id;
        $webhook->status=$request->status;
        $webhook->updated_at=date('Y-m-d H:i:s');
        $webhook->save();

        return response()->json([
                    'message' => __('Webhook update Successfully'),
                    'redirect' =>  route('user.webhook.index')
               ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $webhook=Webhooks::where('user_id',Auth::id())->findorFail($id);
        $webhook->delete();

        return response()->json([
            'message'  => __('Webhook deleted successfully..!!'),
            'redirect' =>  route('user.webhook.index')
        ], 200);
    }

    public function status($id)
    {
        $webhook=Webhooks::where('user_id',Auth::id())->findorFail($id);
        if($webhook->status==1){
            $webhook->status = 0;
        }else{
            $webhook->status = 1;
        }
        $webhook->updated_at=date('Y-m-d H:i:s');
        $webhook->save();
        return $this->index();
    }
}
