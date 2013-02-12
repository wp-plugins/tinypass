<?php
add_action('wp_ajax_tp_showEditPopup', 'ajax_tp_showEditPopup');
add_action('wp_ajax_tp_saveEditPopup', 'ajax_tp_saveEditPopup');

wp_enqueue_script("jquery");
wp_enqueue_script("jquery-ui");
wp_enqueue_script('jquery-ui-dialog');

/**
 * AJAX callback to show the Tinypass options form for both tags and posts/pages
 */
function ajax_tp_showEditPopup() {
	if (!current_user_can('edit_posts'))
		die();

	tinypass_include();

	$postID = $_POST['post_ID'];

	$storage = new TPStorage();
	$ps = $storage->getPostSettings($postID);

	tinypass_post_form($ps, $postID);

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

	tinypass_include();

	$storage = new TPStorage();
	$errors = array();

	$ss = $storage->getSiteSettings();
	$ps = $ss->validatePostSettings($_POST['tinypass'], $errors);

	$storage->savePostSettings($_POST['post_ID'], $ps);

	if (count($errors)) {
		echo "var a; tinypass.clearError(); ";
		foreach ($errors as $field => $msg) {
			echo "tinypass.doError('$field', \"$msg\");";
		}
		die();
	}

	$ps = $storage->getPostSettings($_POST['post_ID']);
	echo tinypass_post_options_summary($ps);

	die();
}

/**
 * 
 * @param TPPaySettings $ps
 * @return string
 */
function tinypass_post_options_summary(TPPaySettings $ps) {

	$output = "";

	$resource_name = htmlspecialchars(stripslashes($ps->getResourceName()));
	$resource_id = htmlspecialchars(stripslashes($ps->getResourceId()));

	if ($resource_name == '')
		$resource_name = 'Default to post title';

	$en = $ps->isEnabled() ? __('Yes') : __('No');

	$output .= "<div><strong>Enabled:</strong>&nbsp;" . $en . "</div>";

	$output .= "<div><strong>Name:</strong>&nbsp;" . $resource_name . "</div>";
	
	$output .= "<div><strong>RID:</strong>&nbsp;" . $resource_id . "</div>";

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
				<div id="tp_dialog" title="<img src='http://www.tinypass.com/favicon.ico'> Tinypass Options" style="display:none;font-size:12px"></div>
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


	$resource_id = htmlspecialchars(stripslashes($ps->getResourceId()));

	if ($resource_id == '')
		$ps->setResourceId('wp_post_' . $postID);

	?>

	<div id="poststuff">
		<?php wp_nonce_field('tinypass_options', 'tinypass_nonce'); ?>
		<div class="tp-settings">
			<div id="tp-error"></div>
			<div class="inside">

				<input type="hidden" name="tinypass[post_ID]" value="<?php echo $postID ?>"/>
				<input type="hidden" name="post_ID" value="<?php echo $postID ?>"/>

				<div style="float:right">
					<strong>Enabled?</strong>: <input type="checkbox" autocomplete=off name="tinypass[mode]" value="1" <?php echo checked($ps->isEnabled()) ?>>
				</div>
				<br>

				<div class="">
					<h3><?php _e("Enter up to 3 price options") ?></h3>
					<div class="inside">

						<table class="tinypass_price_options_form">
							<tr>
								<th width="100"><?php _e('Price') ?></th>
								<th width="180"><?php _e('Length of access') ?></th>
								<th width="270"><?php _e('Caption (optional)') ?></th>
							</tr>
						</table>

						<?php echo __tinypass_price_option_display('1', $ps, false, 180) ?>
						<?php echo __tinypass_price_option_display('2', $ps, false, 180) ?>
						<?php echo __tinypass_price_option_display('3', $ps, false, 180) ?>

						<br>
						<a class="add_option_link button" href="#" onclick="tinypass.addPriceOption();return false;">Add</a>
						<a class="add_option_link button" href="#" onclick="tinypass.removePriceOption();return false;">Remove</a>
					</div>
				</div>
				<?php echo __tinypass_custom_rid_display($ps) ?>
				<?php echo __tinypass_payment_messaging_post_display($ps) ?>
				<div>
					<center>
						<button class="button" type="button" onclick="tinypass.saveTinyPassPopup();">Save</button>
						<button class="button" type="button" onclick="tinypass.closeTinyPassPopup();">Cancel</button>
					</center>
				</div>
			</div>
		</div>
	</div>

<?php } ?>
<?php

