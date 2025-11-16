@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Downline Devices')])
@endsection
@section('content')
<div class="row">
    <div class="col">
        <div class="card">
            <!-- Card header -->
            <div class="card-header border-0 d-flex flex-column align-items-start" style="gap: 16px;">
                <h3 class="mb-0">{{ __('Downline Devices') }}</h3>
                <form class="w-100" method="GET" action="{{ route('user.downline-device.index') }}">
                    <div class="input-group d-flex" style="gap: 24px;">
                        <div class="from-group row flex-grow-1">
                            <label class="col-lg-12 mb-0">{{ __('Search') }}</label>
                            <div class="col-lg-12">
                                <input class="form-control" type="text" name="search" placeholder="Search device name, phone, or user..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="from-group row flex-grow-1">
                            <label class="col-lg-12 mb-0">{{ __('Downline') }}</label>
                            <div class="col-lg-12">
                                <select class="form-control" name="downline">
                                    <option value="all" @if(request('downline') == 'all' || !request('downline')) selected="" @endif>{{ __('All Downlines') }}</option>
                                    @foreach($downlineUsers ?? [] as $downlineUser)
                                        <option value="{{ $downlineUser->uuid }}" @if(request('downline') == $downlineUser->uuid) selected="" @endif>
                                            {{ $downlineUser->name }} ({{ $downlineUser->position }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="from-group row flex-grow-1">
                            <label class="col-lg-12 mb-0">{{ __('Status') }}</label>
                            <div class="col-lg-12">
                                <select class="form-control" name="status">
                                    <option value="all" @if(request('status') == 'all' || !request('status')) selected="" @endif>{{ __('All Status') }}</option>
                                    <option value="1" @if(request('status') == '1') selected="" @endif>{{ __('Active') }}</option>
                                    <option value="0" @if(request('status') == '0') selected="" @endif>{{ __('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column mt-3" style="gap: 12px;">
                        <button class="btn btn-block btn-neutral" type="submit">{{ __('Search') }}</button>
                    </div>
                </form>
            </div>
            <!-- Light table -->
            <div class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th class="col-1">{{ __('Downline Name') }}</th>
                            <th class="col-1">{{ __('Position') }}</th>
                            <th class="col-1">{{ __('Device Name') }}</th>
                            <th class="col-1">{{ __('Phone') }}</th>
                            <th class="col-1">{{ __('Status') }}</th>
                            <th class="col-1">{{ __('Created At') }}</th>
                        </tr>
                    </thead>
                    @if(count($devices) != 0)
                    <tbody class="list">
                        @foreach($devices ?? [] as $device)
                        <tr>
                            <td>
                                <a href="{{ route('user.downline.device', $device->user->uuid) }}" class="text-dark">
                                    {{ $device->user->name }}
                                </a>
                            </td>
                            <td>{{ $device->user->position }}</td>
                            <td>
                                <a href="{{ route('user.downline.device.chat', $device->uuid) }}" class="text-dark">
                                    {{ $device->name }}
                                </a>
                            </td>
                            <td>{{ $device->phone }}</td>
                            <td>
                                <span class="badge badge-{{ $device->status == 1 ? 'success' : 'danger' }}">
                                    {{ $device->status == 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($device->created_at)->format('d-F-Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @endif
                </table>
                @if(count($devices) == 0 && (request('search') || request('downline') || request('status')))
                <div class="text-center mt-2 px-2">
                    <div class="alert bg-gradient-primary text-white">
                        <span class="text-left">{{ __('No devices found with the selected filters!') }}</span>
                    </div>
                </div>
                @endif
                @if(count($devices) == 0 && !request('search') && !request('downline') && !request('status'))
                <div class="text-center mt-2 px-2">
                    <div class="alert bg-gradient-primary text-white">
                        <span class="text-left">{{ __('No downline devices found!') }}</span>
                    </div>
                </div>
                @endif
            </div>
            @if(count($devices) > 0)
            <div class="card-footer py-4">
                {{ $devices->appends($request->all())->links('vendor.pagination.bootstrap-4') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
