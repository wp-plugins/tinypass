<?php

function tinypass_paywalls_list() {

  $storage = new TPStorage();
  $pws = $storage->getPaywalls(true);
  ?>

  <div id = "poststuff">
    <div class="tp-settings">
      <h2><?php _e('Content Paywalls'); ?></h2>
      <hr>

      <div class = "postbox">

        <h3>Paywalls</h3>
        <div class="inside">
          <?php foreach ($pws as $rid => $pw) : ?>

            <div>
              <?php echo print_r($pw->getSummaryFields()); ?>
              <a class="button" href="admin.php?page=TinyPassEditPaywall&rid=<?php echo $rid ?>">Edit</a>
            </div>

          <?php endforeach; ?>

          <br>
          <div class="buttons">
            <a class="button" href="admin.php?page=TinyPassEditPaywall&rid=">Add New</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php
}
?>