/**
 * 
 */
function __tinypass_metered_display(TPPaySettings $ps, $num = '') {

	$metered = $ps->getMetered('count');

	$trial_period = $ps->getMeterTrialPeriod('');
	$trial_period_type = $ps->getMeterTrialPeriodType();

	$lockout_period = $ps->getMeterLockoutPeriod();
	$lockout_period_type = $ps->getMeterLockoutPeriodType();

	$meter_count = $ps->getMeterMaxAccessAttempts();

	$times = TPSiteSettings::$PERIOD_CHOICES;
	?>

	<div class="tp-section">

		<h3><?php _e("$num. " . 'Configure your preview period'); ?> </h3>
		<p class="info">Users can look at your content for a certain number of views, or for a certain time period. After the limit is reached, your user will be asked to pay until the meter opens again. 
		</p>
		<div class="postbox">
			<h3><?php _e('Preview Options'); ?> </h3>
			<div class="inside">

				<table class="form-table">
					<tr>
						<td width="120" valign="middle">
							<div>
								<?php echo __tinypass_dropdown("tinypass[metered]", array('count' => 'View Based', 'time' => 'Time Based'), $metered, array("onchange" => "tinypass.showMeteredOptions(this)")) ?>
							</div>
						</td>
						<td style="border-left:1px solid #DFDFDF">

							<div id="tp-metered-count" class="tp-metered-options">
								<label>Users get</label>
								<input type="text" size="5" maxlength="5" name="tinypass[m_maa]" value="<?php echo $meter_count ?>">
								<label>views within</label>
								<input type="text" size="5" maxlength="5" name="tinypass[m_lp]" value="<?php echo $lockout_period ?>">
								<?php echo __tinypass_dropdown("tinypass[m_lp_type]", $times, $lockout_period_type) ?>
							</div>

							<div id="tp-metered-time" class="tp-metered-options">
								<label>Users will have access for</label>
								<input type="text" size="5" maxlength="5" name="tinypass[m_tp]" value="<?php echo $trial_period ?>">
								<?php echo __tinypass_dropdown("tinypass[m_tp_type]", $times, $trial_period_type) ?>
								<label>then they will be locked out for</label>
								<input type="text" size="5" maxlength="5" name="tinypass[m_lp]" value="<?php echo $lockout_period ?>">
								<?php echo __tinypass_dropdown("tinypass[m_lp_type]", $times, $lockout_period_type) ?>
							</div>

						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>


<?php } ?>
<?php

function __tinypass_purchase_page_display(TPPaySettings $ps, $num = "") { ?>

	<div class="tp-section" id="">
		<h3><?php _e("$num. " . 'Add an information page (optional)'); ?> </h3>
		<p class="info">Does your site have a marketing page, with custom messaging about membership, 
			subscriptions, or purchasing?  Enter its page name below and the Tinypass button will automatically
			be added to it.
		<div class="postbox">
			<h3><?php echo _e("Dedicated selling page") ?></h3>
			<div class="inside"> 
				<input name="tinypass[sub_page]" size="40" value="<?php echo $ps->getSubscriptionPage() ?>" >
				<p class="help">Path of existing page e.g. /signup, /join</p>
			</div>
		</div>

		<div class="postbox">
			<h3><?php echo _e("Confirmation page (thank you page)") ?></h3>
			<div class="inside"> 
				<input name="tinypass[sub_page_success]" size="40" value="<?php echo $ps->getSubscriptionPageSuccess() ?>" >
				<p class="help">Path of existing page e.g. /thankyou</p>
			</div>
		</div>
	</div>

<?php } ?>
<?php

/**
 * 
 */
function __tinypass_tag_display(TPPaySettings $ps, $num = "") {
	?>

	<div class="tp-section">
		<h3><?php _e("$num. " . 'Select the tags you\'d like to restrict'); ?> </h3>
		<p class="info">All posts associated with these tags wil automatically be restricted with Tinypass</p>
		<div class="">
			<div class=""> 
				<div class="tag-holder">
					<?php foreach ($ps->getPremiumTagsArray() as $tag): ?>
						<div class="tag">
							<div class="text"><?php echo $tag ?></div>
							<div class="remove"></div>
							<input type="hidden" name="tinypass[tags][]" value="<?php echo $tag ?>">
						</div>
					<?php endforeach; ?>
				</div>
				<div class="clear"></div>
				<div class="tag-entry tp-bg">
					<input type="text" class="premium_tags" autocomplete="off" >
					<a class="add_tag button-secondary"><?php _e('Add') ?></a>
				</div>
			</div>
		</div>
	</div>


<?php } ?>
<?php

