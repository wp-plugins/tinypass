<?php
/**
 * Available variables
 * 	
 * 	$count - current number of views
 *  $max - max views before meter expires
 *  $remaining - remaining number of view before expiration
 *  $class - position class
 *  $on_click_url - wordpress page to go when clicked
 *
 * 
 */
?>

<a href="<?php echo $on_click_url ?> ">
  <div id="tinypass-counter" class="<?php echo $class ?>">
    <div id="inner">
      <div class="num"><?php echo $remaining ?> </div>
      <div span class="text">views </div>
      <div span class="arrow">&rsaquo;</div>
      <div class="clear"></div>
    </div>
  </div>
</a>