<?php if(!class_exists('ChiefEditorSettings')){
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

      $query.= " ORDER BY post_status DESC, blog_id DESC";// LIMIT 0,$howMany;";
      # echo $query; # debugging code
      $rows = $wpdb->get_results( $query );

      // now we need to get each of our posts into an array and return them
      if ( $rows ) :

	  $futureColor = '#FFE699';
	  $draftColor = '#EDEDED';
	  
	  echo '<h3>Non published post(s) found : '. count($rows).'</h3>';
	  echo 'Color codes:<div style="border:solid black 1px;background-color:'.$futureColor.';">Scheduled posts</div><div style="border:solid black 1px;background-color:'.$draftColor.';">Draft posts</div>';
	  echo '<hr>';
	  echo '<table style="border:solid #6B6B6B 1px;width:100%;"><tr style="background-color:#6B6B6B;color:#FFFFFF"><td>Blog title</td><td>Featured image</td><td>Post</td><td>Excerpt</td><td>Author</td><td>Date</td><td>Change date</td></tr>';
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
		$line_color = $post_state == 'future' ? $futureColor : $draftColor;
		$complete_new_table_line = '<tr style="background-color:'.$line_color.';">';
	  $complete_new_table_line .= '<td><h3>'.$blog_name.'</h3></td><td><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$image_img_tag.'</a></td>';
		$complete_new_table_line .= '<td><h3><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$title.'</a></h3></td>';
		$complete_new_table_line .= '<td>'.$abstract.'</td><td><h4>'.$username.'</h4></td><td><h4>'.$date.'</h4></td>';
		$complete_new_table_line .= '<td><form><input type="text" id="chief-editor-custom-date" class="chief-editor-custom-date" name="start_date" value="'.$date.'"/></form></td></tr>';
		echo $complete_new_table_line;
		$posts[] = $new_post;
	endforeach;
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
