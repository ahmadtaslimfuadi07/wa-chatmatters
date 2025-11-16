"use strict";

const uuid = window.location.pathname.split('/').pop();
const whatsappicon = $('#base_url').val() + '/assets/img/whatsapp.png';
let isDeviceInactive = false;
const limit = 10;
checkSession();
getChatList();

function checkSession() {
	const base_url_ = $('#base_url').val();
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	$.ajax({
		type: 'POST',
		url: base_url_ + `/user/check-session/${uuid}`,
		dataType: 'json',
		success: function (response) {
			if (response.connected === true) {
				isDeviceInactive = false;
				$('.server_disconnect').remove();
				$('.card-header').removeClass('flex-row-reverse').addClass('flex-row');
				NotifyAlert('success', null, response.message);
			}
			else {
				isDeviceInactive = true;
				NotifyAlert('error', null, 'Device is not ready for sending message');
			}
		},
		error: function (xhr) {
			if (xhr.status == 500) {
				$('.server_disconnect').show();
				isDeviceInactive = true;
			}
		}
	});
}

function getStatus(status, type = 'list') {
	const spanClass = type == 'list' ? 'mr-1' : 'ml-1';
	if (status == 2 || status == "PENDING") {
		return `<span class="${spanClass}" style="color: grey;" title="Sent"><i class="fas fa-check"></i></span>`;
	}
	if (status == 3) {
		return `<span class="${spanClass}" style="color: grey;" title="Delivered"><i class="fas fa-check"></i><i class="fas fa-check" style="margin-left: -6px;"></i></span>`;
	}
	if (status == 4) {
		return `<span class="${spanClass}" style="color: #53bdeb;" title="Read"><i class="fas fa-check"></i><i class="fas fa-check" style="margin-left: -6px;"></i></span>`;
	}

	return '';
}

function getChatList() {
	const base_url_ = $('#base_url').val();
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	$.ajax({
		type: 'POST',
		url: base_url_ + `/user/get-chats/${uuid}`,
		dataType: 'json',
		success: function (response) {
			$('.qr-area').remove();
			$('.contact-list').removeClass('none');

			if (response.status) {
				$('.device-status').addClass('text-green');
				$('.device-status').html('Device Connected!');
			} else {
				$('.device-status').addClass('text-red');
				$('.device-status').html('Device Disconnected!');
				$('.synchronizing-chat').addClass('none');
			}

			if (response.sync) {
				$('.synchronizing-chat').addClass('none');
			}

			const deviceName = `<h4 class="mb-0">${response.device_name} - +${response.phone}</h4>`;
			$('.card-header').prepend(deviceName);

			$.each(response.chats, function (_key, item) {
				if (item.user_id !== 114 && (item.number === response.phone || item.number === '0')) return;
				const time = formatTimestamp(item.timestamp);
				let message = item.message
				if (item?.file) {
					message = `<i class="fas fa-camera"></i> ${item.message || 'Foto'}`
				}
				const html = `
				<li class="list-group-item px-0 contact text-muted wa-link link" data-number="${item.number ?? item.lid}">
					<div class="row align-items-center mx-0">
						<div class="col-2 px-0">
						<img class="w-100 avatar rounded-circle wa-link" src="${whatsappicon}" style="height: 100%">
						</div>
						<div class="col-10 px-1">
						<div class="d-flex justify-content-between">
							<h4 class="mb-0">${item.name ?? item.number ?? item.lid}</h4>
							<div class="d-flex align-items-center" style="gap: 2px;">
								<h5 class="mb-0 time">${time}</h5>
								${item.fromMe === "false" && item.status != 4 ? `<span style="width: 10px;height: 10px;background-color: #2aa81a;border-radius: 50%;"></span>` : ""}
							</div>
						</div>
						<div class="mb-0 line-clamp-2 message">${item.fromMe === "true" ? getStatus(item.status) : ""}${message ?? ''}</div>
						</div>
					</div>
				</li>`;

				$('.contact-list').append(html);
			});
		},
		error: function () {
			$('.qr-area').remove();
		}
	});
}

function getChatByPhoneNumber(phoneNumber, limit, page = 0, lastId = '') {
	const base_url_ = $('#base_url').val();
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	$.ajax({
		type: 'POST',
		url: base_url_ + `/user/get-chat-details/${uuid}`,
		data: { phoneNumber, limit, page, lastId },
		dataType: 'json',
		success: function (response) {
			$('.chat-room').removeClass('justify-content-center');
			$('.chat-room').addClass('justify-content-end');
			$('.loading-messages').addClass('none');
			if (page) {
				$('.load-more-spinner').addClass('none');
			}
			$.each(response.chats, function (_key, item) {
				if (item === null) return;

				const divClass = JSON.parse(item.fromMe) ? 'align-self-end bg-gray' : 'align-self-start bg-light';
				let html = ''
				if (item?.file) {
					html = `
					<div id="${item.id}" class="${divClass} rounded mb-2 p-2 message-item" style="width: fit-content;max-width: 80%;">
						<div style="width: min-content">
							<img class="rounded" src="${item.file}" style="max-width: 200px" />
							<p>${item.message ?? ''}</p>
						</div>
						<div class="text-right">${formatTimestamp(item.timestamp)}${item.fromMe === "true" ? getStatus(item.status, 'detail') : ""}</div>
					</div>`;
				} else {
					html = `
					<div id="${item.id}" class="${divClass} rounded mb-2 p-2 message-item" style="width: fit-content;max-width: 80%;">
						<div class="text-justify" style="white-space: pre-wrap;">${item.message ?? ' '}</div>
						<div class="text-right">${formatTimestamp(item.timestamp)}${item.fromMe === "true" ? getStatus(item.status, 'detail') : ""}</div>
					</div>`;
				}

				$('.message-items').prepend(html);
			});

			if (response.chats.length === limit) {
				$('.load-more').removeClass('none');
			} else {
				$('.load-more').addClass('none');
			}
		},
		error: function () { }
	});
}


