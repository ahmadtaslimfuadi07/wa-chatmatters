@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'=> __('Create User'),
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
        <strong>{{ __('Create User') }}</strong>
        <p>{{ __('Create user profile information') }}</p>
    </div>
    <div class="col-lg-7 mt-5">
        <form method="post" action="{{ route('admin.customer.store') }}" class="ajaxform_instant_reload">
        	@csrf
        	<div class="card">
            <div class="card-body">
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Name') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="name" required="" class="form-control">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Email') }}</label>
                    <div class="col-lg-12">
                        <input type="email" name="email" required="" class="form-control">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Phone') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Position') }}</label>
                    <div class="col-lg-12">
                        <select id="position" class="form-control" name="position" required>
                            <option value="">Select position</option>
                            @foreach($positions as $position)
                            <option value="{{ $position }}">{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>   
                <div id="superior-field" class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Superior') }}</label>
                    <div class="col-lg-12">
                        <select id="superior" class="form-control" name="superior" required>
                        </select>
                    </div>
                </div>           
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Max User Device') }}</label>
                    <div class="col-lg-12">
                        <input type="number" name="max_device" min="1" required="" class="form-control">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Allow Delete Device') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="allow_delete_device" required>
                         <option value="">Select permission</option>
                       	 <option value="1">{{ __('Allow') }}</option>
                       	 <option value="0">{{ __('Disallow') }}</option>
                       </select>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Allow Broadcast') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="allow_broadcast" required>
                         <option value="">Select permission</option>
                       	 <option value="1">{{ __('Allow') }}</option>
                       	 <option value="0">{{ __('Disallow') }}</option>
                       </select>
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Expired At') }}</label>
                    <div class="col-lg-12">
                        <input type="date" name="will_expire" required="" class="form-control">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Password') }}</label>
                    <div class="col-lg-12">
                        <input type="password" name="password" required="" class="form-control">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Confirm Password') }}</label>
                    <div class="col-lg-12">
                        <input type="password" name="password_confirmation" required="" class="form-control">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Status') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="status">
                       	 <option value="1">{{ __('Active') }}</option>
                       	 <option value="0">{{ __('Deactive') }}</option>
                       </select>
                    </div>
                </div>               
                 <div class="from-group row mt-3">
                    <div class="col-lg-12">
                       <button class="btn btn-neutral submit-button btn-sm float-left"> {{ __('Create') }}</button>
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