/**
 * Display pricing options section
 */
function __tinypass_pricing_display(TPPaySettings $ps, $num = "") {
	?>

	<div class="tp-section">
		<h3><?php _e("$num. " . 'Set your price options'); ?> </h3>

		<p class="info">
			Set the price for access to your content, and the length of the access period (up to 3 options total). You can offer access periods by hours, days, weeks, months, unlimited time, or even monthly subscriptions. For handling foreign currency, pay-what-you-want, and other cool stuff, 
			<a target="_blank" href="http://developer.tinypass.com/main/price_formats">check out the documentation</a>.
		</p>
		<p>Billing reoccurs automatically every month for subscriptions</p>


		<div class="">
			<div class=""> 

				<table class="tinypass_price_options_form">
					<tr>
						<th width="100"><?php _e('Price') ?></th>
						<th width="380"><?php _e('Length of access') ?></th>
						<th width="270"><?php _e('Caption (optional)') ?></th>
					</tr>
				</table>

				<?php echo __tinypass_price_option_display(1, $ps) ?>
				<?php echo __tinypass_price_option_display(2, $ps) ?>
				<?php echo __tinypass_price_option_display(3, $ps) ?>

				<br>
				<div id="pricing_add_more_buttons">
					<strong>
						<a class="add_option_link button-secondary" href="#" onclick="tinypass.addPriceOption();return false;"><?php _e('Add') ?></a>
						<a class="add_option_link button-secondary" href="#" onclick="tinypass.removePriceOption();return false;"><?php _e('Remove') ?></a>
					</strong>
					<br>
				</div>
			</div>
		</div>
	</div>

<?php } ?>
<?php

/**
 * 
 */
function __tinypass_ppv_payment_display(TPSiteSettings $ss) {
	?>

	<div class="postbox">
		<h3><?php _e('Purchase section'); ?> </h3>
		<div class="inside"> 

			<div class="tp-simple-table">

				<div class="label">Header</div>
				<input id="tp_pd_denied_msg1" name="tinypass[pd_denied_msg1]" value="<?php echo esc_attr(stripslashes($ss->getDeniedMessage1())) ?>" size="80" maxlength="80">
				<br>

				<div class="label">Description</div>
				<textarea id="tp_pd_denied_sub1" rows="5" cols="80" name="tinypass[pd_denied_sub1]"><?php echo stripslashes($ss->getDeniedSub1()) ?></textarea>
			</div>

		</div>
	</div>

<?php } ?>
<?php

function __tinypass_custom_rid_display(TPPaySettings $ps) { ?>
	<div class="tp-section" id="">

		<div class="postbox">
			<h3><?php _e('Resource Details - for advanced usage only'); ?> </h3>
			<div class="inside"> 
				<div class="tp_pd_type_panel">

					<div class="tp-simple-table">

						<div class="label"><?php _e('RID') ?></div>
						<input id="tp_pd_denied_msg1" name="tinypass[resource_id]" value="<?php echo esc_attr(stripslashes($ps->getResourceId(""))) ?>" size="50" maxlength="50">

						<div class="label"></div>
						<p class="info">Leave empty for RID of <b>'wp_post_XXX'</b> where XXX is the current wordpress post ID</p>
						<p class="info">Changing RID will cause previous purchases to be 'disconnected'.  Users that have already made a purchase will no longer have access.</p>
						<p class="info">RIDs should NOT be modified after purchases have been made</p>

					</div>
				</div>
			</div>
		</div>

	</div>

<?php } ?>
<?php

function __tinypass_payment_messaging_post_display(TPPaySettings $ps) { ?>

	<div class="tp-section" id="">

		<div class="postbox">
			<h3><?php _e('Denied access messaging'); ?> </h3>
			<div class="inside"> 
				<div class="tp_pd_type_panel">

					<div class="tp-simple-table">

						<div class="label"><?php _e('Header (optional)') ?></div>
						<input id="tp_pd_denied_msg1" name="tinypass[pd_denied_msg1]" value="<?php echo esc_attr(stripslashes($ps->getDeniedMessage1(""))) ?>" size="50" maxlength="50">
						<br>

						<div class="label"><?php _e('Description (optional)') ?></div>
						<textarea id="tp_pd_denied_sub1" rows="3" cols="49" name="tinypass[pd_denied_sub1]"><?php echo stripslashes($ps->getDeniedSub1("")) ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="postbox">
			<h3><?php _e('Pop-up purchase window'); ?> </h3>
			<div class="inside"> 

				<div class="tp-simple-table">
					<label><?php _e('Offer title (optional)') ?></label>
					<input name="tinypass[resource_name]" size="40" value="<?php echo $ps->getResourceName() ? stripslashes(esc_attr($ps->getResourceName())) : "" ?>" >
				</div>
				<br>

			</div>
		</div>
	</div>

<?php } ?>
<?php

