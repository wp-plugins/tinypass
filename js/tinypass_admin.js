function tinypass_hidePostOptions(elem){
	if(jQuery(elem).attr("checked")){
		jQuery("#tinypass_post_options_form").show();
	}else{
		jQuery("#tinypass_post_options_form").hide();
	}
}

jQuery(function(){
	jQuery.datepicker.setDefaults();
	jQuery.timepicker.setDefaults( {
		timeOnlyTitle: 'Choose Time',
		timeText: 'Time',
		hourText: 'Hour',
		minuteText: 'Minute',
		secondText: 'Second',
		currentText: 'Now',
		closeText: 'Done'
	}
	);
	jQuery('.tinypass-datetimepicker').datetimepicker({
		dateFormat: 'yy-mm-dd'
	});
});


