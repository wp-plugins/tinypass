function tinypass_addPriceOption(){
	var count = jQuery(".tinypass_price_options_form:visible").size();
	if(count <= 3){
		var opt = count+1;
		jQuery(".option_form" + opt).show('fast');
		jQuery(".option_form" + opt).find("input:hidden").val("1");
	}
}

function tinypass_removePriceOption(){
	var count = jQuery(".tinypass_price_options_form:visible").size();
	if(count > 1){
		var opt = count;
		jQuery(".option_form" + opt).hide('fast');
		jQuery(".option_form" + opt).find("input:hidden").val("0");
	}
}

function tp_showTinyPassPopup(type, termId){

	if(termId){
		data = 'tagPopup=t&action=tp_showEditPopup&tp_type=' + type + "&term_id=" + termId;
	}else{
		var data = jQuery('form').serialize();
		data += '&action=tp_showEditPopup&tp_type=' + type;
	}

	jQuery.post(ajaxurl, data, function(response) {
		jQuery("#tp_dialog").html(response);
		jQuery("#tp_dialog").dialog({
			minWidth:520
		});
		tinypass_initPopup();
	});

}

function tp_deleteTagOption(termId){
	data = 'tagPopup=t&action=tp_deleteTagOption&tp_type=tag&term_id=' + termId;

	jQuery.post(ajaxurl, data, function(response) {
		jQuery("#tp_hidden_options").html(response);
		jQuery("#tp_dialog").dialog('close');
	});
}

function tp_doError(fieldName, msg){
	jQuery("#tp-error").html(msg);
}


function tp_saveTinyPassPopup(){
	var data = jQuery('#tp_dialog *').serialize();
	data += '&action=tp_saveEditPopup';

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(response) {
		if(response.indexOf("var a;") >= 0)
			eval(response);
		else{
			jQuery("#tp_hidden_options").html(response);
			jQuery("#tp_dialog").dialog('close');
		}
	});


}

function tp_closeTinyPassPopup(){
	jQuery("#tp_dialog").dialog('close');
}

function tinypass_initPopup(){
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
}