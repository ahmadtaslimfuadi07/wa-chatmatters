@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',
[
'title' => __('Downline Chats'),
'buttons'=>[
[
'name'=> __('Downline Devices'),
'url'=> route('user.downline.device',$selectedDownlineUserId),
]
]])
@endsection
@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/qr-page.css') }}">
@endpush
@section('content')
<div class="card">
	<div class="card-header d-flex flex-row-reverse justify-content-between">
		<div class="server-disconnect text-red mb-0 none" role="alert">
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
				<div class="chats d-flex flex-column w-100 overflow-auto" style="max-height: 500px;">
					<div class="text-center">
						<div class="load-more-spinner none">
							<div class="spinner-grow text-primary" style="width: 24px; height:24px"></div>
						</div>
						<div class="cursor-pointer load-more none">Load More</div>
					</div>
					<div class="message-items d-flex flex-column w-100"></div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
@push('js')
<script type="text/javascript" src="{{ asset('assets/js/pages/user/downline-chats.js') }}"></script>
@endpush
