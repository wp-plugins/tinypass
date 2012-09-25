
<style type="text/css">
	.tinypass_display {
		text-align: center;
	}
	.tinypass_display .tinypass_denied_message {
		font-size: 1.1em;
		margin-bottom: 10px;
	}
	.tinypass_button_slot {
		float:left;
		width:45%;
		border:1px solid #aaa;
		border-radius: 10px;
		padding-top:20px;
		padding-bottom:20px;
		margin-left:5px;
		-moz-box-shadow: 3px 3px 3px #888;
		-webkit-box-shadow: 2px 2px 3px #888;
		box-shadow: 2px 2px 3px #888;
		min-height:50px;
	}

</style>

<script type="text/javascript">
	if(typeof tinypass_reloader != 'function') {
		function tinypass_reloader(status){
			if(status.state == 'granted'){ window.location.reload(); }
		}
	}
</script>


<div class="tinypass_display">
	<div class="tinypass_button_slot">
		<div class="tinypass_denied_message"><?php echo $message1 ?></div>
		<?php echo $button1 ?>
	</div>
	<div class="tinypass_button_slot">
		<div class="tinypass_denied_message"><?php echo $message2 ?></div>
		<?php echo $button2 ?>
	</div>
	<div style="clear:both"></div>
</div>

