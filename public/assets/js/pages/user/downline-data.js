"use strict";

const id = window.location.pathname.split('/').pop();
const startDate = $('#start-date').val();
const endDate = $('#end-date').val();
getDownlineData(id, startDate, endDate)

function getDownlineData(id, startDate, endDate) {
  const base_url_ = $('#base_url').val();
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  $.ajax({
    type: 'POST',
    url: base_url_ + '/user/downline-data',
    data: { id, startDate, endDate },
    dataType: 'json',
    success: function (response) {
      $('#total-contact img').addClass('none');
      $('#total-contact .total').html(response.totalContact);
      $('#total-send-message img').addClass('none');
      $('#total-send-message .total').html(response.totalSending);
      $('#total-receive-message img').addClass('none');
      $('#total-receive-message .total').html(response.totalReceive);
      $('#message-percentage img').addClass('none');
      $('#message-percentage .total').html(`${response.percentage.toFixed(2)}%`);
    },
    error: function (xhr) {
      if (xhr.status === 500) {
        NotifyAlert('error', null, 'Oops! Something went wrong');
      } else {
        NotifyAlert('error', null, xhr.responseJSON.message);
      }
    }
  });
}

$("#start-date, #end-date").on("change", function () {
  const startDate = $("#start-date").val();
  const endDate = $("#end-date").val();
  $('#total-contact img').removeClass('none');
  $('#total-contact .total').empty();
  $('#total-send-message img').removeClass('none');
  $('#total-send-message .total').empty();
  $('#total-receive-message img').removeClass('none');
  $('#total-receive-message .total').empty();
  $('#message-percentage img').removeClass('none');
  $('#message-percentage .total').empty();
  getDownlineData(id, startDate, endDate)
});