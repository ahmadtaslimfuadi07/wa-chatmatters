"use strict";


$('.edit-contact').on('click',function(){
	const username = $(this).data('name');
	const phone = $(this).data('phone');
	const lid = $(this).data('lid');
	const action = $(this).data('action');
	const device = $(this).data('deviceid');

	$('#user_name').val(username);
	$('#user_phone').val(phone);
	$('#user_lid').val(lid);
	$('#device-list').val(device);
	$('.edit-modal').attr('action',action);
});

$('.save-template').on('change',function(){
	
	
	if ($(this).is(':checked')) {
		$('.receivers').hide();
		$('.bulk_send_form').addClass('ajaxform_instant_reload');
		$('.bulk_send_form').removeClass('ajaxform');
	}else{
		
		$('.bulk_send_form').removeClass('ajaxform_instant_reload');
		$('.bulk_send_form').addClass('ajaxform');
		$('.receivers').show()
	}  

});
