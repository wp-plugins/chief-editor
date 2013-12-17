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

        $query.= " (SELECT ID, post_date, $blogId as `blog_id` FROM $tableName WHERE (post_status = 'draft' OR post_status = 'pending' OR post_status = 'pitch') AND post_type = 'post')";
        $i++;

      endforeach;

      $query.= " ORDER BY blog_id DESC";// LIMIT 0,$howMany;";
      # echo $query; # debugging code
      $rows = $wpdb->get_results( $query );

      // now we need to get each of our posts into an array and return them
      if ( $rows ) :

	echo '<h2>Brouillons d articles trouve(s) : '. count($rows).'</h2>';
	echo '<p>Vas y françois, tu peux cliquer sur le nom des articles pour les voir dans un nouvel onglet, et préparer les articles à publier cette semaine. Je vais essayer d améliorer cet outil par la suite. Merci.</p>';
	echo '<table style="width:100%;">';
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
		$permalink = get_blog_permalink( $blog_id, $data );
		$author = $new_post->post_author;
		$user_info = get_userdata($author);
      		$username = $user_info->user_login;
		#echo 'Username: ' . $user_info->user_login . "\n";
     		#echo 'User roles: ' . implode(', ', $user_info->roles) . "\n";
      		#echo 'User ID: ' . $user_info->ID . "\n";
		$title = $new_post->post_title;
		$date = $new_post->post_date;
		echo '<tr><td><h3>'.$blog_name.'</h3></td><td><h3><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$title.'</a></h3></td><td><h4>'.$username.'</h4></td><td><h4>'.$date.'</h4></td></tr>';
		
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