<?php

add_action('wp_ajax_tp_showEditPopup', 'ajax_tp_showEditPopup');
add_action('wp_ajax_tp_saveEditPopup', 'ajax_tp_saveEditPopup');
add_action('wp_ajax_tp_deleteTagOption', 'ajax_tp_deleteTagOption');
wp_enqueue_script("jquery-ui");
wp_enqueue_script('jquery-ui-dialog');

function ajax_tp_deleteTagOption() {
	global $wpdb;

	if(!current_user_can('edit_posts')) die();

	$termId = $_POST['term_id'];
	$wpdb->query("delete from $wpdb->tinypass_ref where term_id = $termId ");
	tinypass_admin_tags_body();
	die();
}

function ajax_tp_showEditPopup() {
	if(!current_user_can('edit_posts')) die();
	$postID = $_POST['post_ID'];
	$type = $_POST['tp_type'];
	$termId = $_POST['term_id'];

	if($type == 'tag') {
		$meta = tinypass_fetch_tag_meta($termId);
	}else {
		$type = 'page';
		$meta = get_post_meta($postID, 'tinypass', true);
	}

	tinypass_popup_form($meta, $postID, $type);
	die();
}

/**
 * Save the Popup Form
 *
 * 	Tags will be saved directly
 *
 */
function ajax_tp_saveEditPopup() {
	if(!current_user_can('edit_posts')) die();

	$fields = array('po_ap', 'po_cap', 'po_en', 'po_et', 'po_p', 'po_st', 'po_type');
	foreach($fields as $f){
		if(isset($_POST['tinypass']['po_en2']) == false || $_POST['tinypass']['po_en2'] == 0){
			unset($_POST['tinypass'][$f . '2']);
		}
		if(isset($_POST['tinypass']['po_en3']) == false || $_POST['tinypass']['po_en3'] == 0){
			unset($_POST['tinypass'][$f . '3']);
		}
	}

	$values = $_POST['tinypass'];
	$tp_type = $values['tp_type'];

	$errors = tinypass_validate_popup_values($values, $tp_type);
	if($errors) {
		echo "var a;";
		foreach($errors as $field => $msg) {
			echo "tp_doError('$field', '$msg');";
		}
		die();
	}

	if(isset($tp_type) && $tp_type == 'tag') {
		tinypass_save_tag_data($values);
		tinypass_admin_tags_body();
	}else {
		if(isset($values['en']) == false)
			$_POST['tinypass']['en'] = 0;
		tinypass_save_postdata($_POST['post_ID']);
		echo tinypass_options_overview($values);
	}

	die();
}

function tinypass_validate_popup_values($values, $type) {

	$errors = array();
	if($type == 'tag') {

		if($values['resource_id'] == '')
			$errors['resource_id'] = 'Tag name cannot be empty';

		if($values['resource_name'] == '')
			$errors['resource_name'] = 'Description cannot be empty';

		if($values['po_p1'] == '' || is_numeric($values['po_p1']) == false)
			$errors['po_p1'] = 'Price must be valid number';

	}else {

		if($values['po_p1'] == '' || is_numeric($values['po_p1']) == false)
			$errors['po_p1'] = 'Price must be valid number';

		if($values['po_ap1'] != '' && is_numeric($values['po_ap1']) == false)
			$errors['po_ap1'] = 'Access period must be valid number';


	}

	return $errors;

}

function tinypass_save_tag_data($values) {
	global $wpdb;

	$tagName = $values['resource_id'];

	$values['en'] = 1;

	if(isset($values['term_id']) && $values['term_id'] != null)
		$d = $wpdb->query("delete from $wpdb->tinypass_ref where term_id = " . $values['term_id']);

	$termData = term_exists(intval($values['term_id']), 'post_tag');

	//check by name
	if($termData == null)
		$termData = term_exists($tagName, 'post_tag');

	if($termData == null)
		$termData = wp_insert_term($tagName, 'post_tag', array('slug'=>$tagName));
	else {
		wp_update_term($termData['term_id'], 'post_tag', array('name'=>$tagName, 'slug'=>$tagName));
	}


	$termId = $termData['term_id'];

	$values['resource_id'] = 'wp_tag_' . $termId;

	$wpdb->insert($wpdb->tinypass_ref , array( 'term_id' => $termId, 'type' => 0, 'data' => serialize($values)) );

}

function tinypass_popup_form($meta, $postID = null, $type = null) {
	tinypass_page_form($meta, $postID, $type);
}

