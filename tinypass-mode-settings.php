<?php

function tinypass_mode_settings() {

	$storage = new TPStorage();
	$errors = array();

	if (isset($_POST['_Submit'])) {
		$ss = $storage->getSiteSettings();
		$errors = $ss->updatePaySettings($_POST['tinypass']);
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

		<div class="">
			<h2><?php _e('TinyPass'); ?></h2>
			<form action="" method="post">

				<div class="inside">
					<div id="tp_modes">
						<input id="tp_mode" name="tinypass[mode]" type="hidden">
						<div id="tp_mode1" class="choice" value="<?php echo TPSiteSettings::MODE_OFF ?>" <?php checked($ss->getMode(), TPSiteSettings::MODE_OFF) ?> >Off</div>
						<div id="tp_mode2" class="choice" value="<?php echo TPSiteSettings::MODE_METERED ?>" <?php checked($ss->getMode(), TPSiteSettings::MODE_METERED) ?>>Metered</div>
						<div id="tp_mode3" class="choice" value="<?php echo TPSiteSettings::MODE_STRICT ?>" <?php checked($ss->getMode(), TPSiteSettings::MODE_STRICT) ?>>Strict</div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="clear"></div>

				<div id="tp_mode1_panel" class="tp_mode_panel">
					TinyPass is disabled
				</div>
				<div id="tp_mode2_panel" class="tp_mode_panel">
					<div class="heading">
						<h3><?php _e("Metered Mode") ?></h3>
						<p>
							Create a premium section on your site in minutes.  Select the tags you want to restrict, choose your price options, and we'll do the rest.
						</p>
					</div>

					<?php __tinypass_tag_display($modeMetered) ?>
					<?php __tinypass_pricing_display($modeMetered) ?>
					<?php __tinypass_metered_display($modeMetered) ?>
					<?php __tinypass_payment_display($modeMetered) ?>
					<?php __tinypass_purchase_page_display($modeMetered) ?>
				</div>
				<div id="tp_mode3_panel" class="tp_mode_panel">
					<div class="heading">
						<h3><?php _e("Strict Mode") ?></h3>
						<p>
							Create a premium section on your site in minutes.  Select the tags you want to restrict, choose your price options, and we'll do the rest.
						</p>
					</div>
					<?php __tinypass_tag_display($modeStrict) ?>
					<?php __tinypass_pricing_display($modeStrict) ?>
					<?php __tinypass_payment_display($modeStrict) ?>
					<?php __tinypass_purchase_page_display($modeStrict) ?>
				</div>

				<p>
					<input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
				</p>

			</form>
		</div>
	</div>

	<script>
		var scope = '';
		jQuery(function(){
			var $ = jQuery;

			//setup modes
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

				$("#tp_mode").val(elem.attr('value'));

				tinypass.fullHide('.tp_mode_panel');
				tinypass.fullShow(scope);

			});
			$("#tp_modes .choice[checked=checked]").trigger('click');

			$(".tag-holder").click(function(event){
				if($(event.target).hasClass("remove"))
					$(event.target).parent().remove();
			})

			function addTag(){
				var tag = $(".premium_tags", scope).val();

				$(".tag-holder", scope).append("<div class='tag'><div class='text'>" + tag + "</div><div class='remove'></div>" 
					+ "<input type='hidden' name='tinypass[tags][]' value='" + tag  + "'>" 
					+ "</div>"
			);
				$(".premium_tags", scope).val("");
				$(".premium_tags", scope).focus();
			}
			$(".tag-entry .add_tag").click(function(){
				addTag();
			});

			$(".tag-entry .premium_tags").keypress(function(event){
				if(event.which == 13){
					addTag();
					event.stopPropagation();
					return false;
				}
			});
				
			//toggle access_period after recurring is changed
			$('.recurring-opts').bind('change', function(){
				var index = $(this).attr("opt");
				if($(this).is(":checked")){
					$(scope + " .po_ap_opts" + index).attr("disabled", "disabled");
				} else {
					$(scope + " .po_ap_opts" + index).removeAttr("disabled");
				}
			})
			$('.recurring-opts').trigger('change');

			$('.premium_tags').suggest("admin-ajax.php?action=ajax-tag-search&tax=post_tag",{minchars:2,multiple:false,multipleSep:""})

			tinypass.showMeteredOptions(document.getElementsByName("tinypass[metered]")[0])


		});
	</script>


	<?php if (count($errors)): ?>
		<?php foreach ($errors as $key => $value): ?>
			<script>tinypass.doError("<?php echo $key ?>", "<?php echo $value ?>");</script>
		<?php endforeach; ?>
	<?php endif; ?>


<?php } ?>

