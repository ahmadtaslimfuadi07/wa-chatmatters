   "use strict";

   $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
   });
    const base_url_ = $('#base_url').val();

   $.ajax({
    type: 'POST',
    /* url: base_url+'/user/webhook-statics', */
       url: base_url_+'/user/webhook-statics',
    dataType: 'json',
    success: function(response) {
        $('#total-device').html(response.total);
        $('#total-inactive').html(response.inActive);
        $('#total-active').html(response.active);
    }
   });


$('.edit-webhook').on('click',function(){
	const url = $(this).data('url');
	const status = $(this).data('status');
	const action = $(this).data('action');
	const device_id = $(this).data('device_id');

	$('#url').val(url);
	$('#status').val(status);
	$('#device_id').val(device_id);
	$('.edit-modal').attr('action',action);

});
