<?php
add_action('wp_ajax_tp_enablePaywall', 'ajax_tp_enablePaywall');
add_action('wp_ajax_nopriv_tp_enablePaywall', 'ajax_tp_enablePaywall');

/**
 * Enable/Disable paywall via ajax call
 */
function ajax_tp_enablePaywall() {
  if (!current_user_can('edit_posts'))
    die();

  if (!wp_verify_nonce($_REQUEST['tinypass_nonce'], 'enable_paywall'))
    die('Security check failed');

  tinypass_include();

  $storage = new TPStorage();
  $ss = $storage->getSiteSettings();

  $form = $_POST['tinypass'];

  $ps = $storage->getPaywall($form['rid']);

  $ps->setEnabled(0);
  if (isset($form['en']) && $form['en'] == 1)
    $ps->setEnabled(1);

  $storage->savePaywallSettings($ss, $ps);

  echo "Saved";
  die;
}

function tinypass_paywalls_list() {

  $storage = new TPStorage();
  $pws = $storage->getPaywalls(true);

  if (count($pws) == 0)
    wp_redirect(menu_page_url("TinyPassEditPaywall"));
  ?>

  <div id = "poststuff">
    <div class="tp-settings">
      <h2><?php _e('My Paywalls'); ?></h2>
      <hr>

      <?php foreach ($pws as $rid => $ps) : ?>
        <?php tinypass_display_card($rid, $ps); ?>
      <?php endforeach; ?>

      <br>
      <div class="buttons">
        <a class="button" href="admin.php?page=TinyPassEditPaywall&rid="><?php _e("Add another") ?></a>
      </div>
    </div>

    <br><br>

  </div>


  <script>
    jQuery(function(){
      var $ = jQuery;
      $('.tp-slider').bind('click', function(event){
        $('.choice-selected', this).removeClass("choice-selected");
        var choice = $(event.target);
        choice.addClass('choice-selected');
        $("input", this).val(choice.parent().attr('val'));
        tinypass.enablePaywall($(this).parents("form"));
      })

      $('#tp-show-deleted').bind('click', function(event){
        $("#tp-deleted-paywalls").toggle();
        return false;
      })

    })
  </script>

<?php } ?>

<?php

function tinypass_display_card($rid, TPPaySettings $ps) {

  $tags = $ps->getPremiumTagsArray();
  $all = array();
  $count = 0;
  foreach ($tags as $name) {
    $td = get_term_by('name', $name, 'post_tag');
    $count += $td->count;
  }
  ?>

  <div class="paywall-card">
    <div class="slider">
      <form>
        <?php wp_nonce_field('enable_paywall', 'tinypass_nonce'); ?>
        <input type="hidden" name="tinypass[rid]" value="<?php echo $rid ?>">
        <?php echo tinypass_slider('tinypass[en]', array('Off' => '0', 'On' => '1'), $ps->getEnabled()) ?>
      </form>
    </div>
    <div class="type"> <?php echo "{$ps->getModeName()} ({$ps->getModeNameReal()}) " ?> </div>
    <div class="title"> <?php echo $ps->getResourceName() ?> </div>

    <div class="footer">
      <div class="leftcol">
        <div class="section">
          <div class="value"><?php echo $count ?> Items</div>
          <div class="label"><?php _e("Content") ?></div>
        </div>            
        <div class="section">
          <div class="value"><?php _e($ps->getPremiumTags(',')) ?></div>
          <div class="label"><?php _e("Tags") ?></div>
        </div>            
        <div class="clear"></div>
      </div>

      <div class="action">
        <a class="button" href="admin.php?page=TinyPassEditPaywall&rid=<?php echo $rid ?>">Edit</a>
      </div>            
      <div class="clear"></div>
    </div>

  </div>

  <?php
}
?>