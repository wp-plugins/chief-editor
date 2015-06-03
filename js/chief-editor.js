jQuery(document).ready(function($) {
  
	jQuery(function() {
        jQuery( ".datepicker" ).datepicker({
            dateFormat : "dd-mm-yy"
        });
    });
    
	
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
		
	  }
			);
	
	return false;
	
  }
									 );
  
  $('body').on('click', '.chief-editor-bat-send-confirm', function() {
	//$('#chief-editor-bat-send-confirm').click(function() {
	//alert($(this).html());
	//console.log( "ready! .chief-editor-bat-send-confirm" );
	//var $this = $(this);
	//alert('inside jquery function : chief-editor-bat-send-confirm');	
	var formId = $(this).parents('form');
	//alert($formId.attr( "id" ));
	
	var fields = $( formId ).serializeArray();
	//console.log( fields);
	var post_ID = fields[0].value;
	var blog_ID = fields[1].value;
	
	console.log("post "+ post_ID+" on blog id "+blog_ID);
	
	//alert($postID.val());
	data = {
	  action : 'ce_send_author_std_validation_email_confirmed',
	  postID : post_ID,
	  blogID : blog_ID
	  
	}
	  
	  //alert(formId.html());
	var ce_loading_icon = $(formId).children("div").children(".ce_loading_icon");
	//alert(ce_loading_icon.attr("id"));
	//console.log(ce_loading_icon);
	
	ce_loading_icon.show();
	$(this).prop('disabled', true);

	
	$.post(ajaxurl, data, function (response) {
	    
	  ce_loading_icon.hide();
	  $(this).prop('disabled', false);
	  $(formId).children("div").hide();
	  alert(response);
	}
		  );
	
	return false;
	
  }
			  );
  
}
					  );