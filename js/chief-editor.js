jQuery(document).ready(function($) {
 
  $('.chief-editor-bat-submit').click(function() {

	var $this = $(this);
	//alert('inside jquery function');
	console.log( "ready!" );	
	var $formId = $(this).parents('form');
	//alert($formId.attr( "id" ));
	
	var fields = $( $formId ).serializeArray();
    var $postID = fields[0].value;
	var $blogID = fields[1].value;
	var $authorID = fields[2].value;
	
	
	//alert($postID.val());
	data = {
	  action : 'ce_send_author_std_validation_email',
	  postID : $postID,
	  blogID : $blogID,
	  authorID : $authorID
	}
	  
	  $.post(ajaxurl, data, function (response) {
		
		//alert(response);
		var $submit_button = $this;
		
		//alert ($submit_button.attr('id'));
		var $parent = $submit_button.parents('form');
		//alert($parent.attr('id'));
		var $div = $parent.parent('.wrap');
		//alert($div.attr('id'));
		var $ce_dialog = $div.children('#ce_dialog_email');
		
		$ce_dialog.html(response);
		$ce_dialog.show();
		
	  });
	
	return false;
	
  });
  
  $('body').on('click', '.chief-editor-bat-send-confirm', function() {
  //$('#chief-editor-bat-send-confirm').click(function() {

	//console.log( "ready! .chief-editor-bat-send-confirm" );
	//var $this = $(this);
	//alert('inside jquery function : chief-editor-bat-send-confirm');	
	var $formId = $(this).parents('form');
	//alert($formId.attr( "id" ));
	
	var fields = $( $formId ).serializeArray();
    var $postID = fields[0].value;
	var $blogID = fields[1].value;
	
	
	//alert($postID.val());
	data = {
	  action : 'ce_send_author_std_validation_email_confirmed',
	  postID : $postID,
	  blogID : $blogID
	  
	}
	  
	  $.post(ajaxurl, data, function (response) {
		
		alert(response);
		
		
	  });
	
	return false;
	
  });
					  
});