<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\Whatsapp;
use App\Models\User;
use App\Models\Downline;
use App\Models\Device;
use App\Models\Contact;
use App\Models\Chats;
use Auth;
use Cache;
use DateTime;

class DownlineController extends Controller
{
    use Whatsapp;

    public function index(Request $request)
    {
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());
        $downlineUsers = User::whereIn('id', $downlineUsersId)
            ->orderByRaw("
                CASE
                    WHEN position = 'CSO' THEN 1
                    WHEN position = 'Area Manager' THEN 2
                    WHEN position = 'Store Manager' THEN 3
                    WHEN position = 'Sales' THEN 4
                    ELSE 5
                END
            ")
            ->withCount(['device as active_devices_count' => function($query) {
                $query->where('status', 1);
            }]);

        if (!empty($request->search)) {
            $downlineUsers = $downlineUsers->where($request->type,'LIKE','%'.strtolower($request->search).'%');  
        };

        $downlineUsers = $downlineUsers->paginate(10);
        $type = $request->type ?? '';

        return view('user.downline.index', compact('request', 'type', 'downlineUsers'));
    }

    public function show($id)
    {
        return redirect('user/downline');
    }

    public function downlineDevices(Request $request, $uuid)
    {
        $found = false;
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());
        $downline = User::where('uuid', $uuid)->first(['id', 'name', 'position']);

        foreach ($downlineUsersId as $downlineUserId) {
            if ($downlineUserId == $downline->id) {
                $found = true;
                break;
            }
        }

        if(!$found) {
            return redirect('user/downline');
        }

        $devices = Device::where('user_id', $downline->id)->select('uuid', 'name', 'phone', 'status');;
        if (!empty($request->search)) {
            $devices = $devices->where($request->type,'LIKE','%'.strtolower($request->search).'%');  
        };

        $devices = $devices->paginate(10);
        $type = $request->type ?? '';

        return view('user.downline.devices', compact('downline', 'devices', 'request', 'type'));   
    }

    public function downlineChats($uuid)
    {
        $selectedDownlineUserId = Device::where("uuid", $uuid)->first(['user_id'])->user_id ?? null;
        abort_if(!$selectedDownlineUserId, 404);

        $found = false;
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());

        foreach ($downlineUsersId as $downlineUserId) {
            if ($downlineUserId == $selectedDownlineUserId) {
                $found = true;
                break;
            }
        }

        if(!$found) {
            return redirect('user/downline');
        }

        return view('user.downline.chats', compact('selectedDownlineUserId'));   
    }

    public function downlineContacts($uuid)
    {
        $device = Device::where("uuid", $uuid)->first();
        abort_if(empty($device), 404);

        $data["device_name"] = $device->name;
        $data["phone"] = $device->phone;
        $data["sync"] = $device->sync;
        $data["status"] = $device->status;

        // If synchronization is in progress (status=1, sync=0), return 202
        if ($device->status == 1 && $device->sync == 0) {
            $data["message"] = "Synchronization is still in progress. Please wait.";
            return response()->json($data, 202);
        }

        // Otherwise, return contacts with last messages
        $getContact = Contact::where('device_id', $device->id)->get();

        foreach ($getContact as &$getContact_) {
            $contactsWithLastMessage = $getContact_->lastmessages();
            $getContact_['message'] = isset($contactsWithLastMessage->unic_id) ? ($contactsWithLastMessage->message ?? 'Unsupported Message') : '';
            $getContact_['file'] = $contactsWithLastMessage->file ?? null;
            $getContact_['timestamp'] = $contactsWithLastMessage->timestamp ?? null;
            $getContact_['number'] = $getContact_['phone'] ?? $getContact_['lid'];
            $getContact_['status'] = $contactsWithLastMessage->status ?? null;
            $getContact_['fromMe'] = $contactsWithLastMessage->fromMe ?? null;
            unset($getContact_['phone']);
        }

        $data["chats"] = $getContact->sortByDesc('timestamp')->values();
        return response()->json($data);
    }

    public function downlineContactChats(Request $request, $uuid)
    {
        $device = Device::where("uuid", $uuid)->first();
        abort_if(empty($device), 404);

        $contact = Contact::where('device_id', $device->id)
            ->where(function($query) use ($request) {
                $query->where('phone', $request->phone)
                      ->orWhere('lid', $request->phone);
            })
            ->first();

        abort_if(empty($contact), 404);

        $offset = $request->page * $request->limit;
        $getChat = Chats::where('device_id', $device->id)
            ->where('contact_id', $contact->id)
            ->offset($offset)
            ->limit($request->limit)
            ->orderBy('timestamp', 'desc')
            ->get();

        $formattedChats = $getChat->map(function ($chat) {
            return [
                'id' => $chat->unic_id,
                'message' => $chat->message ?? 'Unsupported Message',
                'file' => $chat->file,
                'fromMe' => $chat->fromMe,
                'timestamp' => $chat->timestamp,
                'status' => $chat->status,
            ];
        });

        $data["chats"] = $formattedChats;
        return response()->json($data);
    }

    public function downlineData(Request $request)
    {
        $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        $startDate = (new DateTime($request->startDate))->setTime(0, 0, 1)->getTimestamp();
        $endDate = (new DateTime($request->endDate))->setTime(23, 59, 59)->getTimestamp();
        $totalCounts = DB::table('chatViewAll')
            ->select([
                DB::raw('CAST(COALESCE(SUM(CASE WHEN fromMe = "true" THEN 1 ELSE 0 END), 0) as UNSIGNED) as totalSending'),
                DB::raw('CAST(COALESCE(SUM(CASE WHEN fromMe = "false" THEN 1 ELSE 0 END), 0) as UNSIGNED) as totalReceive')
            ])
            ->where('id', $request->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->first();
        $totalCounts->percentage = $totalCounts->totalSending !== 0 ? ($totalCounts->totalReceive / $totalCounts->totalSending) * 100 : 0;
        $totalCounts->totalContact = Contact::where('user_id', $request->id)->count();
        return response()->json($totalCounts, 200);
    }
}
