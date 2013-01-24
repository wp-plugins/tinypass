$(function () {
	function expand_readon(readOnLink, id, fetch){
		if(fetch){
			var url = jQuery(readOnLink).attr("rurl");
			$.ajax({
				url: url,
				success: function(data) {
					$('#' + id).html(data).slideToggle("slow");
				}	
			});
		}else {
			$('#' + id).slideToggle("slow");
		}
	};

	// assume that "Read On" was not clicked
	var readOnClick = false;
	$("a.readon-link").toggle(
		function(){
			// replace the hyperlink text "Read On" with "Collapse Post"
			$(this).html('Collapse Post');
			// which means we've clicked on the "Read On" button
			readOnClick = true;
		},
		function () {
			$(this).html($(this).attr('longdesc'));
		});

	$("a.readon-link").click(function () {
		var clickMode;
		// get the url that was clicked
		var hrefClicked = $(this).attr("href");
		// determine whether "Read On" or "Collaped" was clicked
		if (readOnClick) {
			clickMode = "Read On";
		} else {
			clickMode = "Collapsed";
		}
		// and reset the "Read On" flag
		readOnClick = false;
			 
		//expand($(this).parent().parent().find(".extended").attr("id"));
		expand_readon($(this), $(this).attr("rid"), clickMode == 'Read On');
	});
		 
});

