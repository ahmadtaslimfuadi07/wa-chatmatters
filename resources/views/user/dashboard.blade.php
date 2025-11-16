@extends('layouts.main.app')
@section('head')
@if ($countUserDevice < $maxUserDevice)
@include('layouts.main.headersection',['title'=> __('Dashboard'),'buttons'=>[
  [
    'name'=>'<i class="fa fa-plus"></i>&nbsp'.__('Create Device'),
    'url'=> route('user.device.create'),
  ],
  [
    'name'=>'<i class="fi fi-rs-paper-plane"></i>&nbsp'.__('Sent a message'),
    'url'=> url('/user/sent-text-message'),
  ],
]])
@else
@include('layouts.main.headersection',['title'=> __('Dashboard'),'buttons'=>[
  [
    'name'=>'<i class="fi fi-rs-paper-plane"></i>&nbsp'.__('Sent a message'),
    'url'=> url('/user/sent-text-message'),
  ],
]])
@endif
@endsection
@section('content')

{{-- Stats Cards --}}
<div class="row">
  <div class="col-xl-4 col-md-4 col-sm-12">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Devices') }}</h5>
            <span class="h2 font-weight-bold mb-0" id="total-device"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
             <i class="fas fa-server"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-4 col-sm-12">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Contacts') }}</h5>
            <span class="h2 font-weight-bold mb-0" id="total-contacts"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
              <i class="ni ni-collection"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-4 col-sm-12">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Messages') }}</h5>
            <span class="h2 font-weight-bold mb-0 mt-1" id="total-messages"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
              <i class="ni ni-spaceship"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Global Period Control --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body py-3">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">{{ __('Analytics Period') }}</h5>
            <small class="text-muted">{{ __('Select time range for all charts below') }}</small>
          </div>
          <div class="col-auto">
            <div class="row align-items-center">
              <!-- Custom Date Range (on the left) -->
              <div class="col-auto" id="custom-date-range" style="display: none;">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <div class="d-flex align-items-center">
                    <label class="form-control-label mb-0 mr-2">{{ __('From:') }}</label>
                    <input type="datetime-local" id="start-date" class="form-control form-control-sm" style="width: 180px;" />
                  </div>
                  <div class="d-flex align-items-center">
                    <label class="form-control-label mb-0 mr-2">{{ __('To:') }}</label>
                    <input type="datetime-local" id="end-date" class="form-control form-control-sm" style="width: 180px;" />
                  </div>
                  <button type="button" id="apply-custom-range" class="btn btn-primary btn-sm">
                    <i class="fas fa-check"></i> {{ __('Apply') }}
                  </button>
                </div>
              </div>
              
              <!-- Predefined Period Selector (on the right) -->
              <div class="col-auto">
                <select class="form-control" id="global-period" style="min-width: 140px;">
                  <option value="1">{{ __('Today') }}</option>
                  <option value="7" selected>{{ __('Last 7 Days') }}</option>
                  <option value="14">{{ __('Last 14 Days') }}</option>
                  <option value="30">{{ __('Last 30 Days') }}</option>
                  <option value="90">{{ __('Last 3 Months') }}</option>
                  <option value="custom">{{ __('Custom Range') }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Session Alerts --}}
<div class="row">
 @if(Session::has('success'))
 <div class="col-sm-12">
   <div class="alert bg-gradient-success text-white alert-dismissible fade show success-alert" role="alert">
     <span class="alert-icon"><img src="{{ asset('uploads/firework.png') }}" alt=""></span>
     <span class="alert-text"><strong>{{ __('Congratulations ') }}</strong> {{ Session::get('success') }}</span>
     <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </button>
  </div>
</div>
@endif
 @if(Session::has('saas_error'))
 <div class="col-sm-12">
   <div class="alert bg-gradient-primary text-white alert-dismissible fade show" role="alert">
     <a href="{{ url(Auth::user()->plan_id == null ? '/user/subscription' : '/user/subscription/'.Auth::user()->plan_id) }}">
      <span class="alert-icon"><i class="fi  fi-rs-info text-white"></i></span>
    </a>
    <span class="alert-text">
      <strong>{{ __('!Opps ') }}</strong> 
      <a class="text-white" href="{{ url(Auth::user()->plan_id == null ? '/user/subscription' : '/user/subscription/'.Auth::user()->plan_id) }}">
        {{ Session::get('saas_error') }}
      </a>
    </span>
  </div>
</div>
@endif
</div>

{{-- Charts Section --}}
<div class="row">
  {{-- Messages Transaction Chart --}}
  <div class="col-lg-12">
    <div class="card">
       <div class="card-header bg-transparent">
        <h4 class="card-header-title">{{ __('Messages Transaction') }}</h4>
      </div>
      <div class="card-body">
        <!-- Chart -->
        <div class="chart">
          <!-- Chart wrapper -->
          <canvas id="chart-sales" class="chart-canvas"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Device Performance Overview Chart --}}
  <div class="col-lg-12">
    <div class="card">
       <div class="card-header bg-transparent">
        <h4 class="card-header-title">{{ __('Device Performance Overview') }}</h4>
      </div>
      <div class="card-body" style="padding: 1rem;">
        <!-- Chart -->
        <div class="chart" style="position: relative;">
          <!-- Chart wrapper -->
          <canvas id="chart-device-performance" class="chart-canvas"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="static-data" value="{{ route('user.dashboard.static') }}"> 

@endsection
@push('js')
<script src="{{ asset('assets/vendor/chart.js/dist/chart.min.js') }}"></script>
<script src="{{ asset('assets/plugins/canvas-confetti/confetti.browser.min.js') }}"></script>
@endpush
@push('bottomjs')
<script src="{{ asset('assets/js/pages/user/dashboard.js') }}"></script>
@endpush
