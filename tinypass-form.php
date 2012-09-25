<?php
add_action('wp_ajax_tp_showEditPopup', 'ajax_tp_showEditPopup');
add_action('wp_ajax_tp_saveEditPopup', 'ajax_tp_saveEditPopup');
//add_action('wp_ajax_tp_deleteTagOption', 'ajax_tp_deleteTagOption');
wp_enqueue_script("jquery-core");
wp_enqueue_script("jquery-ui");
wp_enqueue_script('jquery-ui-dialog');

/**
 * AJAX callback for deleting tinypass enabled tags
 */
/*
  function ajax_tp_deleteTagOption() {
  global $wpdb;

  if (!current_user_can('edit_posts'))
  die();

  if (!wp_verify_nonce($_REQUEST['tinypass_nonce'], 'tinypass_options'))
  die('Security check failed');

  $termId = $_POST['term_id'];
  $wpdb->query("delete from $wpdb->tinypass_ref where term_id = $termId ");
  tinypass_admin_tags_body();
  die();
  }
 */

/**
 * AJAX callback to show the TinyPass options form for both tags and posts/pages
 */
function ajax_tp_showEditPopup() {
	if (!current_user_can('edit_posts'))
		die();

	tinypass_include();

	$postID = $_POST['post_ID'];

	$storage = new TPStorage();
	$ps = $storage->getPostSettings($postID);

	tinypass_popup_form($ps, $postID);
	die();
}

/**
 * Save the Popup Form
 *
 * 	Tags will be saved directly
 *
 */
function ajax_tp_saveEditPopup() {

	if (!current_user_can('edit_posts'))
		die();

	if (!wp_verify_nonce($_REQUEST['tinypass_nonce'], 'tinypass_options'))
		die('Security check failed');

	$fields = array('po_ap', 'po_cap', 'po_en', 'po_et', 'po_p', 'po_st', 'po_type');
	foreach ($fields as $f) {
		if (isset($_POST['tinypass']['po_en2']) == false || $_POST['tinypass']['po_en2'] == 0) {
			unset($_POST['tinypass'][$f . '2']);
		}
		if (isset($_POST['tinypass']['po_en3']) == false || $_POST['tinypass']['po_en3'] == 0) {
			unset($_POST['tinypass'][$f . '3']);
		}
	}

	$values = $_POST['tinypass'];

	$errors = tinypass_validate_post_submit($values);
	if ($errors) {
		echo "var a;";
		foreach ($errors as $field => $msg) {
			echo "tinypass.doError('$field', '$msg');";
		}
		die();
	}

	if (isset($values['en']) == false)
		$_POST['tinypass']['en'] = 0;

	tinypass_include();

	tinypass_save_post_data($_POST['post_ID']);

	$storage = new TPStorage();
	$ps = $storage->getPostSettings($_POST['post_ID']);
	echo tinypass_post_options_summary($ps);

	die();
}

/**
 * Method will save the TinyPass options data for posts or pages
 */
function tinypass_save_post_data($post_id) {

	delete_post_meta($post_id, 'tinypass');

	$data = array();

	if (isset($_POST['tinypass']))
		$data = $_POST['tinypass'];

	update_post_meta($post_id, 'tinypass', $data, true);

	return $data;
}

/**
 * Validate TinyPass post/tag options when saving
 */
function tinypass_validate_post_submit($values) {

	$errors = array();

	for ($i = 1; $i <= 3; $i++) {

		if (!isset($values['po_en']) || $values['po_en' . $i] == 0)
			continue;


		if (isset($values['po_p' . $i])) {
			$p = $values['po_p' . $i];
			if ($p == '' || (is_numeric($p) && doubleval($p) < 0)) {
				$errors['po_p' . $i] = _("Price($i) must be greater then zero");
			} else if (!preg_match('/\d*[.,]?\d+/', $p)) {
				$errors['po_p' . $i] = _("Price($i) must be a number or <# CUR> e.g. 1 EUR or 2.99 NOK or 1 (default USD) ");
			}
		}

		if (isset($values['po_ap' . $i]) && $values['po_ap' . $i] != '' && is_numeric($values['po_ap' . $i]) == false)
			$errors['po_ap' . $i] = _('Access period must be valid number');
	}


	if (isset($values['metered']) && $values['metered'] != 'off') {

		if (isset($values['m_lp']) && $values['m_lp'] == '' || is_numeric($values['m_lp']) == false)
			$errors['m_lp'] = _('Lockout period must be a valid number');
	}

//validate metered options
	//if ($values['metered'] == 'time') {
	//	if (isset($values['m_tp']) && $values['m_tp'] == '' || is_numeric($values['m_tp']) == false)
	//		$errors['m_tp'] = _('Trial period must be a valid number');
	//}else if ($values['metered'] == 'count') {
	//}

	return $errors;
}

