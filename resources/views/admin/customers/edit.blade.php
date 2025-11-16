@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'=> __('Edit User'),
'buttons'=>[
    [
        'name'=>__('Back'),
        'url'=>route('admin.customer.index'),
    ]
]

])
@endsection
@section('content')
<div class="row ">
	<div class="col-lg-5 mt-5">
        <strong>{{ __('Edit User') }}</strong>
        <p>{{ __('Edit user profile information') }}</p>
    </div>
    <div class="col-lg-7 mt-5">
        <form class="ajaxform_instant_reload" action="{{ route('admin.customer.update',$customer->id) }}">
        	@csrf
        	@method('PUT')
        	<div class="card">
            <div class="card-body">
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Name') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="name" required="" class="form-control" value="{{ $customer->name }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Email') }}</label>
                    <div class="col-lg-12">
                        <input type="email" name="email" required="" class="form-control" value="{{ $customer->email }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Phone') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" required>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Address') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="address"  class="form-control" value="{{ $customer->address }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Position') }}</label>
                    <div class="col-lg-12">
                        <select id="position" class="form-control" name="position" required>
                            <option value="">Select position</option>
                            @foreach($positions as $position)
                            <option value="{{ $position }}" {{ $customer->position == $position ? 'selected' : '' }}>{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>   
                <div id="superior-field" class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Superior') }}</label>
                    <div class="col-lg-12">
                        <select id="superior" class="form-control" name="superior" required>
                            <option value="">Select superior</option>
                            @foreach($superiors as $superior)
                            <option value="{{ $superior->id }}" {{ $currentSuperiorId == $superior->id ? 'selected' : '' }}>{{ $superior->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Max User Device') }}</label>
                    <div class="col-lg-12">
                        <input type="number" name="max_device" min="1" class="form-control" value="{{ $customer->max_device }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Allow Delete Device') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="allow_delete_device">
                       	 <option value="1" {{ $customer->allow_delete_device == 1 ? 'selected' : '' }}>{{ __('Allow') }}</option>
                       	 <option value="0" {{ $customer->allow_delete_device == 0 ? 'selected' : '' }}>{{ __('Disallow') }}</option>
                       </select>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Allow Broadcast') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="allow_broadcast">
                       	 <option value="1" {{ $customer->allow_broadcast == 1 ? 'selected' : '' }}>{{ __('Allow') }}</option>
                       	 <option value="0" {{ $customer->allow_broadcast == 0 ? 'selected' : '' }}>{{ __('Disallow') }}</option>
                       </select>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Expired At') }}</label>
                    <div class="col-lg-12">
                        <input type="date" name="will_expire" class="form-control" value="{{ $customer->will_expire }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('New Password') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="password"  class="form-control" value="">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Status') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="status">
                       	 <option value="1" {{ $customer->status == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                       	 <option value="0" {{ $customer->status == 0 ? 'selected' : '' }}>{{ __('Deactive') }}</option>
                       </select>
                    </div>
                </div>               
                 <div class="from-group row mt-3">
                    <div class="col-lg-12">
                       <button class="btn btn-neutral submit-button btn-sm float-left"> {{ __('Update') }}</button>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>
<input type="hidden" id="base_url" value="{{ url('/') }}">
@endsection
@push('js')
<script type="text/javascript" src="{{ asset('assets/js/pages/admin/customer-create-edit.js') }}"></script>
@endpush