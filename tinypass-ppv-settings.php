<?php

function tinypass_ppv_settings() {

	$storage = new TPStorage();
	$errors = array();

	if (isset($_POST['_Submit'])) {
		$ss = $storage->getSiteSettings();
		$errors = $ss->updatePPVSettings($_POST['tinypass']);
		$storage->saveSiteSettings($ss);
	}

	$ss = $storage->getSiteSettings();
	$modeStrict = $ss->getModeSettings(TPSiteSettings::MODE_STRICT);
	$modeMetered = $ss->getModeSettings(TPSiteSettings::MODE_METERED);
	?>

	<div id="poststuff">

		<?php if (!count($errors)): ?>
			<?php if (!empty($_POST['_Submit'])) : ?>
				<div class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
			<?php endif; ?>
		<?php else: ?>
			<div id="tp-error" class="error fade"><p></p></div>
		<?php endif; ?>


		<div class="wrap">
			<h2><?php _e('TinyPass'); ?></h2>
			<form action="" method="post">

				<div class="">
					<div class="inside">
						<table class="form-table">

							<tr>
								<td>
									<div id="tp_modes">
										<input id="tp_mode" name="tinypass[mode]" type="hidden">
										<div id="tp_mode1" class="choice" value="<?php echo TPSiteSettings::MODE_OFF ?>" <?php checked($ss->getMode(), TPSiteSettings::MODE_OFF) ?> >Off</div>
										<div id="tp_mode2" class="choice" value="<?php echo TPSiteSettings::MODE_METERED ?>" <?php checked($ss->getMode(), TPSiteSettings::MODE_METERED) ?>>Metered</div>
										<div class="clear"></div>
									</div>
								</td>
							</tr>

						</table>
					</div>
				</div>

				<div id="tp_mode1_panel" class="tp_mode_panel">
					TinyPass is disabled
				</div>
				<div id="tp_mode2_panel" class="tp_mode_panel">
					<div class="heading">
						<h3><?php _e("Metered Mode")?></h3>
						<p>
								Create a premium section on your site in minutes.  Select the tags you want to restrict, choose your price options, and we'll do the rest.
						</p>
					</div>
					<?php __tinypass_ppv_payment_display($modeStrict) ?>
				</div>
				<p>
					<input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
				</p>

			</form>
		</div>
	</div>

	<div id="tp-slot"></div>
	<script></script>


	<?php if (count($errors)): ?>
		<?php foreach ($errors as $key => $value): ?>
			<script>tinypass.doError("<?php echo $key ?>", "<?php echo $value ?>");</script>
		<?php endforeach; ?>
	<?php endif; ?>


<?php } ?>

