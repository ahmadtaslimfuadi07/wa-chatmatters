@extends('layouts.main.app')
@section('head')
    @include('layouts.main.headersection', [
        'title' => __('Broadcasts'),
        'buttons' =>
            $prerequisite['haveAuthKey'] && $prerequisite['haveAtLeastOneAppKey']
                ? [
                    [
                        'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Create Broadcast'),
                        'url' => route('user.broadcast.create'),
                    ],
                ]
                : [],
    ])
@endsection
@section('content')
    <div class="row">
        <div class="col">
            @if (!$prerequisite['haveAuthKey'] || !$prerequisite['haveAtLeastOneAppKey'])
                <div class="alert">
                    <strong>{{ __('You need to complete the following before creating a broadcast:') }}</strong>
                    <ul class="mb-0">
                        @unless ($prerequisite['haveAuthKey'])
                            <li>
                                {{ __('You must set up an Auth Key.') }}
                                <a href="{{ url('user/auth-key') }}">{{ __('Click Here') }}</a>
                            </li>
                        @endunless
                        @unless ($prerequisite['haveAtLeastOneAppKey'])
                            <li>
                                {{ __('You must have at least one App Key configured.') }}
                                <a href="{{ url('user/apps') }}">{{ __('Click Here') }}</a>
                            </li>
                        @endunless
                    </ul>
                </div>
            @endif
            <div class="card">
                <!-- Card header -->
                <div class="card-header border-0">
                    <h3 class="mb-0">{{ __('Broadcasts') }}</h3>
                    <form action="" class="card-header-form">
                        <div class="input-group">
                            <input type="text" name="search" value="{{ $request->search ?? '' }}" class="form-control"
                                placeholder="Search......">
                            <select class="form-control" name="type">
                                <option value="title" @if ($type == 'title') selected="" @endif>
                                    {{ __('Title') }}
                                </option>
                            </select>
                            <div class="input-group-btn">
                                <button class="btn btn-neutral btn-icon"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Light table -->
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th class="col-1">{{ __('Desc') }}</th>
                                <th class="col-1">{{ __('Interval') }}</th>
                                <th class="col-1">{{ __('Limit Contact per Interval') }}</th>
                                <th class="col-1">{{ __('Start Date') }}</th>
                                <th class="col-1">{{ __('Status') }}</th>
                                <th class="col-1">{{ __('Sent Count') }}</th>
                                <th class="col-1">{{ __('Created At') }}</th>
                                <th class="col-1">{{ __('Updated At') }}</th>
                                <th class="col-1">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        @if (count($broadcasts) != 0)
                            <tbody class="list">
                                @foreach ($broadcasts ?? [] as $broadcast)
                                    <tr>
                                        <td class="text-wrap">{{ $broadcast['desc'] ?? '' }}</td>
                                        <td>{{ $broadcast['interval'] ?? '' }} Seconds</td>
                                        <td>{{ $broadcast['limitContact'] ?? '' }}</td>
                                        <td>
                                            @if (isset($broadcast['start']))
                                                {{ \Carbon\Carbon::parse($broadcast['start'])->format('d-F-Y H:i:s') }}
                                            @endif
                                        </td>
                                        <td>{{ $broadcast['status'] ?? '' }}</td>
                                        <td>{{ $broadcast['sentCount'] ?? '' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($broadcast['createdAt'])->format('d-F-Y H:i:s') ?? '' }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($broadcast['updatedAt'])->format('d-F-Y H:i:s') ?? '' }}
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a class="btn btn-sm btn-icon-only text-light" href="#" role="button"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                    <a class="dropdown-item"
                                                        href="{{ route('user.broadcast.show', $broadcast['_id']) }}">{{ __('Logs') }}</a>
                                                    <a class="dropdown-item"
                                                        href="{{ route('user.broadcast.edit', $broadcast['_id']) }}">{{ __('Edit') }}</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @else
                            <tbody class="list">
                                <tr>
                                    <td class="alert bg-gradient-primary text-center text-white" colspan="12">
                                        <span class="text-left">{{ __('!Opps no records found') }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                </div>
                <div class="card-footer py-4">
                    {{ $broadcasts->appends($request->all())->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
