(function($) {
	$(function() {
		
		// Check to make sure the input box exists yy-mm-dd
		if( 0 < $('.datepicker').length ) {
		  $('.datepicker').datepicker({dateFormat : 'yy-mm-dd'});
		} // end if 
		
	});
}(jQuery));