@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Report')])
@endsection
@section('content')
<div class="row">
    <div class="col">
        <div class="card">
            <!-- Card header -->
            <div class="card-header border-0 d-flex flex-column align-items-start" style="gap: 16px;">
                <h3 class="mb-0">{{ __('Report') }}</h3>
                <form class="w-100" method="GET" action="{{ route('user.report.index') }}" id="reportForm">
                    <div class="input-group d-flex" style="gap: 24px;">
                        <div class="from-group row flex-grow-1">
                            <label class="col-lg-12 mb-0">{{ __('Start Date') }}</label>
                            <div class="col-lg-12">
                                <input class="form-control" type="date" name="startDate" placeholder="Start Date"
                                    value="{{ request('startDate') }}" required>
                            </div>
                        </div>
                        <div class="from-group row flex-grow-1">
                            <label class="col-lg-12 mb-0">{{ __('End Date') }}</label>
                            <div class="col-lg-12">
                                <input class="form-control" type="date" name="endDate" placeholder="End Date"
                                    value="{{ request('endDate') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column mt-3" style="gap: 12px;">
                        <button class="btn btn-block btn-neutral" type="submit" id="searchReportBtn">{{ __('Search') }}</button>
                        <button type="button" class="btn btn-outline-primary" id="exportReportBtn">{{ __('Export CSV') }}</button>
                    </div>
                </form>
            </div>
            <!-- Light table -->
            <div class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th class="col-1">{{ __('Account/Downline Name') }}</th>
                            <th class="col-1">{{ __('Account/Downline Device') }}</th>
                            <th class="col-1">{{ __('Total Days') }}</th>
                            <th class="col-1">{{ __('Total Contacts') }}</th>
                            <th class="col-1">{{ __('Send Rate') }}</th>
                            <th class="col-1">{{ __('Sales Initiated Send Rate') }}</th>
                            <th class="col-1">{{ __('Customer Initiated Send Rate') }}</th>
                            <th class="col-1">{{ __('Reply Rate') }}</th>
                            <th class="col-1">{{ __('Average Reply Time') }}</th>
                        </tr>
                    </thead>
                    @if(count($reports) > 0)
                    <tbody class="list">
                        @foreach($reports as $report)
                        <tr>
                            <td>{{ $report->user }}</td>
                            <td>
                                <div>{{ $report->name }}</div>
                                <div>{{ $report->phone }}</div>
                            </td>
                            <td>{{ $report->totDays }}</td>
                            <td>{{ $report->totContacts }}</td>
                            <td>
                                <div>{{ $report->totSend }}</div>
                                <hr class="my-1">
                                <div>{{ $report->sendPercent }}%</div>
                            </td>
                            <td>
                                <div>{{ $report->bySales }}</div>
                                <hr class="my-1">
                                <div>{{ $report->sendFirstSalesPercent }}%</div>
                            </td>
                            <td>
                                <div>{{ $report->byCustomer }}</div>
                                <hr class="my-1">
                                <div>{{ $report->sendFirstCustomerPercent }}%</div>
                            </td>
                            <td>
                                <div>{{ $report->totReply }}</div>
                                <hr class="my-1">
                                <div>{{ $report->replyPercent }}%</div>
                            </td>
                            @php
                                $time = \Carbon\Carbon::createFromTimestampUTC(($report->minuteReplyPercent / 100) * 60);
                            @endphp
                            <td>{{ $time->hour }}h {{ $time->minute }}m {{ $time->second }}s</td>
                        </tr>
                        @endforeach
                    </tbody>
                    @endif
                </table>
                @if(count($reports) == 0 && request('startDate') && request('endDate'))
                <div class="text-center mt-2 px-2">
                    <div class="alert  bg-gradient-primary text-white">
                        <span class="text-left">{{ __('There are no reports for the selected date range!') }}</span>
                    </div>
                </div>
                @endif
                @if(count($reports) == 0 && !request('startDate') && !request('endDate'))
                <div class="text-center mt-2 px-2">
                    <div class="alert  bg-gradient-primary text-white">
                        <span class="text-left">{{ __('Please select date range to see the report!') }}</span>
                    </div>
                </div>
                @endif
            </div>
            @if(count($reports) > 0)
            <div class="card-footer py-4">
                {{ $reports->appends($request->all())->links('vendor.pagination.bootstrap-4') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/pages/user/report.js') }}"></script>
@endpush
