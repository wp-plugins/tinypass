<?php
/*
 * This file contains all form related helper methods for disaplying
 * form content either from settings, post settings, or various other popups
 */
wp_enqueue_script("jquery");
wp_enqueue_script("jquery-ui");

/**
 * show settings section head
 */
function __tinypass_section_head(TPPaySettings $ps, $num, $text = '', $html = '') {
	?>

	<div class="tp-section-header">
		<div class="num"><?php echo $num ?></div>
		<?php echo $text ?>
		<?php echo $html ?>
	</div>

<?php } ?>
<?php

/**
 * Alternative settings section head 
 */
function __tinypass_section_head_alt($text = '') {
	?>

	<div class="tp-section-header">
		<?php echo $text ?>
	</div>

<?php } ?>
<?php

/**
 * Show the metered content section
 */
function __tinypass_save_buttons(TPPaySettings $ps, $edit = false) {
	?>

	<p>
		<input type="submit" name="_Submit" value="Save Changes" tabindex="4" class="button-primary" />
	</p>

<?php } ?>
<?php

function __tinypass_mlite_display(TPPaySettings $ps) {

	$prodId = stripslashes(esc_attr($ps->getPaywallIDProd()));
	$sandId = stripslashes(esc_attr($ps->getPaywallIDSand()));
	?>
	<div class="tp-section">

		<div class="desc">Sign up or login to <a href="dashboard.tinypass.com">Tinypass Dashboard</a> to create a new paywall.<br>
			A Paywall ID number will be generated for you.  Copy it to your clipboard
		</div>
		<br> <br>

		<div class="info">
			<div class="heading">Enter your Paywall ID</div>
			<div class="desc">Create a paywall on the <a href="dashboard.tinypass.com">Tinypass Dashboard</a> and paste the ID number here.</div>
			<br>
			<div class="desc">If you're just testing, create a paywall on the <a href="sandbox.tinypass.com">Tinypass Dashboard</a> and paste the ID number here.</div>
		</div>
		<div class="body">

			<div class="postbox">
				<h3><?php _e('Paywall ID'); ?> </h3>
				<div class="inside"> 

					<div class="tp-simple-table">
						<input name="tinypass[mlite_pwid_prod]" size="20" value="<?php echo $prodId ?>" >
					</div>

				</div>
			</div>
			<div class="postbox">
				<h3><?php _e('Sandbox Paywall ID'); ?> </h3>
				<div class="inside"> 

					<div class="tp-simple-table">
						<input name="tinypass[mlite_pwid_sand]" size="20" value="<?php echo $sandId ?>" >
					</div>

				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>

<?php } ?>
<?php

/**
 * Tag display section
 */
function __tinypass_tag_display(TPPaySettings $ps) {

	$name = stripslashes(esc_attr($ps->getResourceName()));

	if (!$name)
		$name = get_bloginfo("name") . " - Premium Content";
	?>
	<div class="tp-section">
		<div class="info">
			<div class="heading">Add tags</div>
			<div class="desc">All tagged posts will automatically be restricted with this paywall.</div>
		</div>
		<div class="body">
			<div class="postbox"> 
				<h3><?php echo _e("Select the tags of the content you want restricted") ?></h3>
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
		<div class="clear"></div>
	</div>
<?php } ?>
<?php

/**
 * Misc options display section
 */
function __tinypass_misc_display(TPPaySettings $ps) {
	?>
	<div class="tp-section">
		<div class="info">
			<div class="heading">Customize</div>
			<div class="desc">Enable / disable these features of additional behavior</div>
		</div>
		<div class="body">
			<div class="postbox"> 
				<h3><?php echo _e("&nbsp;") ?></h3>
				<div class="inside"> 
					<input type="checkbox" name="tinypass[mlite_track_homepage]" value="1" <?php echo checked($ps->isTrackHomePage()) ?>>
					<?php echo _e("Track on home page visit - visiting your homepage will count as a view") ?>
					<br> <br>
					<input type="checkbox" name="tinypass[mlite_readon_enabled]" value="1" <?php echo checked($ps->isReadOnEnabled()) ?>>
					<?php echo _e("Enable ReadOn - Allows home page posts to be fetch via ajax") ?>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
<?php } ?>
