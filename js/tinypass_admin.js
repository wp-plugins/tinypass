var tinypass = {

	addPriceOption: function(){
		var count = jQuery(".option_form:visible", scope).size();
		if(count <= 3){
			var opt = count+1;
			if(typeof scope == 'undefined')
				scope = null;
			jQuery("#po_en" + opt, scope).val(1);
			jQuery(".option_form" + opt, scope).show('fast');
			jQuery(".option_form" + opt, scope).find("input, select").removeAttr("disabled");
		}
	},
	removePriceOption: function(){
		var count = jQuery(".option_form:visible", scope).size();
		if(count > 1){
			var opt = count;
			if(typeof scope == 'undefined')
				scope = null;
			jQuery("#po_en" + opt, scope).val(0);
			jQuery(".option_form" + opt, scope).hide('fast');
			jQuery(".option_form" + opt, scope).find("input, select").attr("disabled", "disabled");
		}
	},

	showTinyPassPopup: function(type, termId){

		var self = this;

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
			self.initPopup();
		});

	},
	deleteTagOption:function(termId){
		data = 'tagPopup=t&action=tp_deleteTagOption&tp_type=tag&term_id=' + termId;

		data += "&tinypass_nonce=" + jQuery("#tinypass_nonce").val();

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#tp_hidden_options").html(response);
			jQuery("#tp_dialog").dialog('close');
		});
	},

	doError:function(fieldName, msg){
		jQuery("#tp-error").append("<p>" + msg + "</p>");
		jQuery('*[name*="'+fieldName+'"]').addClass("error");
	},

	saveTinyPassPopup:function(){
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


	},

	closeTinyPassPopup:function(){
		jQuery("#tp_dialog").dialog('close');
	},

	showMeteredOptions: function(elem){
		var elem = jQuery(elem);
		var type = elem.val()

		jQuery(".tp-metered-options").hide();
		jQuery(".tp-metered-options :input").attr('disabled', 'disabled')
		jQuery("#tp-metered-" + type).show();
		jQuery("#tp-metered-" + type + " :input").removeAttr('disabled');

		this.log("Setting type:" + type);

	},

	initPopup:function(){
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
	},

	log:function(msg){
		if(console && console.log)
			console.log(msg);
	}
}