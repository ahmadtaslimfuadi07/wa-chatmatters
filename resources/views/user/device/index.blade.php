@extends('layouts.main.app')
@section('head')
    @if ($countUserDevice < $maxUserDevice)
        @include('layouts.main.headersection', [
            'title' => __('Device'),
            'buttons' => array_filter([
                [
                    'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Create Device'),
                    'url' => route('user.device.create'),
                ],
            ]),
        ])
    @else
        @include('layouts.main.headersection', ['title' => __('Device')])
    @endif
@endsection
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="row d-flex justify-content-between flex-wrap">
                <div class="col">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <span class="h2 font-weight-bold mb-0 total-transfers" id="total-device">
                                        <img src="{{ asset('uploads/loader.gif') }}">
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                        <i class="fi fi-rs-devices mt-2"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                            </p>
                            <h5 class="card-title  text-muted mb-0">{{ __('Total Devices') }}</h5>
                            <p></p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <span class="h2 font-weight-bold mb-0 total-transfers" id="total-active">
                                        <img src="{{ asset('uploads/loader.gif') }}">
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                        <i class="fi fi-rs-badge-check mt-2"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                            </p>
                            <h5 class="card-title  text-muted mb-0">{{ __('Active Devices') }}</h5>
                            <p></p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-stats">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <span class="h2 font-weight-bold mb-0 completed-transfers" id="total-inactive">
                                        <img src="{{ asset('uploads/loader.gif') }}">
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                        <i class="fi  fi-rs-exclamation mt-2"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-sm">
                            </p>
                            <h5 class="card-title  text-muted mb-0">{{ __('Inactive Devices') }}</h5>
                            <p></p>
                        </div>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('user.device.index') }}">
                <div class="input-group">
                    <input class="form-control mr-4" type="text" name="name" placeholder="Name"
                        value="{{ request('name') }}">
                    <input class="form-control mr-4" type="text" name="phone" placeholder="Phone"
                        value="{{ request('phone') }}">
                    <select class="form-control mr-4" name="status">
                        <option value="">Select Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @if (Auth::user()->id == 114)
                        <input class="form-control" type="text" name="remark" placeholder="Remark"
                            value="{{ request('remark') }}">
                    @endif
                </div>
                <button class="btn btn-block btn-neutral mt-3 mb-4" type="submit">Filter</button>
            </form>
            @if (count($devices ?? []) > 0)
                <div class="row">
                    @foreach ($devices ?? [] as $device)
                        <div class="col-xl-4 col-md-6">
                            <div class="card  border-0">
                                <!-- Card body -->
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0 text-dark">
                                                {{ $device->name }}</h5>
                                            <div class="mt-3 mb-0">
                                                <span class="pt-2 text-dark">{{ __('Phone :') }}
                                                    @if (!empty($device->phone))
                                                        <a href="{{ route('user.device.scan', $device->uuid) }}">
                                                            {{ $device->phone }}
                                                        </a>
                                                    @endif
                                                </span>
                                                <br>
                                                <br>
                                                <span class="pt-2 text-dark">{{ __('Total Messages:') }}
                                                    {{ number_format($device->smstransaction_count) }}</span>
                                                @if (Auth::user()->id == 114)
                                                    <div class="d-flex text-dark">
                                                        <div class="mr-1">{{ __('Remark:') }}</div>
                                                        <div>{{ $device->remark }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-sm btn-neutral mr-0"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item has-icon"
                                                    href="{{ route('user.device.scan', $device->uuid) }}"><i
                                                        class="fas fa-qrcode"></i>{{ __('Scan') }}</a>
                                                <a class="dropdown-item has-icon"
                                                    href="{{ url('/user/device/chats/' . $device->uuid) }}"><i
                                                        class="fi fi-rs-comments-question-check"></i>{{ __('Chats') }}</a>
                                                <a class="dropdown-item has-icon"
                                                    href="{{ route('user.device.edit', $device->uuid) }}"><i
                                                        class="fi  fi-rs-edit"></i>{{ __('Edit Device') }}</a>
                                                <a class="dropdown-item has-icon"
                                                    href="{{ route('user.device.show', $device->uuid) }}"><i
                                                        class="ni ni-align-left-2"></i>{{ __('View Log') }}</a>
                                                @if ($isUserCanDeleteDevice)
                                                    <a class="dropdown-item has-icon" href="#" data-toggle="modal" data-target="#delete-device-{{ $device->uuid }}">
                                                        <i class="fi  fi-rs-trash"></i>
                                                        {{ __('Delete Device') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-sm">
                                        <a class="text-nowrap  font-weight-600"
                                            href="{{ route('user.device.scan', $device->uuid) }}">
                                            <span class="text-dark">{{ __('Status :') }}</span>
                                            <span class="badge badge-sm {{ badge($device->status)['class'] }}">
                                                {{ $device->status == 1 ? __('Active') : __('Inactive') }}
                                            </span>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @if ($isUserCanDeleteDevice)
                        <div class="modal fade" id="delete-device-{{ $device->uuid }}" tabindex="-1" aria-labelledby="deleteDeviceConfirmation" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form type="POST" action="{{ route('user.device.destroy',$device->uuid) }}" class="ajaxform_instant_reload">
                                        @csrf
                                        @method("DELETE")
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">{{ __('Delete Device') }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            {{ __('Device name') }} <b>"{{ $device->name }}"</b> {{ __('will be deleted.') }}
                                            {{ __('This action will delete the device and all the data associated with it. This action is irreversible. Are you sure you want to proceed?') }}
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">{{ __('Close') }}</button>
                                            <button type="submit" class="btn btn-primary submit-btn">{{ __('Delete') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="alert  bg-gradient-primary text-white"><span
                        class="text-left">{{ __('Opps There Is No Device Linked To This Account....') }}</span></div>
            @endif
        </div>
    </div>
    {{ $devices->links('vendor.pagination.bootstrap-4') }}
    <input type="hidden" id="base_url" value="{{ url('/') }}">
@endsection
@push('js')
    <script src="{{ asset('assets/js/pages/user/device.js') }}"></script>
@endpush