$(document).on('click', '.wa-link', function () {
	const phone = $(this).data('number');
	$('#plain-text').val('');
	$('#plain-text').attr('readonly', false);

	$('.wa-image').remove();
	$('.message-item').remove();
	$('.load-more').addClass('none');
	$('#selected-number').addClass('none');
	$('.loading-messages').removeClass('none');
	getChatByPhoneNumber(phone, limit);

	$('.contact').removeClass('active');
	$(this).addClass('active');
	$('.chat-list').html(phone);
	if (!isDeviceInactive) {
		$('.sendble-row').removeClass('none');
	}
	$('.reciver-number').val(phone);

});

$(document).on('click', '.load-more', function () {
	const phone = $('.reciver-number').val();
	const page = $('.message-item').length / limit;
	const lastId = $('.message-item').first().attr("id");
	$('.load-more-spinner').removeClass('none');
	getChatByPhoneNumber(phone, limit, page, lastId);
});

$(document).on('change', '#select-type', function () {
	const type = $(this).val();

	if (type == 'plain-text') {
		$('#plain-text').show();
		$('#templates').hide();
	}
	else {
		$('#plain-text').hide();
		$('#templates').show();
	}
});

$(document).on('click', '.submit-button', function () {
	$('#plain-text').attr('readonly', true);
});

function successCallBack(data) {
	const receiver = data.get('reciver');
	const message = data.get('message');
	const time = formatTimestamp(Math.round(new Date().getTime() / 1000));
	const currenctReceiver = $('#receiver').val();
	const contactList = $('.contact-list');
	const receiverContactElement = $(`.contact[data-number=${receiver}]`);
	receiverContactElement.find('.message').html(message);
	receiverContactElement.find('.time').html(time);
	receiverContactElement.prependTo(contactList);

	if (receiver === currenctReceiver) {
		const html = `
		<div class="align-self-end bg-gray rounded mb-2 p-2 message-item" style="width: fit-content;max-width: 80%;">
			<div class="text-justify" style="white-space: pre-wrap;">${message}</div>
			<div class="text-right">${time}</div>
		</div>`;
		$('.message-items').append(html);
		$('#plain-text').val('');
		$('#plain-text').attr('readonly', false);
	}
}

function sortByKey(array, key) {
	return array.sort(function (a, b) {
		var x = a[key]; var y = b[key];
		return ((x > y) ? -1 : ((x < y) ? 1 : 0));
	});
}

function formatTimestamp(timestamp) {
	// Check if the timestamp is null or undefined
	if (timestamp === null || timestamp === undefined) {
		return ''; // Return an empty string or any default value you prefer
	}

	const now = new Date();
	const targetDate = new Date(timestamp * 1000); // Convert to milliseconds

	const hours = targetDate.getHours().toString().padStart(2, '0');
	const minutes = targetDate.getMinutes().toString().padStart(2, '0');

	// Check if the timestamp is today and format it as 24-hour time
	if (targetDate.toDateString() === now.toDateString()) {
		return `${hours}:${minutes}`;
	}

	// Check if the timestamp is yesterday and show 'Yesterday'
	const yesterday = new Date(now);
	yesterday.setDate(now.getDate() - 1);
	if (targetDate.toDateString() === yesterday.toDateString()) {
		return `Yesterday`;
	}

	// Check if the timestamp is within the last week and show the day name
	const oneWeekAgo = new Date(now);
	oneWeekAgo.setDate(now.getDate() - 7);
	if (targetDate >= oneWeekAgo) {
		const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		return `${daysOfWeek[targetDate.getDay()]}`;
	}

	// For all other cases, show date in dd/MM/yy format
	const day = targetDate.getDate().toString().padStart(2, '0');
	const month = (targetDate.getMonth() + 1).toString().padStart(2, '0');
	const year = targetDate.getFullYear().toString().substr(-2);
	return `${day}/${month}/${year}`;
}

function padTwoDigits(num) {
	return num.toString().padStart(2, "0");
}


function dateInYyyyMmDdHhMmSs(date, dateDiveder = "-") {
	return (
		[
			padTwoDigits(date.getDate()),
			padTwoDigits(date.getMonth() + 1),
			date.getFullYear(),
		].join(dateDiveder) +
		" " +
		[
			padTwoDigits(date.getHours()),
			padTwoDigits(date.getMinutes()),
		].join(":")
	);
}