function tinypass_options_overview($values, $post = null) {

	if($values == "" || count($values) == 0)
		return "";

	$output = "";

	foreach($values as $name => $value) {
		$output .= "<input type='hidden' name='tinypass[$name]' value='$value'>";
	}

	$resource_name = $values['resource_name'];

	if($resource_name == '')
		$resource_name = 'Default to post title';

	$en= 'No';
	if(isset($values['en']) && $values['en'])
		$en= 'Yes';

	$output .= "<div><strong>Enabled:</strong>&nbsp;" . $en . "</div>";

	$output .= "<div><strong>Name:</strong>&nbsp;" . $resource_name . "</div>";

	$line = "";
	for($i = 1; $i <= 1; $i++) {

		$price = $values["po_p$i"];
		$accessPeriod = $values["po_ap$i"];
		$accessPeriodType =  $values["po_type$i"] . "(s)";
		$caption =  $values["po_cap$i"];
		$startTime =  $values["po_st$i"];
		$endTime =  $values["po_et$i"];

		if($accessPeriod == '') {
			$line = "<div><strong>Price:</strong>&nbsp; $price for unlimited access</div>";
		}else {
			$line = "<div><strong>Price:</strong>&nbsp; $price for $accessPeriod $accessPeriodType</div>";
		}

		if($caption != '') {
			$line .= "<div><strong>Caption:</strong>&nbsp; '$caption'</div>";
		}

		if($startTime != '' && $endTime != '')
			$line .= " <br><strong>Valid from: </strong><div style='padding-left:15px'>$startTime to </div><div style='padding-left:15px'>$endTime</div>";
		else if($startTime != '')
			$line .= " <br>Valid from $startTime";
		else if($endTime != '')
			$line .= " <br>Ending on $endTime";

		$line .= "<br>";

	}

	$output .= $line;

	return $output;

}

function tinypass_post_header_form($meta) {

	?>

<table class="form-table">
	<tr>
		<td>
			<input id="tp_modify_button" class="button" type="button" hef="#" onclick="return tp_showTinyPassPopup();return false;" value="Modify Options">
			<div id="tp_dialog" title="<img src='http://www.tinypass.com/favicon.ico'> TinyPass Post Options" style="display:none;width:650px;"></div>
			<br>
		</td>
	</tr>

	<tr>
		<td>
			<span id="tp_hidden_options"><?php echo tinypass_options_overview($meta); ?> </span>
		</td>
	</tr>
</table>

	<?php }


/**
 * Method outputs the popup form for entering TP parameters.
 * Will work with both tag and post forms
 */
function tinypass_page_form($meta, $postID = null, $type = null) {

	wp_nonce_field( 'tinypass_post_save', 'tinypass_noncename' );

	$resource_id = '';
	$resource_name = '';


	$resource_id_label = '';
	$resource_id_label_desc = '';
	$resource_name_label = 'Title';
	$resource_name_label_desc = 'Optional - Leave empty to default to post title';
	$showIDLabel = false;
	$termId = "-1";

	if($type == 'tag') {
		$termId = $meta['term_id'];
		//$termData = get_term($termId, 'post_tag');
		$showIDLabel = true;
		$resource_id_label = 'Tag Name';
		$resource_id_label_desc = 'Required - standard Wordpress tag';
		$resource_name_label = 'Description';
		$resource_name_label_desc = 'Description for this section of content. e.g. "Premium Site Access"';
	}

	if(isset($meta['resource_id']))
		$resource_id = $meta['resource_id'];

	if(isset($meta['resource_name']))
		$resource_name = $meta['resource_name'];

	if(isset($meta['en']) == false || $meta['en'])
		$checked = "checked=true";

	?>
<style>
	#tinypass_post_options_form h4{
		margin-bottom:0px;
		padding-bottom:0px;
	}
	.tinypass_price_options_form {
		background-color:#eee;
		border-bottom:1px solid black;
		width:100%;
	}
	.tinypass_price_options_form td{
		padding:3px;
	}
	.options {
		width:100%;
	}
	.options, .options td {
		margin:0px;
		padding:0px;
	}
	.add_option_link {
		text-decoration: none;
	}
</style>

