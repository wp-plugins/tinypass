<?php

function tinypass_ppv_settings() {

	$storage = new TPStorage();
	$errors = array();

	if (isset($_POST['_Submit'])) {
		$ss = $storage->getSiteSettings();
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
			<h2><?php _e('TinyPass'); ?></h2>
			<form method="post">

				<div class="">
					<div class="inside">
						<div id="tp_modes">
							<input id="tp_ppv" name="tinypass[ppv]" type="hidden">
							<div id="tp_mode1" class="choice" value="0" <?php checked(!$ss->isPPVEnabled()) ?> >Off</div>
							<div id="tp_mode2" class="choice" value="1" <?php checked($ss->isPPVEnabled()) ?>>On</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				<div class="clear"></div>

				<div id="tp_mode1_panel" class="tp_mode_panel">
					Pay-per-view is disabled
				</div>
				<div id="tp_mode2_panel" class="tp_mode_panel">
					<div class="heading">
						<h3><?php _e("Customize your PPV messaging") ?></h3>
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


	<script>
				
		jQuery(function(){
			var $ = jQuery;
			$('#tp_modes .choice').hover(
			function(){
				$(this).addClass("choice-on");
			}, 
			function(){
				$(this).removeClass("choice-on");
			});


			$('#tp_modes .choice').click(function(){
				$('#tp_modes .choice').removeClass("choice-selected");
				$('#tp_modes .choice').removeAttr("checked");

				$(this).addClass("choice-selected");
				$(this).attr("checked", "checked");
								
				var elem = $(".choice[checked=checked]");
				var id = elem.attr("id");

				scope = '#' + id + '_panel';

				$("#tp_ppv").val(elem.attr('value'));

				tinypass.fullHide('.tp_mode_panel');
				tinypass.fullShow(scope);

			});
			$("#tp_modes .choice[checked=checked]").trigger('click');

		});

	</script>


	<?php if (count($errors)): ?>
		<?php foreach ($errors as $key => $value): ?>
			<script>tinypass.doError("<?php echo $key ?>", "<?php echo $value ?>");</script>
		<?php endforeach; ?>
	<?php endif; ?>


<?php } ?>