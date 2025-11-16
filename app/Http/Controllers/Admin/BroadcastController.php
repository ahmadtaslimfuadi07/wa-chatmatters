<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use App\Models\Contact;
use App\Models\Device;
use App\Models\User;
use App\Models\App;
use Carbon\Carbon;
use Auth;
use Http;

class BroadcastController extends Controller
{
    private $BE_BROADCAST;

    public function __construct() {
        $this->BE_BROADCAST = env('BE_BROADCAST');
    }

    public function index(Request $request)
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $limit = 20;
        $response=Http::post($this->BE_BROADCAST.'/api/broadcasts/list', [
            'page' => $page - 1,
            'limit' => $limit,
            'title' => $request->search
        ]);
        $data = $response->json();
        $broadcastFormatted = [];
        foreach ($data['data'] as $item) {
            $item['userName'] = '';
            if (isset($item['userId'])) {
                $item['userName'] = User::where('id',$item['userId'])->value('name');
            }
            $broadcastFormatted[] = $item;
        }
        $broadcasts = new LengthAwarePaginator($broadcastFormatted, $data['totalData'], $limit, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
        $type = $request->type ?? '';

        return view('admin.broadcast.index', compact('request', 'type', 'broadcasts'));
    }

    public function create()
    {
        return redirect('admin/broadcast');
    }

    public function edit()
    {
        return redirect('admin/broadcast');
    }

    public function show($id)
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $limit = 20;
        $response=Http::post($this->BE_BROADCAST.'/api/broadcasts/detailsend', [
            'idBroadcast' => $id,
            'page' => $page - 1,
            'limit' => $limit
        ]);
        $data = $response->json();
        if (!$data['data']) {
            abort(404);
        }
        $broadcastLogsFormatted = [];
        foreach ($data['data'] as $item) {
            $item['deviceName'] = Device::where('id',$item['deviceId'])->value('name');
            $broadcastLogsFormatted[] = $item;
        }
        $broadcastLogs = new LengthAwarePaginator($broadcastLogsFormatted, $data['totalData'], $limit, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return view('admin.broadcast.show', compact('broadcastLogs'));
    }
}
