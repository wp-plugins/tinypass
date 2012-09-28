<?php

function tinypass_site_settings() {

	$storage = new TPStorage();
	if (isset($_POST['_Submit'])) {
		$ss = $storage->getSiteSettings();
		$ss->mergeValues($_POST['tinypass']);
		$storage->saveSiteSettings($ss);
	}

	$ss = $storage->getSiteSettings();
	?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<?php if (!empty($_POST['_Submit'])) : ?>
			<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
		<?php endif; ?>

		<div class="">
			<h2><?php _e('TinyPass Settings'); ?></h2>
			<form action="" method="post" id="tinypass-conf">

				<div class="postbox">
					<h3><?php _e('Enabled') ?> </h3>
					<div class="inside">
						<input type="radio" name="tinypass[en]" value="0" <?php echo checked($ss->isEnabled(), false) ?>><label><?php _e('Disabled'); ?></label><br>
						<input type="radio" name="tinypass[en]" value="1" <?php echo checked($ss->isEnabled(), true) ?>><label><?php _e('Enabled'); ?></label>
					</div>
				</div>

				<div class="postbox">
					<h3><?php _e('Environment') ?> </h3>
					<div class="inside">
						<input type="radio" name="tinypass[env]" value="0" <?php echo checked($ss->isSand(), true) ?>><label><?php _e('Sandbox - for testing only'); ?></label><br>
						<input type="radio" name="tinypass[env]" value="1" <?php echo checked($ss->isProd(), true) ?>><label><?php _e('Live - for live payments'); ?></label>
					</div>
				</div>

				<div class="postbox">
					<h3><?php _e('Application IDs and Keys'); ?> </h3>
					<div class="inside">

						<table class="form-table">

							<tr valign="top">
								<th scope="row"><?php _e('Application ID (Sandbox)'); ?></th>
								<td>
									<input id="aid_sand" name="tinypass[aid_sand]" type="text" size="10" maxlength="10" value="<?php echo $ss->getAIDSand() ?>"/><br>
									<span class="description">The Application ID (sandbox) can be retrieved from your account at <a href="http://sandbox.tinypass.com/member/merch">http://sandbox.tinypass.com</a></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><?php _e('Application Secret Key (Sandbox)'); ?></th>
								<td>
									<input id="secret_key_sand" name="tinypass[secret_key_sand]" type="text" size="40" maxlength="40" value="<?php echo $ss->getSecretKeySand() ?>" style="" /><br>
									<span class="description">The Secret Key (sandbox) can be retrieved from your account at <a href="http://sandbox.tinypass.com/member/merch">http://sandbox.tinypass.com</a></span><br>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Application ID (Live)'); ?></th>
								<td>
									<input id="aid_prod" name="tinypass[aid_prod]" type="text" size="10" maxlength="10" value="<?php echo $ss->getAIDProd() ?>"/><br>
									<span class="description">The Application ID (Live) can be retrieved from your account at <a href="http://dashboard.tinypass.com/member/merch">http://www.tinypass.com</a></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><?php _e('Application Secret Key (Live)'); ?></th>
								<td>
									<input id="secret_key_prod" name="tinypass[secret_key_prod]" type="text" size="40" maxlength="40" value="<?php echo $ss->getSecretKeyProd() ?>" style="" /><br>
									<span class="description">The Secret Key (Live) can be retrieved from your account at <a href="http://dashboard.tinypass.com/member/merch">http://www.tinypass.com</a></span>
								</td>
							</tr>

						</table>
					</div>
				</div>

				<p>
					<input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
				</p>

			</form>
		</div>
	</div>

<?php } ?>