/**
 * 
 * @param type $ps
 * @param type $postID
 */
function tinypass_popup_form($ps, $postID = null) {
	tinypass_post_form($ps, $postID);
}

/**
 * 
 * @param TPPaySettings $ps
 * @return string
 */
function tinypass_post_options_summary(TPPaySettings $ps) {

	$output = "";

	$resource_name = htmlspecialchars(stripslashes($ps->getResourceName()));

	if ($resource_name == '')
		$resource_name = 'Default to post title';

	$en = __('No');
	if ($ps->isEnabled())
		$en = __('Yes');

	$output .= "<div><strong>Enabled:</strong>&nbsp;" . $en . "</div>";

	$output .= "<div><strong>Name:</strong>&nbsp;" . $resource_name . "</div>";

	$line = "<div><strong>Pricing:</strong></div>";
	for ($i = 1; $i <= 3; $i++) {

		if ($ps->hasPriceConfig($i) == false)
			continue;

		$caption = $ps->getCaption($i);

		$line .= "<div style='padding-left:50px;'>" . $ps->getAccessFullFormat($i);

		if ($caption != '') {
			$line .= " - '" . htmlspecialchars(stripslashes($caption)) . "'";
		}

		$line .= "</div>";
	}

	$output .= $line;

	//$output .= "<div><strong>Remove Teaser:</strong>&nbsp;" . ($ps->isHideTeaser() ? "Yes" : "No") . "</div>";

	return $output;
}

/**
 * 
 * @param type $meta
 */
