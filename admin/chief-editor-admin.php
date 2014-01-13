<?php 

if (isset($_POST['submitDate'])) {
  
  echo $_POST["datepicker"] . '_' .$_POST["blog_id"]. '_'.$_POST["post_id"];
  //echo $_POST["name"];
  updatePostDate($_POST["blog_id"],$_POST["post_id"],$_POST["datepicker"]);
  
} else if (isset($_POST['unschedulePost'])) {
  
  echo 'Unscheduling post : '.$_POST["datepicker"] . '_' .$_POST["blog_id"]. '_'.$_POST["post_id"];
  unschedulePost( $_POST["blog_id"],$_POST["post_id"] );
}


if (!defined('CHIEF_EDITOR_PLUGIN_NAME'))
    define('CHIEF_EDITOR_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (!defined('CHIEF_EDITOR_PLUGIN_DIR'))
    define('CHIEF_EDITOR_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . CHIEF_EDITOR_PLUGIN_NAME);

if (!defined('CHIEF_EDITOR_PLUGIN_URL'))
    define('CHIEF_EDITOR_PLUGIN_URL', WP_PLUGIN_URL . '/' . CHIEF_EDITOR_PLUGIN_NAME);


function updatePostDate($blog_id, $post_id, $post_date) {

  //echo 'date' . $post_date . ' post ID : ' . $post_id .'\r\n';
		
		switch_to_blog( $blog_id );
		
		$operation = 'edit';
  	    $newpostdata = array();
		
	  if ( strtotime($post_date) < strtotime( "tomorrow" ) ) {
		echo strtotime($post_date) . '<'. strtotime( "tomorrow" ) ."\r\n";
		echo 'cannot publish artilces from here, only schedule, dates in future';
		return;
		
		//$status = 'publish';    
        $newpostdata['post_status'] = $status;
        $newpostdata['post_date'] = date( 'Y-m-d H:i:s',  $post_date );

        // Also pass 'post_date_gmt' so that WP plays nice with dates
        $newpostdata['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $post_date );

    } elseif ( strtotime($post_date) > strtotime( 'today' ) ) {
		
		echo strtotime($post_date) . '>'. strtotime( "today" ) .'\r\n';
        $status = 'future';    
        $newpostdata['post_status'] = $status;
        $newpostdata['post_date'] = date( 'Y-m-d H:i:s', strtotime($post_date) );
		//echo $post_date . '=='. strtotime($post_date).'=='.$newpostdata['post_date'].'\r\n';
        // Also pass 'post_date_gmt' so that WP plays nice with dates
        $newpostdata['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime($post_date) );
    }

    if ('insert' == $operation) {
        $err = wp_insert_post($newpostdata, true);
	  
    } elseif ('edit' == $operation) {
	  
	  //echo 'edit ==' .$operation."\r\n";
        $newpostdata['ID'] = $post_id;
	  	$newpostdata['edit_date'] = true;
	  
	  //echo $newpostdata['ID'] . "_" . $newpostdata['edit_date']. "_" . $newpostdata['post_status']. "_" . $newpostdata['post_date'] . "_" . $newpostdata['post_date_gmt'] ."\r\n";
	  
        $err = wp_update_post($newpostdata);
	  //echo "wp_update_post::Error return: ".$err ."\r\n";
    }
}


function unschedulePost($blog_id, $post_id) {
   
  //echo 'unschedulePost-1';
  switch_to_blog( $blog_id );
  //echo 'unschedulePost-2';
  $newpostdata = array();
  $status = 'draft';
  //echo 'unschedulePost-3 '.$status;
  $newpostdata['post_status'] = $status;
  $newpostdata['ID'] = $post_id;
  //echo 'unschedulePost-4 ' .$newpostdata['ID'];
  $err = wp_update_post($newpostdata);
  //echo 'wp_update_post::Error return: '.$err .'\r\n';
  
}


if(!class_exists('ChiefEditorSettings')) {  
  class ChiefEditorSettings
	{
    	/**
     	* Holds the values to be used in the fields callbacks
     	*/
    	private $options;
    	/**
     	* Start up
     	*/
    	public function __construct()
    	{
        	add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        	add_action( 'admin_init', array( $this, 'page_init' ) );
    	}
    	/**
     	* Add options page
     	*/
    	public function add_plugin_page()
    	{
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Chief Editor', 
            'delete_others_pages', 
            'chief-editor-admin', 
            array( $this, 'create_admin_page' )
        );
    	}
	  
	  
	/**
	 * Registers and enqueues admin-specific styles.
	 *
	 * @version		1.0
     * @since 		1.0
	 */
	public function register_admin_styles() {
	
		wp_enqueue_style( 'jquery-ui-datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'wp-jquery-date-picker', plugins_url( CHIEF_EDITOR_PLUGIN_NAME . '/css/chief-editor.css' ) );	
		
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 *
	 * @version		1.0
	 * @since 		1.0
	 */	
	public function register_admin_scripts() {
	
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'wp-jquery-date-picker', plugins_url( CHIEF_EDITOR_PLUGIN_NAME . '/js/chief-editor.js' ) );
		
	} // end register_admin_scripts
	  
	  
	  /*
	function display()
	{
    echo "hello ".$_POST["studentname"];
	}*/
	
	  
    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'my_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Chief Editor</h2>           
		
            <!-- <form method="post" action="options.php"> -->
            <?php /*
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );   
                do_settings_sections( 'chief-editor-admin' );
                submit_button(); */ 
            ?>
            <!-- </form> -->
	    <?php // create list of drafts
		
	//$this->get_all_drafts();
	$this->recent_mu_posts();
?>
        </div>
        <?php
    }
    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'my_option_group', // Option group
            'my_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Preferences', // Title
            array( $this, 'print_section_info' ), // Callback
            'chief-editor-admin' // Page
        );  

        add_settings_field(
            'id_number', // ID
            'ID Number', // Title 
            array( $this, 'id_number_callback' ), // Callback
            'chief-editor-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'title', 
            'Title', 
            array( $this, 'title_callback' ), 
            'chief-editor-admin', 
            'setting_section_id'
        ); 
    }
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['id_number'] ) )
            $new_input['id_number'] = absint( $input['id_number'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }
    /** 
     * Get the settings option array and print one of its values
     */
    public function id_number_callback()
    {
        printf(
            '<input type="text" id="id_number" name="my_option_name[id_number]" value="%s" />',
            isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
        );
    }
    /** 
     * Get the settings option array and print one of its values
     */
    public function title_callback()
    {
        printf(
            '<input type="text" id="title" name="my_option_name[title]" value="%s" />',
            isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
        );
    }
    public function recent_mu_posts( $howMany = 10 ) {
	    global $wpdb;
  	    global $table_prefix;
	    //global $blog_id;
        // get an array of the table names that our posts will be in
        // we do this by first getting all of our blog ids and then forming the name of the 
        // table and putting it into an array
        $rows = $wpdb->get_results( "SELECT blog_id from $wpdb->blogs WHERE
        public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0';" );

  if ( $rows ) :

    $blogPostTableNames = array();
    foreach ( $rows as $row ) :
    
      $blogPostTableNames[$row->blog_id] = $wpdb->get_blog_prefix( $row->blog_id ) . 'posts';

    endforeach;
    # print_r($blogPostTableNames); # debugging code

    // now we need to do a query to get all the posts from all our blogs
    // with limits applied
    if ( count( $blogPostTableNames ) > 0 ) :

      $query = '';
      $i = 0;

      foreach ( $blogPostTableNames as $blogId => $tableName ) :

        if ( $i > 0 ) :
        $query.= ' UNION ';
        endif;

        $query.= " (SELECT ID, post_status, post_date, $blogId as `blog_id` FROM $tableName WHERE (post_status = 'draft' OR post_status = 'pending' OR post_status = 'pitch' OR post_status = 'future') AND post_type = 'post')";
        $i++;

      endforeach;

      $query.= " ORDER BY post_status DESC, blog_id DESC, post_date DESC";// LIMIT 0,$howMany;";
      # echo $query; # debugging code
      $rows = $wpdb->get_results( $query );

	  
	  
      // now we need to get each of our posts into an array and return them
      if ( $rows ) :
	  $nb_of_scheduled = 0;
	  $nb_of_drafts = 0;
	  $nb_of_pending = 0;
	  $futureColor = '#A4F2FF';
	  $draftColor = '#EDEDED';
	  $pendingColor = '#9CFFA1';
	  $tableHeaderColor = "#6B6B6B";
	  echo '<h3>Total non published post(s) found : '. count($rows).'</h3>';
	  //echo '<input type="text" id="datepicker" name="start_date" value="'.$date.'"/>';
	  
	  //echo 'Color codes:<div style="border:solid black 1px;background-color:'.$futureColor.';">Scheduled posts</div><div style="border:solid black 1px;background-color:'.$draftColor.';">Draft posts</div>';
	  echo '<hr>';
	  echo '<table style="border:solid #6B6B6B 1px;width:100%;"><tr style="background-color:'.$tableHeaderColor.';color:#FFFFFF">';
	  echo '<td>Blog title</td><td>Featured image</td><td>Post</td><td>Status</td><td>Excerpt</td><td>Author</td><td>Scheduled for date</td><td>Change scheduling</td></tr>';
        $posts = array();
        foreach ( $rows as $row ) :
		$blog_id = $row->blog_id;
		$data = $row->ID;
        	$new_post = get_blog_post( $blog_id, $data );
        	#global $blog_id;
		$current_blog_details = get_blog_details( $blog_id );
		$blog_name = $current_blog_details->blogname;
		//echo $blog_name;
		//echo '<tr><td>'.$new_post->get_title().'</td></tr>';
		//setup_postdata( $new_post );
		//echo '<h2>'.the_title() .'</h2>';		
		$images = get_children( array( 'post_parent' => $new_post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order', 'order' => 'ASC', 'numberposts' => 999 ) );
		$image_img_tag = '';
		if ( $images ) {
			$total_images = count( $images );
			$image = array_shift( $images );
			$image_img_tag = wp_get_attachment_image( $image->ID, 'thumbnail' );
			}
		$abstract = $new_post->post_excerpt; 
		$permalink = get_blog_permalink( $blog_id, $data );
		$author = $new_post->post_author;
		$user_info = get_userdata($author);
      		$username = $user_info->user_login;
		#echo 'Username: ' . $user_info->user_login . "\n";
     		#echo 'User roles: ' . implode(', ', $user_info->roles) . "\n";
      		#echo 'User ID: ' . $user_info->ID . "\n";
		$title = $new_post->post_title;
		$date = $new_post->post_date;
		$post_state = $new_post->post_status;
		$line_color = $post_state == 'future' ? $futureColor : ( $post_state == 'pending' ? $pendingColor : $draftColor);
	  
		  if ($post_state == 'future') {
		   $nb_of_scheduled++;
		  } elseif ($post_state == 'draft') {
			$nb_of_drafts++;
		} elseif ($post_state == 'pending') {
			$nb_of_pending++;
		  }
	  
		$complete_new_table_line = '<tr style="background-color:'.$line_color.';">';
	  $complete_new_table_line .= '<td><h3>'.$blog_name.'</h3></td><td><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$image_img_tag.'</a></td>';
		$complete_new_table_line .= '<td><h3><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$title.'</a></h3></td>';
	  $status_image = CHIEF_EDITOR_PLUGIN_URL . '/images/'.$post_state.'.png';
	  $complete_new_table_line .= '<td><img src="'.$status_image.'"/></td>';
		$complete_new_table_line .= '<td>'.$abstract.'</td><td>'.$username.'</td>';
	  
	  if ($post_state == 'future') {
		$complete_new_table_line .= '<td><h3>' . $date . '</h3></td>';
	  } else {
		$complete_new_table_line .= '<td>not scheduled</td>';
	  }
	  
	  $date_chooser_name = 'datepicker';//_'.$blog_id.'_'.$new_post->ID;
	  
	  $complete_new_table_line .= '<td><form name="changeDateForm" method="post" action="">';
	  $complete_new_table_line .= '<input type="hidden" name="post_id" value="'.$new_post->ID.'"/>';
	  $complete_new_table_line .= '<input type="hidden" name="blog_id" value="'.$blog_id.'">';
	  $change_date_button = '<input style="float:right;background-color:blue;color:white;" id="save-post" class="button" type="submit" value="Schedule" name="submitDate"></input>';
	  $unschedule_button = '<input style="float:right;" id="save-post" class="button" type="submit" value="Unchedule" name="unschedulePost"></input>';
	  $complete_new_table_line .= '<input type="text" class="datepicker" name="'.$date_chooser_name.'" value="'.$date.'"/>'.$change_date_button.$unschedule_button.'</form></td>';
	  $complete_new_table_line .= '</tr>';
		echo $complete_new_table_line;
	  
	  
	  
	  
	  
		$posts[] = $new_post;
	endforeach;
	echo '</table>';
	  
	  echo '<hr>';
	  echo '<table style="border:solid black 1px;width:50%;">';
	  echo '<tr style="background-color:'.$futureColor.';"><td>Scheduled posts : </td><td>'.$nb_of_scheduled.'</td></tr>';
	  echo '<tr style="background-color:'.$pendingColor.';"><td>Pending posts : </td><td>'.$nb_of_pending.'</td></tr>';
	  echo '<tr style="background-color:'.$draftColor.';"><td>Draft posts : </td><td>'.$nb_of_drafts.'</td></tr>';
	  echo '<tr style="background-color:#ffffff;color:#000000;"><td>Total unpublished posts : </td><td>'.count($rows).'</td></tr>';
	  echo '</table>';
	  
	  
        //echo "<pre>"; print_r($posts); echo "</pre>"; exit; # debugging code
        return $posts;

      else:

        return "Error: No Posts found";

      endif;

    else:

       return "Error: Could not find blogs in the database";

    endif;
  
  else:

    return "Error: Could not find blogs";
    
  endif;
}


	 
	  
	  
public function getDefaultWPPublishBox() {

	$result = "<div id=\"submitdiv\" class=\"postbox\">
                                <div class=\"handlediv\" title=\"<?php esc_attr_e( 'Click to toggle' ); ?>\"><br /></div>
                                <h3 class=\"hndle\"><?php _e('Press This') ?></h3>
                                <div class=\"inside\">
                                        <p id=\"publishing-actions\">
                                        <?php
                                                submit_button( __( 'Save Draft' ), 'button', 'draft', false, array( 'id' => 'save' ) );
                                                if ( current_user_can('publish_posts') ) {
                                                        submit_button( __( 'Publish' ), 'primary', 'publish', false );
                                                } else {
                                                        echo '<br /><br />';
                                                        submit_button( __( 'Submit for Review' ), 'primary', 'review', false );
                                                } ?>
                                                <span class=\"spinner\" style=\"display: none;\"></span>
                                        </p>
                                        <?php if ( current_theme_supports( 'post-formats' ) && post_type_supports( 'post', 'post-formats' ) ) :
                                                        $post_formats = get_theme_support( 'post-formats' );
                                                        if ( is_array( $post_formats[0] ) ) :
                                                                $default_format = get_option( 'default_post_format', '0' );
                                                ?>
                                        <p>
                                                <label for=\"post_format\"><?php _e( 'Post Format:' ); ?>
                                                <select name=\"post_format\" id=\"post_format\">
                                                        <option value=\"0\"><?php echo get_post_format_string( 'standard' ); ?></option>
                                                <?php foreach ( $post_formats[0] as $format ): ?>
                                                        <option<?php selected( $default_format, $format ); ?> value=\"<?php echo esc_attr( $format ); ?>\"> <?php echo esc_html( get_post_format_string( $format ) ); ?></option>
                                                <?php endforeach; ?>
                                                </select></label>
                                        </p>
                                        <?php endif; endif; ?>
                                </div>
                        </div>";
	return $result;


}

    public function get_all_drafts() 
	{
	global $wpdb;
	global $post;

	$site_blog_ids = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM wp_blogs where blog_id > 1")); // get all subsite blog ids
	//print_r( $site_blog_ids ); // checkem - output is "Array ( [0] => stdClass Object ( [blog_id] => 2 ) [1] => stdClass Object ( [blog_id] => 3 ) [2] => stdClass Object ( [blog_id] => 5 ) ) "
	foreach( $site_blog_ids AS $site_blog_id ) { //iterate through the ids
		print_r( "siteid= ".$site_blog_id->blog_id."</br>" );
	
	

	$fivesdrafts = $wpdb->get_results( 
	"
	SELECT * 
	FROM $wpdb->posts
	WHERE post_status = 'draft'
	"
	);

	echo '<div>';
	echo '<h2>Brouillons d articles trouve(s) : '. count($fivesdrafts).'</h2>';
	//echo '<table style="width:100%;">'
	if ( $fivesdrafts )
	{
	echo '<table style="width:100%;">';
		foreach ( $fivesdrafts as $post )
		{
		setup_postdata( $post );
		//echo '<h2>'.the_title() .'</h2>';		
		$permalink = get_permalink($post->ID);
		$author = get_the_author();		
		echo '<tr><td><h3><a href="'.$permalink.'" target="blank_" rel="bookmark" title="Permalink:'.get_the_title().'">'.get_the_title().'</a></h3></td><td><h3>'.$author.'</h3></td><td><h3>'.get_the_date().'</h3></td></tr>';
		
		}	
	echo '</table>';
	}
	else
	{
		echo '<h2>Not Found</h2>';
	}
	echo '</div>';
	}
	}
}
}