/**
 * Display payment display options 
 */
function __tinypass_payment_messaging_display(TPPaySettings $ps, $num = "") {
	?>

	<div class="tp-section" id="">
		<h3><?php _e(($num ? "$num. " : "") . 'Customize your messaging'); ?> </h3>
		<p class="info">When users reach any restricted post, they will see an 
			<!--<a target="_blank" href="http://developer.tinypass.com">inline purchase section</a>--> 
			inline purchase section
			with your header,
			description, and the Tinypass purchase button
		</p>

		<div class="postbox">
			<h3><?php _e('Purchase section'); ?> </h3>
			<div class="inside"> 
				<div class="tp_pd_type_panel">

					<div class="tp-simple-table">

						<div class="label">Header</div>
						<input id="tp_pd_denied_msg1" name="tinypass[pd_denied_msg1]" value="<?php echo esc_attr(stripslashes($ps->getDeniedMessage1())) ?>" size="80" maxlength="80">
						<br>

						<div class="label">Description</div>
						<textarea id="tp_pd_denied_sub1" rows="3" cols="79" name="tinypass[pd_denied_sub1]"><?php echo stripslashes($ps->getDeniedSub1()) ?></textarea>
					</div>
				</div>


				<br>
				<div class="tp-simple-table">
					<div class="label">&nbsp;</div>
					<div>
						<input type="checkbox" name="tinypass[pd_order]" value="1" <?php echo checked($ps->isPostFirstInOrder()) ?>>
						<span class="info">Always display Pay-per-Post option first on restricted pages</span>
					</div>
				</div>
			</div>
		</div>

		<p class="info">Choose the default offer title for your 
			<!--<a target="_blank" href="http://developer.tinypass.com">purchase pop-up window</a>-->
			purchase pop-up window
			This title re-affirms what your users are buying in the pop-up.  Remember, you can override this offer name with a custom title
			inside each post on your site.
		</p>
		<div class="postbox">
			<h3><?php _e('Purchase pop-up window'); ?> </h3>
			<div class="inside"> 
				<div class="tp-simple-table">
					<label><?php _e('Offer title') ?></label>
					<input name="tinypass[resource_name]" size="40" value="<?php echo $ps->getResourceName() ? stripslashes(esc_attr($ps->getResourceName())) : stripslashes(bloginfo("name")) . " - Premium Access" ?>" >
				</div>
			</div>
		</div>

	</div>

<?php } ?>
<?php

/**
 * Display individual price option
 */
function __tinypass_price_option_display($opt, TPPaySettings $ps, $sub = true, $subWidth = 380) {

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
				<input type="text" size="8" maxlength="10" name="tinypass[<?php echo "po_p$opt" ?>]" value="<?php echo $price ?>">
			</td>
			<td width="<?php echo $subWidth ?>">
				<?php if ($sub): ?>
					<input class="recurring-opts-off" opt="<?php echo $opt ?>" type="radio" value="0" name="tinypass[po_recur<?php echo $opt ?>]" <?php echo checked($recur, false) ?> autocomplete="false">
				<?php endif; ?>
				<input type="text" size="5" maxlength="5" name="tinypass[<?php echo "po_ap$opt" ?>]" value="<?php echo $access_period ?>" class="po_ap_opts<?php echo $opt ?>">
				<?php echo __tinypass_dropdown("tinypass[po_ap_type$opt]", $times, $access_period_type, array('class' => "po_ap_opts$opt")) ?>
				<?php if ($sub): ?>
					<span style="margin-left:30px">&nbsp;</span>
					<input class="recurring-opts-on" id="<?php echo "po_recur$opt" ?>" type="radio" name="tinypass[po_recur<?php echo $opt ?>]" value="1 month" <?php checked($recur) ?> opt="<?php echo $opt ?>">
					<label for="<?php echo "po_recur$opt" ?>"><?php _e("Monthly Subscription") ?></label>
				<?php endif; ?>
			</td>
			<td width="270">
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