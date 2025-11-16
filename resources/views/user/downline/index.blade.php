@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Downlines')])
@endsection
@section('content')
<div class="row">
	<div class="col">
		<div class="card">
			<!-- Card header -->
			<div class="card-header border-0">
				<h3 class="mb-0">{{ __('Downlines') }}</h3>
				<form action="" class="card-header-form">
					<div class="input-group">
						<input type="text" name="search" value="{{ $request->search ?? '' }}" class="form-control" placeholder="Search......">
						<select class="form-control" name="type">
							<option value="name" @if($type == 'name') selected="" @endif>{{ __('Name') }}</option>
							<option value="email" @if($type == 'email') selected="" @endif>{{ __('Email') }}</option>
							<option value="phone" @if($type == 'phone') selected="" @endif>{{ __('Phone Number') }}</option>
							<option value="uuid" @if($type == 'position') selected="" @endif>{{ __('Position') }}</option>
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
							<th class="col-1">{{ __('Email') }}</th>
							<th class="col-1">{{ __('Phone') }}</th>
							<th class="col-1">{{ __('Position') }}</th>
							<th class="col-1">{{ __('Active Devices') }}</th>
							<th class="col-1">{{ __('Max Device') }}</th>
							<th class="col-1">{{ __('Will Expire') }}</th>
							<th class="col-1">{{ __('Status') }}</th>
							<th class="col-1">{{ __('Created At') }}</th>
							<th class="col-1">{{ __('Updated At') }}</th>
						</tr>
					</thead>
					@if(count($downlineUsers) != 0)
					<tbody class="list">
						@foreach($downlineUsers ?? [] as $downlineUser)
						<tr>
							<td>
                <a href="{{ route('user.downline.device',$downlineUser->uuid) }}" class="text-dark">
									{{ $downlineUser->name }}
								</a>
              </td>
							<td>{{ $downlineUser->email }}</td>
							<td>{{ $downlineUser->phone }}</td>
							<td>{{ $downlineUser->position }}</td>
							<td>{{ $downlineUser->active_devices_count ?? 0 }}</td>
							<td>{{ $downlineUser->max_device }}</td>
							<td>{{ \Carbon\Carbon::parse($downlineUser->will_expire)->format('d-F-Y') }}</td>
							<td>
								<span class="badge badge-{{ $downlineUser->status == 1 ? 'success' : 'danger' }}">
									{{ $downlineUser->status == 1 ? 'Active' : 'Inactive' }}
								</span>
							</td>
							<td>
								{{ \Carbon\Carbon::parse($downlineUser->created_at)->format('d-F-Y') }}
							</td>
							<td>
								{{ \Carbon\Carbon::parse($downlineUser->updated_at)->format('d-F-Y') }}
							</td>
						</tr>
						@endforeach
					</tbody>
					@endif
				</table>
				@if(count($downlineUsers) == 0)
				<div class="text-center mt-2">
					<div class="alert  bg-gradient-primary text-white">
						<span class="text-left">{{ __('!Opps no records found') }}</span>
					</div>
				</div>
				@endif
			</div>
			<div class="card-footer py-4">
				{{ $downlineUsers->appends($request->all())->links('vendor.pagination.bootstrap-4') }}
			</div>
		</div>
	</div>
</div>
@endsection