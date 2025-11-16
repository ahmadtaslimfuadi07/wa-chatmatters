<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Contact;
use App\Models\Chats;
use App\Models\Downline;
use Carbon\Carbon;
use Auth;
use Session;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->will_expire != null) {
            $nextDate= Carbon::now()->addDays(7)->format('Y-m-d');
            if (Auth::user()->will_expire <= now()) {
                Session::flash('saas_error', __('Your subscription was expired at '.Carbon::parse(Auth::user()->will_expire)->diffForHumans().' please renew the subscription'));
            }

            elseif(Auth::user()->will_expire <= $nextDate){
                Session::flash('saas_error', __('Your subscription is ending in '.Carbon::parse(Auth::user()->will_expire)->diffForHumans()));
            }
        }
       
        $countUserDevice=Device::where('user_id',Auth::id())->count();
        $maxUserDevice=Auth::user()->max_device;
       
        return view('user.dashboard',compact('countUserDevice','maxUserDevice'));
    }

    public function dashboardData()
    {
        // Handle period parameter for chart updates
        $days = request()->query('days', 7); // Default to 7 days

        // Get downline user IDs for recursive data
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());
        
        // Include current user ID in the array
        $allUserIds = array_merge([Auth::id()], $downlineUsersId);
        
        // Basic counts for stats cards (only on initial load, not period changes)
        $data = [];
        
        // Include stat card data only when it's initial load (no period specified in query)
        if (!request()->has('days') && !request()->has('start_date')) {
            $data = [
                'devicesCount' => Device::whereIn('user_id', $allUserIds)->count(),
                'messagesCount' => Chats::whereIn('user_id', $allUserIds)->count(),
                'contactCount' => Contact::whereIn('user_id', $allUserIds)->count(),
            ];
        }
        
        // Always include chart data (for both initial load and period changes)
        $data = array_merge($data, [
            'messagesStatics' => $this->getMessagesTransaction($days),
            'devicePerformance' => $this->getDevicePerformance($days)
        ]);

        return response()->json($data);
    }

    public function getMessagesTransaction($days = null)
    {
        // Get downline user IDs for recursive data
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());
        
        // Include current user ID in the array
        $allUserIds = array_merge([Auth::id()], $downlineUsersId);

        // Handle custom date range or predefined days
        if (request()->has('start_date') && request()->has('end_date')) {
            // Respect user's exact time selection for custom ranges
            $startDate = Carbon::parse(request('start_date'));
            $endDate = Carbon::parse(request('end_date'));
            
            // Backend validation: enforce 90-day limit
            if ($startDate->diffInDays($endDate) > 90) {
                return response()->json(['error' => 'Date range cannot exceed 90 days'], 400);
            }
        } else {
            // Fallback to days-based calculation for backward compatibility
            $days = $days ?? 7;
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }
        
        $statics = Chats::query()->whereIn('user_id', $allUserIds)
                ->where('timestamp', '>=', $startDate->timestamp)
                ->where('timestamp', '<=', $endDate->timestamp)
                ->selectRaw('
                    DATE(FROM_UNIXTIME(timestamp)) as date,
                    COUNT(CASE WHEN fromMe = "true" THEN 1 END) as sent_count,
                    COUNT(CASE WHEN fromMe = "false" THEN 1 END) as received_count,
                    COUNT(*) as total_count
                ')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

        return $statics;
    }

    public function getDevicePerformance($days = null)
    {
        // Get downline user IDs for recursive data
        $downline = new Downline();
        $downlineUsersId = $downline->getDownlineUsersId(Auth::id());
        
        // Include current user ID in the array
        $allUserIds = array_merge([Auth::id()], $downlineUsersId);

        // Handle custom date range or predefined days
        if (request()->has('start_date') && request()->has('end_date')) {
            // Respect user's exact time selection for custom ranges
            $startDate = Carbon::parse(request('start_date'));
            $endDate = Carbon::parse(request('end_date'));
            
            // Backend validation: enforce 90-day limit
            if ($startDate->diffInDays($endDate) > 90) {
                return response()->json(['error' => 'Date range cannot exceed 90 days'], 400);
            }
        } else {
            // Fallback to days-based calculation for backward compatibility
            $days = $days ?? 7;
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }
        
        // Use same pattern as working getMessagesTransaction method
        $deviceStats = Chats::whereIn('chats.user_id', $allUserIds)
            ->where('chats.timestamp', '>=', $startDate->timestamp)
            ->where('chats.timestamp', '<=', $endDate->timestamp)
            ->leftJoin('devices', 'chats.device_id', '=', 'devices.id')
            ->selectRaw('
                devices.id,
                devices.name,
                devices.uuid,
                devices.status,
                COUNT(CASE WHEN chats.fromMe = "true" THEN 1 END) as sent_count,
                COUNT(CASE WHEN chats.fromMe = "false" THEN 1 END) as received_count,
                COUNT(*) as total_count
            ')
            ->groupBy('devices.id', 'devices.name', 'devices.uuid', 'devices.status')
            ->orderByDesc('total_count')
            ->get();

        // Map to expected format
        $mappedStats = $deviceStats->map(function($device) {
            return [
                'device_name' => $device->name ?: 'Device ' . substr($device->uuid, 0, 8),
                'sent_count' => (int) $device->sent_count,
                'received_count' => (int) $device->received_count,
                'total_count' => (int) $device->total_count,
                'status' => $device->status
            ];
        });

        return $mappedStats;
    }
}
