@extends('layouts.main.app')
@section('head')
    @include('layouts.main.headersection', [
        'title' => __('Broadcast Logs'),
        'buttons' => [
            [
                'name' => 'Back',
                'url' => route('admin.broadcast.index'),
            ],
        ],
    ])
@endsection
@section('content')
    <div class="row">
        <div class="col">
            <div class="card">
                <!-- Card header -->
                <div class="card-header border-0">
                    <h3 class="mb-0">{{ __('Broadcast Logs') }}</h3>
                </div>
                <!-- Light table -->
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th class="col-1">{{ __('Phone Number') }}</th>
                                <th class="col-1">{{ __('Device Name') }}</th>
                                <th class="col-1">{{ __('Status') }}</th>
                                <th class="col-1">{{ __('Created At') }}</th>
                            </tr>
                        </thead>
                        @if (count($broadcastLogs) != 0)
                            <tbody class="list">
                                @foreach ($broadcastLogs ?? [] as $broadcastLog)
                                    <tr>
                                        <td>{{ $broadcastLog['phoneNumber'] ?? '' }}</td>
                                        <td>{{ $broadcastLog['deviceName'] ?? '' }}</td>
                                        <td>{{ $broadcastLog['status'] ?? '' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($broadcastLog['createdAt'])->format('d-F-Y H:i:s') ?? '' }}
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
                    {{ $broadcastLogs->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
