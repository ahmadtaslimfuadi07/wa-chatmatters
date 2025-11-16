<?php

namespace App\Http\Controllers\User;

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
            'title' => $request->search,
            'userId' => strval(Auth::id())
        ]);
        $data = $response->json();
        $broadcasts = new LengthAwarePaginator($data['data'], $data['totalData'], $limit, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
        $type = $request->type ?? '';

        $prerequisite = [
            'haveAuthKey' => isset(Auth::user()->authkey),
            'haveAtLeastOneAppKey' => App::where('user_id', Auth::id())->exists()
        ];

        return view('user.broadcast.index', compact('request', 'type', 'broadcasts', 'prerequisite'));
    }

    public function create()
    {
        $contacts=Contact::where('user_id',Auth::id())->whereNotNull('phone')->orderByRaw('CASE WHEN name IS NULL OR name = "" THEN 1 ELSE 0 END')->latest()->get();
        $activeDevices=Device::where('user_id',Auth::id())->where("status", 1)->latest()->get()->pluck('id');
        $apps=App::where('user_id', Auth::id())->whereIn('device_id', $activeDevices)->latest()->get()->toArray();
        return view('user.broadcast.create', compact('contacts', 'apps'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'contact' => 'required|array',
            'title' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    $body = $request->input('body', '');
                    $totalLength = mb_strlen($value . $body);
        
                    if ($totalLength > 995) {
                        $fail('The combined message may not exceed 995 characters.');
                    }
                },
            ],
            'body' => 'required|string',
        ]);

        $contact = [];
        $device = [];
        $auntKey = Auth::user()->authkey;
        $key = $request->key;

        foreach ($request->contact as $item) {
            list($selectedContact, $selectedDeviceContact) = explode('|', $item);
            $contact[] = $selectedContact;
            $deviceItem['deviceId'] = $selectedDeviceContact;
            $deviceItem['auntKey'] = $auntKey;
            $deviceItem['key'] = $key;
            $device[] = (object) $deviceItem;
        }

        $body=[
            'title' => $request->title,
            'body' => $request->body,
            'desc' => $request->desc,
            'start' => Carbon::parse($request->start)->format('Y-m-d H:i:s'),
            'typeInterval' => $request->typeInterval,
            'interval' => $request->interval,
            'limitContact' => $request->limitContact,
            'contact' => join(",", $contact),
            'device' => json_encode($device),
            'userId' => Auth::id()
        ];
        $fileImage = $request->file('image');
        if (!$fileImage) {
            $response=Http::post($this->BE_BROADCAST.'/api/broadcasts/create/v2', $body);
        }
        else {
            $fileContents = file_get_contents($fileImage->path());
            $fileName = $fileImage->getClientOriginalName();
            $response=Http::attach('image', $fileContents, $fileName)->post($this->BE_BROADCAST.'/api/broadcasts/create/v2', $body);
        }
        if ($response['response_code'] == 202) {
            return response()->json([
                'redirect' => route('user.broadcast.index'),
                'message' => __('Broadcast created successfully.')
            ]);
        } else {
            return response()->json([
                'message'=>__('Oops! Something went wrong, please contact administrator')
            ],422);
        }
    }

    public function edit($id)
    {
        $broadcast=[];
        $response=Http::post($this->BE_BROADCAST.'/api/broadcasts/listdetail', [
            'id' => $id
        ]);
        if (isset($response['data'])) {
            $broadcast=$response['data'];
        }
        if (isset($broadcast['interval'])) {
            $broadcast['interval']=$this->getIntervalValue($broadcast['interval'], $broadcast['typeInterval']);
        }
        $contacts=Contact::where('user_id',Auth::id())->whereNotNull('phone')->latest()->get();
        $activeDevices=Device::where('user_id',Auth::id())->where("status", 1)->latest()->get()->pluck('id');
        $apps=App::where('user_id', Auth::id())->whereIn('device_id', $activeDevices)->latest()->get()->toArray();
       
        return view('user.broadcast.edit',compact('broadcast', 'contacts', 'apps'));
    }
    
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'contact' => 'required|array',
            'title' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    $body = $request->input('body', '');
                    $totalLength = mb_strlen($value . $body);
        
                    if ($totalLength > 995) {
                        $fail('The combined message may not exceed 995 characters.');
                    }
                },
            ],
            'body' => 'required|string',
        ]);

        $contact = [];
        $device = [];
        $auntKey = Auth::user()->authkey;
        $key = $request->key;

        foreach ($request->contact as $item) {
            list($selectedContact, $selectedDeviceContact) = explode('|', $item);
            $contact[] = $selectedContact;
            $deviceItem['deviceId'] = $selectedDeviceContact;
            $deviceItem['auntKey'] = $auntKey;
            $deviceItem['key'] = $key;
            $device[] = (object) $deviceItem;
        }

        $body=[
            '_id' => $id,
            'title' => $request->title,
            'body' => $request->body,
            'desc' => $request->desc,
            'start' => Carbon::parse($request->start)->format('Y-m-d H:i:s'),
            'typeInterval' => $request->typeInterval,
            'interval' => $request->interval,
            'limitContact' => $request->limitContact,
            'contact' => join(",", $contact),
            'device' => json_encode($device),
            'userId' => Auth::id()
        ];

        $fileImage = $request->file('image');
        if (!$fileImage) {
            $response=Http::post($this->BE_BROADCAST.'/api/broadcasts/update/v2', $body);
        }
        else {
            $fileContents = file_get_contents($fileImage->path());
            $fileName = $fileImage->getClientOriginalName();
            $response=Http::attach('image', $fileContents, $fileName)->post($this->BE_BROADCAST.'/api/broadcasts/update/v2', $body);
        }
        if ($response['response_code'] == 202) {
            return response()->json([
                'redirect' => route('user.broadcast.index'),
                'message' => __('Broadcast updated successfully.')
            ]);
        } else {
            return response()->json([
                'message'=>__('Oops! Something went wrong, please contact administrator')
            ],422);
        }
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

        $broadcastLogsFormatted = [];
        foreach ($data['data'] as $item) {
            $contactName = Contact::where('device_id',$item['deviceId'])->where('phone', $item['phoneNumber'])->value('name');
            $item['recipient'] = $contactName
                ? $contactName . ' - ' . $item['phoneNumber']
                : $item['phoneNumber'];
            $senderDevice = Device::where('id',$item['deviceId'])->first();
            $item['sender'] = $senderDevice
                ? $senderDevice->name . ' - ' . $senderDevice->phone
                : 'Unknown Device';
            $broadcastLogsFormatted[] = $item;
        }
        $broadcastLogs = new LengthAwarePaginator($broadcastLogsFormatted, $data['totalData'], $limit, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return view('user.broadcast.show', compact('broadcastLogs'));
    }

    private function getIntervalValue($originalInterval, $typeInterval)
    {
        if ($typeInterval == "JAM") {
            return $originalInterval/3600;
        }
        elseif ($typeInterval == "MENIT") {
            return $originalInterval/60;
        }
        else {
            return $originalInterval;
        }
    }
}