function tinypass_post_header_form($meta) {
	?>

	<table class="form-table">
		<tr>
			<td>
				<input id="tp_modify_button" class="button" type="button" hef="#" onclick="return tinypass.showTinyPassPopup();return false;" value="Modify Options">
				<div id="tp_dialog" title="<img src='http://www.tinypass.com/favicon.ico'> TinyPass Post Options" style="display:none;width:650px;"></div>
				<br>
			</td>
		</tr>

		<tr>
			<td>
				<span id="tp_hidden_options"><?php echo tinypass_post_options_summary($meta); ?> </span>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Method outputs the popup form for entering TP parameters.
 * Will work with both tag and post forms
 */
function tinypass_post_form(TPPaySettings $ps, $postID = null) {

	wp_nonce_field('tinypass_options', 'tinypass_nonce');

	$resource_name = $ps->getResourceName();

	$resource_name_label = 'Title';
	$resource_name_label_desc = 'Optional - Leave empty to default to post title';
	$termId = "-1";
	?>


	<div id="tinypass_post_options_form">
		<div id="tp-error" style="text-align:center;color:red;font-size:10pt"></div>

		<input type="hidden" name="tinypass[post_ID]" value="<?php echo $postID ?>"/>
		<input type="hidden" name="post_ID" value="<?php echo $postID ?>"/>

		<table class="form-table" id="" style="" >
			<tr>
				<td>
					<div style="float:right">
						<strong>Enabled?</strong>: <input type="checkbox" autocomplete=off name="tinypass[en]" value="1" <?php echo checked($ps->isEnabled()) ?>>
					</div>
					<strong><?php echo $resource_name_label ?></strong> - this value will be displayed in the TinyPass popup window
					<br>
					<input type="text" size="35" maxlength="255" name="tinypass[resource_name]" value="<?php echo htmlspecialchars(stripslashes($resource_name)) ?>">
					<div class="description"><?php echo $resource_name_label_desc ?></div>
			</tr>
		</table>

		<?php
		/*
		  <hr>
		  <table class="form-table">
		  <tr>
		  <td>
		  <input type="checkbox" autocomplete=off name="tinypass[ht]" value="1" <?php echo checked($ps->isHideTeaser()) ?>>
		  Hide the teaser after successful purchase
		  </td>
		  </tr>
		  </table>
		 */
		?>
		<hr>
		<table class="form-table" id="" style="margin-top:0px;padding-top:0px;" >
			<tr>
				<td>
					<strong>Pricing options
						<a class="add_option_link" href="#" onclick="tinypass.addPriceOption();return false;">[+]</a>
						<a class="add_option_link" href="#" onclick="tinypass.removePriceOption();return false;">[-]</a>
					</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(between 1 and 3)<br>
					<?php echo __tinypass_price_option_display('1', $ps, false) ?>
					<?php echo __tinypass_price_option_display('2', $ps, false) ?>
					<?php echo __tinypass_price_option_display('3', $ps, false) ?>
					<br>
			<center>
				<button class="button" type="button" onclick="tinypass.saveTinyPassPopup();">Save</button>
				<button class="button" type="button" onclick="tinypass.closeTinyPassPopup();">Cancel</button>
			</center>
			</td>
			</tr>
		</table>
	</div>

<?php } ?>
<?php

/**
 * 
 */
function __tinypass_metered_display(TPPaySettings $ps) {

	$metered = $ps->getMetered('count');

	$trial_period = $ps->getMeterTrialPeriod('');
	$trial_period_type = $ps->getMeterTrialPeriodType();

	$lockout_period = $ps->getMeterLockoutPeriod();
	$lockout_period_type = $ps->getMeterLockoutPeriodType();

	$meter_count = $ps->getMeterMaxAccessAttempts();

	$times = TPSiteSettings::$PERIOD_CHOICES;
	?>

	<div class="postbox">

		<h3><?php _e('Meter Options'); ?> </h3>
		<div class="inside"> 
			<table class="form-table">
				<tr>
					<td>
						<strong></strong>
						<?php echo __tinypass_dropdown("tinypass[metered]", array('count' => 'View Based', 'time' => 'Time Based'), $metered, array("onchange" => "tinypass.showMeteredOptions(this)")) ?>

						<div id="tp-metered-count" class="tp-metered-options">
							<table class="options">
								<tr>
									<td>
										<label>Users get</label>
										<input type="text" size="5" maxlength="5" name="tinypass[m_maa]" value="<?php echo $meter_count ?>">
										<label>views within</label>
										<input type="text" size="5" maxlength="5" name="tinypass[m_lp]" value="<?php echo $lockout_period ?>">
										<?php echo __tinypass_dropdown("tinypass[m_lp_type]", $times, $lockout_period_type) ?>
									</td>
								</tr>
							</table>
						</div>

						<div id="tp-metered-time" class="tp-metered-options">
							<table class="options">
								<tr>
									<td>
										<label>Users will have access for</label>
										<input type="text" size="5" maxlength="5" name="tinypass[m_tp]" value="<?php echo $trial_period ?>">
										<?php echo __tinypass_dropdown("tinypass[m_tp_type]", $times, $trial_period_type) ?>
										<label>then they will be locked out for</label>
										<input type="text" size="5" maxlength="5" name="tinypass[m_lp]" value="<?php echo $lockout_period ?>">
										<?php echo __tinypass_dropdown("tinypass[m_lp_type]", $times, $lockout_period_type) ?>
									</td>
								</tr>
							</table>
						</div>

					</td>
				</tr>
			</table>
		</div>
	</div>


<?php } ?>
<?php

function __tinypass_strict_messaging_display(TPPaySettings $ps) { ?>

	<div class="postbox" id="">
		<h3><?php _e('Add an information page (optional)'); ?> </h3>
		<div class="inside"> 

			<div class="tp-simple-table">
				<div class="label"><?php _e('Upsell splash page') ?></div>
				<input name="tinypass[sub_page]" size="40" value="<?php echo $ps->getSubscriptionPage() ?>" >
				<div class="info">Path of existing page e.g. /signup, /join</div>

				<br>

				<div class="label"><?php _e('Confirmation (thank you page)') ?></div>
				<input name="tinypass[sub_page_success]" size="40" value="<?php echo $ps->getSubscriptionPageSuccess() ?>" >
				<div class="info">Path of existing page e.g. /signup, /join</div>
			</div>


		</div>
	</div>

	<?php
}
?>
<?php

/**
 * 
 */
function __tinypass_metered_options_display(TPPaySettings $ps) {
	?>

	<div class="postbox">
		<h3><?php _e('Protected Content'); ?> </h3>
		<div class="inside"> 
			<div>
				<label for="tp_enable_per_tag"><?php _e('Site-wide restriction based on the following tags:') ?></label>
				<input type="text" name="tinypass[tags]" class="premium_tags" autocomplete="off" value="<?php echo $ps->getPremiumTags(', ') ?>">
			</div>
		</div>
	</div>

<?php } ?>
<?php

/**
 * 
 */
function __tinypass_strict_options_display(TPPaySettings $ps) {
	?>

	<div class="postbox">
		<h3><?php _e('Protected Content'); ?> </h3>
		<div class="inside"> 
			<div>
				<input class="tp_enable_per_tag" type="checkbox" name="tinypass[per_tag]" <?php echo checked($ps->isEnabledPerTag()) ?> >
				<label for="tp_enable_per_tag"><?php _e('Enable site-wide restriction based on the following tags:') ?></label>
				<input type="text" name="tinypass[tags]" class="premium_tags" autocomplete="off" value="<?php echo $ps->getPremiumTags(', ') ?>">
			</div>

			<div>
				<input class="tp_enable_per_post" type="checkbox" name="tinypass[per_post]" <?php echo checked($ps->isEnabledPerPost()) ?> >
				<label for="tp_enable_per_post"><?php _e('Allow pay-per-view on individual pages or posts') ?></label>
			</div>
		</div>
	</div>

<?php } ?>

<?php

/**
 * 
 */
function __tinypass_tag_display(TPPaySettings $ps) {
	?>

	<div class="postbox">
		<h3><?php _e('Select the tags you\'d like to restrict'); ?> </h3>
		<div class="info">All posts associated with these tags wil automatically be restricted with Tinypass</div>
		<div class="inside"> 
			<div id="tag-holder">
				<?php foreach ($ps->getPremiumTagsArray() as $tag): ?>
					<div class="tag">
						<div class="text"><?php echo $tag ?></div>
						<div class="remove"></div>
						<input type="hidden" name="tinypass[tags][]" value="<?php echo $tag ?>">
					</div>
				<?php endforeach; ?>
			</div>
			<div class="clear"></div>
			<div id="tag-entry">
				<input class="tp_enable_per_tag" type="hidden" name="tinypass[per_tag]" <?php echo checked($ps->isEnabledPerTag()) ?> >
				<input type="text" class="premium_tags" autocomplete="off" >
				<a class="add_tag button-secondary"><?php _e('Add') ?></a>
			</div>
		</div>
	</div>

<?php } ?>



<?php

/**
 * Display pricing options section
 */
function __tinypass_pricing_display(TPPaySettings $ps) {
	?>

	<div class="postbox">
		<h3><?php _e('Set price options for access'); ?> </h3>
		<div class="info">Add up to three options.  You can offer hours, days, weeks, or even months.  Leave access field empty for unlimited.</div>
		<div class="info">Specify an additional currency by entering "20 NOK" or "4 EUR" in the price field</div>
		<div class="inside"> 
			<table class="tinypass_price_options_form" style="<?php echo $display ?>">
				<tr>
					<th width="100"><?php _e('Price') ?></th>
					<th width="150"><?php _e('Length of access') ?></th>
					<th width="200"><?php _e('Monthly subscription') ?></th>
					<th width="300"><?php _e('Caption (optional)') ?></th>
				</tr>
			</table>

			<?php echo __tinypass_price_option_display(1, $ps) ?>
			<?php echo __tinypass_price_option_display(2, $ps) ?>
			<?php echo __tinypass_price_option_display(3, $ps) ?>

			<br>
			<div id="pricing_add_more_buttons">
				<strong>
					<a class="add_option_link button-secondary" href="#" onclick="tinypass.addPriceOption();return false;"><?php _e('Add More') ?></a>
					<a class="add_option_link button-secondary" href="#" onclick="tinypass.removePriceOption();return false;"><?php _e('Remove') ?></a>
				</strong>
				<br>
			</div>
		</div>
	</div>

<?php } ?>
<?php

/**
 * 
 */
function __tinypass_ppv_payment_display(TPPaySettings $ps) {
	?>

	<div class="postbox">
		<h3><?php _e('Payment Display Options'); ?> </h3>
		<div class="inside"> 

			<div class="tp-simple-table">

				<div class="label">Header</div>
				<input id="tp_pd_denied_msg1" name="tinypass[pd_denied_msg1]" value="<?php echo esc_attr(stripslashes($ps->getDeniedMessage1())) ?>" size="80" maxlength="80">
				<br>

				<div class="label">Description</div>
				<textarea id="tp_pd_denied_sub1" rows="5" cols="80" name="tinypass[pd_denied_sub1]"><?php echo stripslashes($ps->getDeniedSub1()) ?></textarea>
			</div>

		</div>
	</div>


<?php } ?>

<?php

/**
 * Display payment display options 
 */
function __tinypass_payment_display(TPPaySettings $ps) {
	?>

	<div class="postbox" id="">
		<h3><?php _e('Customize your messaging'); ?> </h3>
		<div class="info">When users reach any restricted post, the will see an inline block with your header, description, and the Tinypass purchase button</div>
		<div class="inside"> 

			<?php
			/*
			  <div class="section">
			  <label for="tp_pa_expand"><?php _e('Select your payment display:') ?></label>
			  <?php echo __tinypass_dropdown("tinypass[pd_type]", TPSiteSettings::$PA_CHOICES, $ps->getPaymentDisplay(), array('class' => 'tp_pd_type')) ?>
			  </div>
			 */
			?>

			<div class="tp-simple-table">
				<label><?php _e('Site description:') ?></label>
				<input name="tinypass[resource_name]" size="40" value="<?php echo $ps->getResourceName() ? esc_attr($ps->getResourceName()) : bloginfo("name") . " - Premium Access" ?>" >
				<div class="info">This will be dispalyed on the Tinypass ticket and user's purchase history</div>
			</div>

			<div class="tp_pd_type_panel">

				<div class="tp-simple-table">

					<div class="label">Header</div>
					<input id="tp_pd_denied_msg1" name="tinypass[pd_denied_msg1]" value="<?php echo esc_attr(stripslashes($ps->getDeniedMessage1())) ?>" size="80" maxlength="80">
					<br>

					<div class="label">Description</div>
					<textarea id="tp_pd_denied_sub1" rows="5" cols="80" name="tinypass[pd_denied_sub1]"><?php echo stripslashes($ps->getDeniedSub1()) ?></textarea>
				</div>
			</div>

		</div>
	</div>

	<?php
}
?>
<?php

/**
 * Display individual price option
 */
function __tinypass_price_option_display($opt, TPPaySettings $ps, $sub = true) {

	$times = TPSiteSettings::$PERIOD_CHOICES;

	$enabled = 0;
	$price = $ps->getPrice($opt, '');

	$access_period = $ps->getAccessPeriod($opt, '');

	$access_period_type = $ps->getAccessPeriodType($opt, '');

	$caption = htmlspecialchars(stripslashes($ps->getCaption($opt)));

	$recur = "1 month" == $ps->getRecurring($opt);

	if ($opt == 1 || $ps->hasPriceConfig($opt)) {
		$enabled = 1;
	}

	$display = "display:none";
	if ($opt == '1' || $enabled) {
		$display = "";
	}
	?>
	<table class="tinypass_price_options_form option_form option_form<?php echo $opt ?>" style="<?php echo $display ?>">
		<tr>
			<td width="100">
				<input type="hidden" id="<?php echo "po_en$opt" ?>" name="tinypass[<?php echo "po_en$opt" ?>]" value="<?php echo $enabled ?>">
				<input type="text" size="5" maxlength="10" name="tinypass[<?php echo "po_p$opt" ?>]" value="<?php echo $price ?>">
			</td>
			<td width="150">
				<input type="text" size="5" maxlength="5" name="tinypass[<?php echo "po_ap$opt" ?>]" value="<?php echo $access_period ?>" class="po_ap_opts<?php echo $opt ?>">
				<?php echo __tinypass_dropdown("tinypass[po_ap_type$opt]", $times, $access_period_type, array('class' => "po_ap_opts$opt")) ?>
			</td>
			<?php if ($sub): ?>
				<td width="200">
					<label for="<?php echo "po_recu$opt" ?>">Enabled</label>
					<input class="recurring-opts" opt="<?php echo $opt ?>" id="<?php echo "po_recur$opt" ?>" value="1 month" type="checkbox" name="tinypass[po_recur<?php echo $opt ?>]" <?php checked($recur) ?> >
				</td>
			<?php endif; ?>
			<td width="300">
				<input type="text" size="20" maxlength="20" name="tinypass[<?php echo "po_cap$opt" ?>]" value="<?php echo $caption ?>">
			</td>
		</tr>
	</table>
	<?php
}

function __tinypass_dropdown($name, $values, $selected, $attrs = null) {
	if ($attrs == null)
		$attrs = array();

	$output = "<select name=\"$name\" ";

	foreach ($attrs as $key => $value) {
		$output .= " $key=\"$value\"";
	}

	$output .= ">";

	foreach ($values as $key => $value) {
		$output .= "<option value=\"$key\" " . selected($selected, $key, false) . ">$value</option>";
	}

	$output .= "</select>";

	return $output;
}
?>