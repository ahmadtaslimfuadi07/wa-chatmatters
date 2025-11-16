<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\App;
use Carbon\Carbon;
use App\Models\Chats;
use App\Models\Device;
use Http;
use Auth;
use Str;
use DB;
use Session;
class ChatController extends Controller
{


    /**
     * sent message
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {

        if($request->appkey==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param appkey is required',
          ],400);
        }

        if($request->phone==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param phone is required',
          ],400);
        }

        if($request->start_date==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param start_date is required',
          ],400);
        }

        if($request->end_date==null){
            return response()->json([
            'code'  => 400,
            'message'  => 'param end_date is required',
          ],400);
        }

        $app=App::where('key',$request->appkey)->whereHas('device')->with('device')->where('status',1)->first();
        if ($app == null) {
            return response()->json(['error'=>'Invalid AppKey'],401);
        }
        $device=Device::where('id',$app->device_id)->where('phone',$request->phone)->first();
        if ($device == null) {
            return response()->json(['error'=>'Invalid AppKey for phone'],401);
        }
        $dateStart = strtotime($request->start_date);
        $dateEnd = strtotime($request->end_date);
        $chats=Chats::where('device_id',$app->device_id)
            ->where('timestamp', '<=', $dateEnd)
            ->where('timestamp', '>=', $dateStart)
            ->orderBy('timestamp', 'desc')
            ->get();

        foreach ($chats as $row){
            $row["timestamp"] = date("Y-m-d H:i:s", $row->timestamp);
            unset($row['id']);
            unset($row['created_at']);
            unset($row['updated_at']);
            unset($row['user_id']);
            unset($row['device_id']);
            unset($row['contact_id']);
            unset($row['unic_id']);
            if($row['file']==null){
                unset($row['file']);
            }
        }
        return response()->json([
            'code'  => 202,
            'data'  => $chats,
            'message'  => 'Succes',
        ],202);
     }

     public function updateStatus(Request $request)
     {
        $unique_id = $request->update['key']['id'] ?? null;
        $status = $request->update['update']['status'] ?? null;

        if ($unique_id != null && $status != null) {
            Chats::where('unic_id', $unique_id)
                ->where(function($query) use ($status) {
                    $query->where('status', '<', $status)
                          ->orWhereNull('status');
                })
                ->update(['status' => $status]);
        }

        return response()->json([
            'code'  => 200,
            'message'  => 'Success',
        ],200);
     }
 }
