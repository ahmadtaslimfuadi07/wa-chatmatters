@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['buttons'=>[
	[
		'name'=>'Back',
		'url'=> route('user.webhook.index'),
	]
]])
@endsection
@section('content')
<div class="row justify-content-center">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h4>{{ __('Create Webhook') }}</h4>
			</div>
			<div class="card-body">
				<form method="POST" class="ajaxform_reset_form" action="{{ route('user.webhook.store') }}">
					@csrf
				<div class="form-group row mb-4">
					<label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('URL') }}</label>
					<div class="col-sm-12 col-md-7">
						<input type="text" name="url" placeholder="https://example.com/" maxlength="150" class="form-control">
					</div>
				</div>
				<div class="form-group row mb-4">
					<label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Status') }}</label>
					<div class="col-sm-12 col-md-7">
						<select name="status" class="form-control">
                            <option value=1>{{ __('Active') }}</option>
                            <option value=2>{{ __('Inactive') }}</option>
						</select>
					</div>
				</div>
				<div class="form-group row mb-4">
					<label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Select Devices') }}</label>
					<div class="col-sm-12 col-md-7">
						<select name="device_id" class="form-control">
							@foreach($devices as $device)
							<option value="{{ $device->id }}">{{ $device->name }}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="form-group row mb-4">
					<label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
					<div class="col-sm-12 col-md-7">
						<button type="submit" class="btn btn-outline-primary submit-btn">{{ __('Create Now') }}</button>
					</div>
				</div>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection
