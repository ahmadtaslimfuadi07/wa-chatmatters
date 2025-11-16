<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Downline;
use App\Models\Device;
use Auth;

class DownlineDeviceController extends Controller
{
    public function index(Request $request)
    {
        // Get all downline user IDs
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());
        
        // Get downline users for filter dropdown
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
            ->select('id', 'uuid', 'name', 'email', 'position')
            ->get();

        // Build device query
        $devicesQuery = Device::whereIn('user_id', $downlineUsersId)
            ->with(['user:id,uuid,name,email,position'])
            ->join('users', 'devices.user_id', '=', 'users.id');

        // Apply filters
        if ($request->filled('downline') && $request->downline !== 'all') {
            $devicesQuery->where('users.uuid', $request->downline);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $devicesQuery->where('devices.status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $devicesQuery->where(function($query) use ($search) {
                $query->where('devices.name', 'LIKE', '%' . $search . '%')
                      ->orWhere('devices.phone', 'LIKE', '%' . $search . '%')
                      ->orWhere('users.name', 'LIKE', '%' . $search . '%')
                      ->orWhere('users.email', 'LIKE', '%' . $search . '%');
            });
        }

        // Get paginated results
        $devices = $devicesQuery->select('devices.*')
                               ->orderBy('devices.status', 'desc')
                               ->orderByRaw("
                                   CASE
                                       WHEN users.position = 'CSO' THEN 1
                                       WHEN users.position = 'Area Manager' THEN 2
                                       WHEN users.position = 'Store Manager' THEN 3
                                       WHEN users.position = 'Sales' THEN 4
                                       ELSE 5
                                   END
                               ")
                               ->orderBy('users.name', 'asc')
                               ->orderBy('devices.id', 'desc')
                               ->paginate(10)
                               ->appends($request->query());

        return view('user.downline-device.index', compact('request', 'devices', 'downlineUsers'));
    }
}