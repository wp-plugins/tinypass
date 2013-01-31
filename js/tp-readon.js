jQuery(function () {
	var clicked = {};
	function expand_readon(readOnLink, id, fetch){
		if(fetch){
			var url = jQuery(readOnLink).attr("url");
			jQuery.ajax({
				url: url,
				success: function(data) {
					jQuery('#slot-' + id).html(data).slideToggle("slow");
				}	
			});
		}else {
			jQuery('#slot-' + id).slideToggle("slow");
		}
	};


	jQuery("body").delegate("a.readon-link", "click", function(){

		var expand = jQuery(this).html() == 'Read On';
		
		var href = jQuery(this).attr("href");

		var meter = getTPMeter();
		if(expand && !clicked[href]){
			if (meter.isExpiredNextClick()) {
				meter.showOffer();	
				return false;
			}else {
				meter.processClick(this);
			}
		}
		
		jQuery(this).html(expand ? 'Collapse Post' : 'ReadOn');

		// get the url that was clicked
		clicked[href] = 1;
			
		expand_readon(jQuery(this), jQuery(this).attr("id"), expand);
		return false;
	});
		 
});

