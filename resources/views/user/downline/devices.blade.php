@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',
[
'title' => __('Downline Devices'),
'buttons'=>[
[
'name'=> __('Downlines'),
'url'=> route('user.downline.index'),
]
]])
@endsection
@section('content')
<div class="row mb-4">
	<div class="col-sm-6">
		<label for="start-date">Select Start Date:</label>
		<input type="date" id="start-date" class="form-control" value="{{ date('Y-01-01') }}">
	</div>
	<div class="col-sm-6">
		<label for="end-date">Select End Date:</label>
		<input type="date" id="end-date" class="form-control" value="{{ date('Y-m-d') }}">
	</div>
</div>
<div class="row">
	<div class="col-sm-3">
		<div class="card">
			<div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Contact') }}</h5>
            <div class="h2 font-weight-bold mb-0" id="total-contact">
							<img src="{{ asset('uploads/loader.gif') }}">
							<div class="total"></div>
						</div>
          </div>
        </div>
      </div>
		</div>
	</div>
	<div class="col-sm-3">
		<div class="card">
			<div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Send Message') }}</h5>
            <div class="h2 font-weight-bold mb-0" id="total-send-message">
							<img src="{{ asset('uploads/loader.gif') }}">
							<div class="total"></div>
						</div>
          </div>
        </div>
      </div>
		</div>
	</div>
	<div class="col-sm-3">
		<div class="card">
			<div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Receive Message') }}</h5>
            <div class="h2 font-weight-bold mb-0" id="total-receive-message">
							<img src="{{ asset('uploads/loader.gif') }}">
							<div class="total"></div>
						</div>
          </div>
        </div>
      </div>
		</div>
	</div>
	<div class="col-sm-3">
		<div class="card">
			<div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Message Percentage') }}</h5>
            <div class="h2 font-weight-bold mb-0" id="message-percentage">
							<img src="{{ asset('uploads/loader.gif') }}">
							<div class="total"></div>
						</div>
          </div>
        </div>
      </div>
		</div>
	</div>
	<div class="col-sm-12">
		<div class="card">
			<!-- Card header -->
			<div class="card-header border-0">
				<h3 class="mb-0">{{ __('Downline Devices') }} - {{ $downline->name }} - {{ $downline->position }}</h3>
				<form action="" class="card-header-form">
					<div class="input-group">
						<input type="text" name="search" value="{{ $request->search ?? '' }}" class="form-control" placeholder="Search......">
						<select class="form-control" name="type">
							<option value="name" @if($type == 'name') selected="" @endif>{{ __('Name') }}</option>
							<option value="phone" @if($type == 'phone') selected="" @endif>{{ __('Phone Number') }}</option>
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
							<th class="col-1">{{ __('Name') }}</th>
							<th class="col-1">{{ __('Phone') }}</th>
							<th class="col-1">{{ __('Status') }}</th>
						</tr>
					</thead>
					@if(count($devices) != 0)
					<tbody class="list">
						@foreach($devices ?? [] as $device)
						<tr>
							<td>
                <a class="text-dark" href="{{ route('user.downline.device.chat',$device->uuid) }}">
									{{ $device->name }}
								</a>
              </td>
							<td>{{ $device->phone }}</td>
							<td>
								<span class="badge badge-{{ $device->status == 1 ? 'success' : 'danger' }}">
									{{ $device->status == 1 ? 'Active' : 'Inactive' }}
								</span>
							</td>
						</tr>
						@endforeach
					</tbody>
					@endif
				</table>
				@if(count($devices) == 0)
				<div class="text-center mt-2">
					<div class="alert  bg-gradient-primary text-white">
						<span class="text-left">{{ __('!Opps no records found') }}</span>
					</div>
				</div>
				@endif
			</div>
			<div class="card-footer py-4">
				{{ $devices->appends($request->all())->links('vendor.pagination.bootstrap-4') }}
			</div>
		</div>
	</div>
</div>
@endsection
@push('js')
<script type="text/javascript" src="{{ asset('assets/js/pages/user/downline-data.js') }}"></script>
@endpush
