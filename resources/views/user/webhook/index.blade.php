@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'=>__('Webhook'),
'buttons'=>[
[
'name'=>'<i class="fa fa-plus"></i>&nbsp'.__('Create Webhook'),
'url'=> route('user.webhook.create'),
]
]])
@push('topcss')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/select2/dist/css/select2.min.css') }}">
@endpush
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
                  <h5 class="card-title  text-muted mb-0">{{ __('Total Webhook') }}</h5>
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
                  <h5 class="card-title  text-muted mb-0">{{ __('Active Webhook') }}</h5>
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
                  <h5 class="card-title  text-muted mb-0">{{ __('Inactive Webhook') }}</h5>
                  <p></p>
               </div>
            </div>
         </div>
      </div>
        @if(count($webhook ?? []) == 0)
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <center>
                            <img src="{{ asset('assets/img/404.jpg') }}" height="500">
                            <h3 class="text-center">{{ __('!Opps You Have Not Created Webhook') }}</h3>
                        </center>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12 table-responsive">
                        <table class="table col-12">
                            <thead>
                                <tr>
                                    <th>{{ __('Device') }}</th>
                                    <th>{{ __('Url') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Create At') }}</th>
									<th class="col-2 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @foreach($webhook ?? [] as $webhooks)
                                <tr>
                                    <td>
                                        {{ $webhooks->device->name }}
                                    </td>
                                    <td>
                                        {{ $webhooks->url }}
                                    </td>
                                    <td>
                                        <span class="badge badge-sm {{ badge($webhooks->status)['class'] }}">
                                        {{ $webhooks->status == 1 ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        {{ $webhooks->created_at->format('d F Y') }}
                                    </td>
									<td>
										<div class="btn-group mb-2 float-right">
											<button class="btn btn-neutral btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
												{{ __('Action') }}
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item has-icon edit-webhook" href="#"
												data-action="{{ route('user.webhook.update',$webhooks->id) }}"
												data-url="{{ $webhooks->url }}"
												data-status="{{ $webhooks->status }}"
												data-device_id="{{ $webhooks->device->id ?? '' }}"
												data-toggle="modal"
												data-target="#editModal"
												>
												<i class="ni ni-align-left-2"></i>{{ __('Edit') }}</a>
												<a class="dropdown-item has-icon delete-confirm" href="javascript:void(0)" data-action="{{ route('user.webhook.destroy',$webhooks->id) }}"><i class="fas fa-trash"></i>{{ __('Remove') }}</a>
                                                <a class="dropdown-item has-icon" href="{{ route('user.webhook.status',$webhooks->id) }}">
                                                    @if($webhooks->status == 1)
                                                        <i class="fas fa-toggle-off"></i>
                                                    @else
                                                        <i class="fas fa-toggle-on"></i>
                                                    @endif
                                                    {{ __($webhooks->status == 1 ? 'Inactive' : 'Active' ) }}</a>
											</div>
										</div>
									</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center">{{ $webhook->links('vendor.pagination.bootstrap-4') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
   </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form type="POST" action="" class="edit-modal ajaxform_instant_reload">
				@csrf
				@method('PUT')

				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">{{ __('Edit Webhook') }}</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>{{ __('Url') }}</label>
                        <input type="text" name="url" id="url" placeholder="https://example.com/" maxlength="150" class="form-control" required="">
					</div>
					<div class="form-group">
						<label>{{ __('Status') }}</label>
						<select name="status" id="status" class="form-control">
                            <option value=1>{{ __('Active') }}</option>
                            <option value=2>{{ __('Inactive') }}</option>
						</select>
					</div>
					<div class="form-group">
						<label>{{ __('Select Group') }}</label>
						<select name="device_id" id="device_id" class="form-control">
							@foreach($devices as $device)
							<option value="{{ $device->id }}">{{ $device->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
					<button type="submit" class="btn btn-primary submit-btn">{{ __('Save changes') }}</button>
				</div>
			</form>
		</div>
	</div>
</div>
<input type="hidden" id="base_url" value="{{ url('/') }}">
@endsection
@push('topjs')
<script src="{{ asset('assets/vendor/select2/dist/js/select2.min.js') }}"></script>
@endpush
@push('js')
<script src="{{ asset('assets/js/pages/user/webhook.js') }}"></script>
@endpush