<div id="tinypass_post_options_form">
	<div id="tp-error" style="text-align:center;color:red;font-size:10pt"></div>

	<input type="hidden" name="tinypass[tp_type]" value="<?php echo $type ?>"/>
	<input type="hidden" name="tinypass[term_id]" value="<?php echo $termId ?>"/>
	<input type="hidden" name="tinypass[post_ID]" value="<?php echo $postID?>"/>
	<input type="hidden" name="post_ID" value="<?php echo $postID?>"/>

	<table class="form-table" id="" style="" >
			<?php if($showIDLabel) { ?>
		<tr>
			<td>
				<strong><?php echo $resource_id_label ?></strong><br>
				<input type="text" size="35" maxlength="255" name="tinypass[resource_id]" value="<?php echo $resource_id ?>">
				<div class="description"><?php echo $resource_id_label_desc ?></div>
			</td>
		</tr>
				<?php } ?>
		<tr>
			<td>
					<?php if($type != 'tag') { ?>
				<div style="float:right">
					<strong>Enabled?</strong>: <input type="checkbox" autocomplete=off name="tinypass[en]" value="1" <?php echo $checked?>>
				</div>
						<?php } else { ?>
				<input type="hidden" name="tinypass[en]" value="1">
						<?php } ?>
				<strong><?php echo $resource_name_label ?></strong> - this value will be displayed in the TinyPass Popup
				<br>
				<input type="text" size="35" maxlength="255" name="tinypass[resource_name]" value="<?php echo $resource_name ?>">
				<div class="description"><?php echo $resource_name_label_desc?></div>
			</td>
		</tr>
	</table>
	<hr>
	<table class="form-table" id="" style="margin-top:0px;padding-top:0px;" >
		<tr>
			<td>
				<strong>Pricing Options 
					<a class="add_option_link" href="#" onclick="tinypass_addPriceOption();return false;">[+]</a>
					<a class="add_option_link" href="#" onclick="tinypass_removePriceOption();return false;">[-]</a>
				</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(between 1 and 3)<br>
					<?php echo __tinypass_price_option_display('1', $meta)  ?>
				<!--				<hr> -->
					<?php echo __tinypass_price_option_display('2', $meta)  ?>
					<?php echo __tinypass_price_option_display('3', $meta)  ?>
				<!--
				<hr>
					<?php //echo __tinypass_price_option_display('3', $meta)  ?>
				-->
				<br>
				<center>
					<button class="button" type="button" onclick="tp_saveTinyPassPopup();">Save</button>
					<button class="button" type="button" onclick="tp_closeTinyPassPopup();">Cancel</button>
				</center>
			</td>
		</tr>
	</table>
</div>

	<?php
}

function __tinypass_price_option_display($opt, $values) {

	$times = array('hour'=>'hour(s)', 'day'=>'day(s)', 'week'=>'week(s)', 'month'=>'month(s)');

	$price = '';
	$access_period = '';
	$access_period_type = 'day';
	$caption = '';
	$enabled = 0;
	$readonly = '';
	$checked = '';
	$start_time = '';
	$end_time = '';

	if(isset($values["po_p$opt"])) {
		$price = $values["po_p$opt"];
	}

	if(isset($values["po_ap$opt"])) {
		$access_period = $values["po_ap$opt"];
	}

	if(isset($values["po_type$opt"])) {
		$access_period_type = $values["po_type$opt"];
	}

	if(isset($values["po_cap$opt"])) {
		$caption = $values["po_cap$opt"];
	}

	if(isset($values["po_st$opt"])) {
		$start_time = $values["po_st$opt"];
	}

	if(isset($values["po_et$opt"])) {
		$end_time = $values["po_et$opt"];
	}

	if(isset($values["po_en$opt"])) {
		$enabled = 1;
	}

	$enab = '';
	$checked = 'checked=false';
	$readonly = '';
	$name = "tinypass[po_en$opt]";
	$display = "display:none";
	if($opt == '1' || $enabled) {
		$display = "";
	}

	?>
<table class="options">
	<tr>
		<td>
			<table class="tinypass_price_options_form option_form<?php echo $opt ?>" style="<?php echo $display?>">
				<tr>
					<td></td>
					<td>Price:</td>
					<td>Access Period:<span>(optional)</span></td>
					<td>Caption:<span>(optional)</span></td>
				</tr>
				<tr>
					<td>
						<input type="hidden" name="tinypass[<?php echo "po_en$opt"?>]" value="<?php echo $enabled ?>">
					</td>
					<td>
						<input type="text" size="5" maxlength="5" name="tinypass[<?php echo "po_p$opt"?>]" value="<?php echo $price ?>">
					</td>
					<td>
						<input type="text" size="5" maxlength="5" name="tinypass[<?php echo "po_ap$opt"?>]" value="<?php echo $access_period ?>">
						<select name="tinypass[<?php echo "po_type$opt" ?>]">
								<?php foreach($times as $key => $value): ?>
									<?php if($key == $access_period_type) { ?>
							<option value="<?php echo $key ?>" selected=true><?php echo $value ?>
											<?php } else { ?>
							<option value="<?php echo $key ?>"><?php echo $value ?>
											<?php } ?>
									<?php endforeach ?>
						</select>
					</td>
					<td>
						<input type="text" size="20" maxlength="20" name="tinypass[<?php echo "po_cap$opt"?>]" value="<?php echo $caption ?>">
					</td>
				</tr>
				<!--
				<tr>
					<td colspan=2><strong>Dates Active</strong></td>
	</tr>
	 -->
				<tr>
					<td></td>
					<td colspan="2">
			Start Date:<span>(optional)</span><br><input type="text" maxlength="16" class="tinypass-datetimepicker" name="tinypass[<?php echo "po_st$opt"?>]" value="<?php echo $start_time?>">
					</td>
					<td>
			End Date: <span>(optional)</span>&nbsp;&nbsp;<br> <input type="text" maxlength="16" class="tinypass-datetimepicker" name="tinypass[<?php echo "po_et$opt"?>]" value="<?php echo $end_time ?>" >
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
	<?php }
