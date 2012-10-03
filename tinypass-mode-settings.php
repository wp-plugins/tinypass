<?php

function tinypass_mode_settings() {

  $storage = new TPStorage();
  $errors = array();

  if (isset($_POST['_Submit'])) {
    $ss = $storage->getSiteSettings();
    $errors = array();
    $ps = $ss->validatePaySettings($_POST['tinypass'], $errors);
    $storage->savePaywallSettings($ss, $ps);
  }

  $ps = $storage->getPaywall("wp_bundle1", true);
  ?>

  <div id="poststuff">

    <?php if (!count($errors)): ?>
      <?php if (!empty($_POST['_Submit'])) : ?>
        <div class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
      <?php endif; ?>
    <?php else: ?>
      <div id="tp-error" class="error fade"></div>
    <?php endif; ?>

    <div class="tp-settings">
      <h2><?php _e('Paywall settings'); ?></h2>
      <hr>

      <div class="tp-section">
        <h3><?php _e("1. " . 'Select your paywall settings'); ?> </h3>

        <div id="tp_modes">
          <div id="tp_mode1" class="choice" value="<?php echo TPPaySettings::MODE_OFF ?>" <?php checked($ps->getMode(), TPPaySettings::MODE_OFF) ?> >Off</div>
          <div id="tp_mode2" class="choice" value="<?php echo TPPaySettings::MODE_METERED ?>" <?php checked($ps->getMode(), TPPaySettings::MODE_METERED) ?>>Preview</div>
          <div id="tp_mode3" class="choice" value="<?php echo TPPaySettings::MODE_STRICT ?>" <?php checked($ps->getMode(), TPPaySettings::MODE_STRICT) ?>>No-Preview</div>
          <div class="clear"></div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="hr"></div>

      <div id="tp_mode1_panel" class="tp_mode_panel">
        <form action="" method="post" autocomplete="off">
          <input class="tp_mode" name="tinypass[mode]" type="hidden">
          <div style="float:right">
            RID:<input type="text" readonly="true" name="tinypass[resource_id]" value="<?php echo $ps->getResourceId() ?>">
          </div>
          <div class="heading">
            <p>
              Create a premium section on your site in minutes.  Select the tags you want to
              restrict, choose your price options, and we'll do the rest. 
              <br>
              Our plugin supports
              two settings: metered browsing or a strict paywall.  Take a look at how each works
              and decide which works best for your content.
            </p>
          </div>
          <div>
            <img src="http://developer.tinypass.com/_media/cmsimages/wp_off_copy.png">
          </div>

          <p>
            <input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
          </p>
        </form>
      </div>


      <div id="tp_mode2_panel" class="tp_mode_panel">
        <div>
          <p>
            Users can look at your content for a certain number of views, or for a certain time period.  After the limit is reached, your
            users will have to pay to continue
          </p>
        </div>

        <form action="" method="post" autocomplete="off">
          <input class="tp_mode" name="tinypass[mode]" type="hidden">
          <div style="float:right">
            RID:<input type="text" readonly="true" name="tinypass[resource_id]" value="<?php echo $ps->getResourceId() ?>">
          </div>
          <?php $num = 1; ?>
          <?php __tinypass_tag_display($ps, ++$num) ?>
          <?php __tinypass_metered_display($ps, ++$num) ?>
          <?php __tinypass_pricing_display($ps, ++$num) ?>
          <?php __tinypass_payment_messaging_display($ps, ++$num) ?>
          <?php __tinypass_purchase_page_display($ps, ++$num) ?>

          <p>
            <input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
          </p>
        </form>

      </div>

      <div id="tp_mode3_panel" class="tp_mode_panel">
        <div>
          <p>
            All of your content is locked down from the start.  When users click on any locked post, they'll be asked to pay for access immediately
          </p>
        </div>
        <?php $num = 1; ?>
        <form action="" method="post" autocomplete="off">
          <input class="tp_mode" name="tinypass[mode]" type="hidden">
          <div style="float:right">
            RID:<input type="text" readonly="true" name="tinypass[resource_id]" value="<?php echo $ps->getResourceId() ?>">
          </div>
          <?php __tinypass_tag_display($ps, ++$num) ?>
          <?php __tinypass_pricing_display($ps, ++$num) ?>
          <?php __tinypass_payment_messaging_display($ps, ++$num) ?>
          <?php __tinypass_purchase_page_display($ps, ++$num) ?>

          <p>
            <input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
          </p>
        </form>
      </div>
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

        $(".tp_mode").val(elem.attr('value'));

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
        if(tag == "")
          return;

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
                      
      $('.recurring-opts-off').bind('change', function(){
        var index = $(this).attr("opt");
        if($(this).is(":checked")){
          $(scope + " .po_ap_opts" + index).removeAttr("disabled");
        }
      });

      $('.recurring-opts-on').bind('change', function(){
        var index = $(this).attr("opt");
        if($(this).is(":checked")){
          $(scope + " .po_ap_opts" + index).attr("disabled", "disabled");
        } else {
          $(scope + " .po_ap_opts" + index).removeAttr("disabled");
        }
      })
      $('.recurring-opts-on').trigger('change');

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

