<?php

function tinypass_ppv_settings() {

	$storage = new TPStorage();
	$errors = array();

	if (isset($_POST['_Submit'])) {
		$ss = $storage->getSiteSettings();
		if (!isset($_POST['tinypass']['ppv']))
			$_POST['tinypass']['ppv'] = 0;

		$ss->mergeValues($_POST['tinypass']);
		$storage->saveSiteSettings($ss);
	}

	$ss = $storage->getSiteSettings();
	?>

	<div id="poststuff">

		<?php if (!count($errors)): ?>
			<?php if (!empty($_POST['_Submit'])) : ?>
				<div class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
			<?php endif; ?>
		<?php else: ?>
			<div id="tp-error" class="error fade"><p></p></div>
		<?php endif; ?>


		<div class="">
			<h2><?php _e('Pay-per-post settings'); ?></h2>
			<hr>

			<p class="info">Want to allow users to buy individual pages or posts?  You can set the prices using the post options in the post editor screen.</p>
			<form method="post">

				<div class="postbox">
					<div class="inside">
						<input type="checkbox" id="ppv" name="tinypass[ppv]" value="1" <?php checked($ss->isPPPEnabled()) ?>>
						<label for="ppv" >Enable the sale of purchase individual posts</label>
					</div>
				</div>
				<div class="clear"></div>

				<div id="tp_mode2_panel" class="tp_mode_panel">
					<div class="heading">
						<p>
							When users reach any restricted post, they will see an inline block with your header, description, and the Tinypass purchase button
						</p>
					</div>
					<?php __tinypass_ppv_payment_display($ss) ?>
				</div>
				<p>
					<input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
				</p>

			</form>
		</div>
	</div>

	<?php if (count($errors)): ?>
		<?php foreach ($errors as $key => $value): ?>
			<script>tinypass.doError("<?php echo $key ?>", "<?php echo $value ?>");</script>
		<?php endforeach; ?>
	<?php endif; ?>


<?php } ?>