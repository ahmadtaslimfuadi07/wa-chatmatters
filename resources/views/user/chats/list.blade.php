@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title' => __('Chat List'),
'buttons'=>[
[
'name'=> __('Devices List'),
'url'=> route('user.device.index'),
]
]])
@endsection
@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/qr-page.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/select2/dist/css/select2.min.css') }}">
@endpush
@section('content')

<div class="card">
	<div class="card-header d-flex flex-row-reverse justify-content-between">
		<div class="server_disconnect text-red mb-0 none" role="alert">
			{{ __('Opps! Server Disconnected 😭') }}
		</div>
		<div class="synchronizing-chat">
			{{ __('Synchronizing Chat History...') }}
		</div>
		<div class="device-status"></div>
	</div>
	<hr class="my-0"/>
	<div class="card-body position-relative">
		<div class="row" style="min-height: 552px;">
			<div class="col-sm-4 d-flex flex-column">
				<div class="form-group">
					<input type="text" data-target=".contact" class="form-control filter-row" placeholder="{{ __('Search....') }}">
				</div>
				<div class="h-100 d-flex flex-column align-items-center justify-content-center qr-area">
					<div class="spinner-grow text-primary" role="status"></div>
					<br>
					<p><strong>{{ __('Loading Contacts.....') }}</strong></p>
				</div>
				<ul class="none list-group list-group-flush list my--3 contact-list mt-5 position-relative"></ul>
			</div>
			<div class="chat-room col-sm-8 d-flex flex-column justify-content-center align-items-center">
				<div class="h-100 loading-messages none">
					<div class="h-100 d-flex flex-column align-items-center justify-content-center">
						<div class="spinner-grow text-primary" role="status"></div>
						<br>
						<p><strong>{{ __('Loading Messages.....') }}</strong></p>
					</div>
				</div>
				<img width="50%" src="{{ asset('assets/img/whatsapp-bg.png') }}" class="wa-image">
				<h4 id="selected-number" class="mb-0 py-3 align-self-start none"></h4>
				<div class="chats d-flex flex-column w-100 overflow-auto" style="flex: 1 1 0">
					<div class="text-center">
						<div class="load-more-spinner none">
							<div class="spinner-grow text-primary" style="width: 24px; height:24px"></div>
						</div>
						<div class="cursor-pointer load-more none">Load More</div>
					</div>
					<div class="message-items d-flex flex-column w-100"></div>
				</div>
				<form method="post" class="ajaxform w-100 none sendble-row" action="{{ route('user.chat.send-message',$device->uuid) }}">
					@csrf
					<div class="form-group mb-2">
						<label class="mb-0">{{ __('Reciver') }}</label>
						<input type="number" readonly="" name="reciver" value="" class="form-control bg-white reciver-number" id="receiver">
					</div>
					<div class="input-group">
						<div class="d-flex flex-fill mr-1">
							<select class="form-control mr-1" name="selecttype" id="select-type">
								<option value="plain-text">{{ __('Plain Text') }}</option>
								@if(count($templates) > 0)
								<option value="template">{{ __('Template') }}</option>
								@endif
							</select>
							@if(count($templates) > 0)
							<select class="form-control none" name="template" id="templates">
								@foreach($templates as $template)
								<option value="{{ $template->id }}">{{ $template->title }}</option>
								@endforeach
							</select>
							@endif
							<input type="text" name="message" class="form-control" id="plain-text" placeholder="Message" aria-label="Recipient's username" aria-describedby="basic-addon2">
						</div>
						<button class="btn btn-outline-success submit-button" type="submit"><i class="fi fi-rs-paper-plane"></i>&nbsp&nbsp {{ __('Sent') }}</button>
					</div>
				</form>				
			</div>
		</div>
	</div>
</div>
@endsection
@push('js')
<script type="text/javascript" src="{{ asset('assets/js/pages/chat/list.js') }}"></script>
@endpush
