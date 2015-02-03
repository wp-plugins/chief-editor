<?php 

if (isset($_POST['submitDate'])) {
  
  echo $_POST["datepicker"] . '_' .$_POST["blog_id"]. '_'.$_POST["post_id"];
  //echo $_POST["name"];
  updatePostDate($_POST["blog_id"],$_POST["post_id"],$_POST["datepicker"]);
  
}
else if (isset($_POST['unschedulePost'])) {
  
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
  
  echo '<br/>date: ' . $post_date . ' strtotime: ' . strtotime($post_date) . ' strtotime(now): ' . strtotime('now').' '.strtotime("+1 hour").'<br/>';
  echo 'date("Y-m-d H:i:s"):' . date("Y-m-d H:i:s");
  echo 'time("Y-m-d H:i:s"):' . time("Y-m-d H:i:s");
  $now = gmdate('Y-m-d H:i:59');
  echo '<br/>now from gmdate'.$now;
  $time = time();
  echo '<br/>time:'.$time;
  
  $status = strtotime($post_date) > strtotime('+1 hour') ? 'future' : 'publish';
  
  
  switch_to_blog( $blog_id );
  
  $operation = 'edit';
  $newpostdata = array();
  //strtotime("now"), "\n";
  if ( $status == 'publish' ) {
	echo ' ' .strtotime($post_date) .'('.$post_date. ') < '. strtotime( "now" ) ,"\n" ;
	echo 'cannot publish artilces from here, only schedule, dates in future';
	return;
	
	//$status = 'publish';    
	//$newpostdata['post_status'] = $status;
	//$newpostdata['post_date'] = date( 'Y-m-d H:i:s',  $post_date );
	
	// Also pass 'post_date_gmt' so that WP plays nice with dates
	//$newpostdata['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $post_date );
	
  }
  elseif ( $status == 'future' ) {
	
	echo '<br/>SCHEDULING: ' .strtotime($post_date) . '>'. strtotime( "today" ) .'\r\n';
	//$status = 'future';    
	$newpostdata['post_status'] = $status;
	$newpostdata['post_date'] = date( 'Y-m-d H:i:s', strtotime($post_date) );
	$newpostdata->edit_date = true;
	// Also pass 'post_date_gmt' so that WP plays nice with dates
	$newpostdata['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime($post_date) );
	
	echo '<br/>SCHEDULING: ' . $newpostdata['post_date'] . ' / GMDate : ' . $newpostdata['post_date_gmt'];
  }
  
  if ('insert' == $operation) {
	$err = wp_insert_post($newpostdata, true);
	
  }
  elseif ('edit' == $operation) {
	
	//echo 'edit ==' .$operation."\r\n";
	$newpostdata['ID'] = $post_id;
	
	//$newpostdata['edit_date'] = true;
	
	//echo $newpostdata['ID'] . "_" . $newpostdata['edit_date']. "_" . $newpostdata['post_status']. "_" . $newpostdata['post_date'] . "_" . $newpostdata['post_date_gmt'] ."\r\n";
	//echo '<br/>'.$newpostdata;
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
	private $lang_domain = 'chief-editor';
	private $general_settings_key = 'chief_editor_posts_tab';
	private $advanced_settings_key = 'chief_editor_comments_tab';
	private $stats_key = 'chief_editor_stats_tab';
	private $chief_editor_options_key = 'chief_editor_settings_tab';
	private $chief_editor_admin_page_name = 'chief_editor';
	
	private $chief_editor_settings_tabs = array();
	/**
	* Start up
	*/
	public function __construct()
	{
	  //add_action( 'init', array( &$this, 'load_settings' ) );
	  add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
	  add_action( 'admin_init', array( &$this, 'register_advanced_settings' ) );
	  add_action( 'admin_init', array (&$this, 'register_stats_tab'));
	  add_action( 'admin_init', array (&$this, 'register_options_tab'));
	  add_action( 'admin_init', array (&$this, 'settings_page_init'));
	  add_action( 'admin_menu', array( &$this, 'add_admin_menus' ));
	  add_action( 'admin_enqueue_scripts',array( &$this,'chief_editor_load_scripts'));
	  add_action( 'wp_ajax_ce_send_author_std_validation_email', array( &$this,'ce_process_ajax'));
	  add_action( 'wp_ajax_ce_send_author_std_validation_email_confirmed', array( &$this,'ce_process_ajax_bat_confirm'));
	}
	
	function ce_process_ajax_bat_confirm() {
	  $pID = htmlspecialchars($_POST['postID']);
	  $bID = htmlspecialchars($_POST['blogID']);
	  
	  
	  $this->send_confirmation_email_to_author_of_post($bID,$pID,$this->options);
	  die();
	}
	
	
	function send_confirmation_email_to_author_of_post($blogID,$postID,$options) {
	  //echo "need to send BAT for blog ".$blogID." fo post ".$postID;
	  //echo "send_confirmation_email_to_author_of_post";
	  
	  $blog_url = get_site_url( $blogID );
	  
	  // get post unique URL
	  
	  switch_to_blog( $blogID );

	  $current_post = get_post($postID); 
	  $post_title = $current_post->post_title;
	  $permalink = get_permalink( $postID);
	  $post_author_id = $current_post->post_author ;
	  
	  // get author email
	  $user_info = get_userdata($post_author_id);
	  $user_login = $user_info->user_login;
	  $user_displayname = $user_info->display_name;
	  $user_email = $user_info->user_email;
		
	  restore_current_blog();
	  
	  // build mail content with std text
	  $multiple_to_recipients = $user_email.','.$options['email_recipients'];
	  $msg_object = "BAT : ".$post_title;
	  //$msg_content = $options['email_content'];
	  //echo $msg_object;
	  //echo $multiple_to_recipients;
	  //echo $msg_content;
	  // add other email recipients
	  $sender_email = $options['sender_email'];
	  $sender_name = $options['sender_name'];
	  
	  // send email to recipents
	  $headers[] = "From: ".$sender_name." <".$sender_email.">";
	  $headers[] = "Content-type: text/html";
	  
	  //add_filter( 'wp_mail_content_type', 'set_html_content_type' );
	  
	  $search = array ('/%username%/', '/%userlogin%/','/%useremail%/', '/%postlink%/', '/%posttitle%/','/%blogurl%/','/%n%/');	 
	$replace = array ($user_displayname, $user_login,( $user_email == "" ? "no email" : $user_email ), $permalink, $post_title,$blog_url, "\n");
	foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
		$ind = 1 + $userdata_key;
		array_push($search, '/%userdata'.$ind.'%/');  
		array_push($replace, $userdata_field["value"]);
	}   
	$msg_content = preg_replace($search, $replace, $options['email_content']);
	
	  //echo $msg_content;
	
	  $success = wp_mail( $multiple_to_recipients, $msg_object, $msg_content, $headers );

	// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
	  //remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

  
	  // send confirmation for ajax callback
	 echo $success ? __('Email sent successfully','chief-editor') : __('Problem sending email...','chief-editor');
	  
	}
	
	
	function ce_process_ajax() {
	  
	  //print_r($_POST);
	  	  
	  $pID = htmlspecialchars($_POST['postID']);
	  $aID = htmlspecialchars($_POST['authorID']);
	  $bID = htmlspecialchars($_POST['blogID']);
	  
	  switch_to_blog( $bID );

	  $current_post = get_post($pID); 
	  $title = $current_post->post_title;
	  
	  $user_info = get_userdata($aID);
	  $userlogin = $user_info->user_login;
	  $userdisplayname = $user_info->display_name;
	  $user_email = $user_info->user_email;
	  
	  restore_current_blog();
	  
	  echo '<form id="'.$bID.'_'.$pID.'_chief-editor-bat-form-send" class="chief-editor-bat-form-send" action="" method="POST"><div>';
	  echo __('Are you sure you want to sent BAT email?','chief-editor').'<br/>';
	  echo $title . '<br/>';
	  echo $userdisplayname.' ('.$userlogin.')'. '<br/>';
	  echo $user_email. '<br/>';
	  echo '<input type="hidden" id="postID" name="postID" value="'.$pID.'">';
	  echo '<input type="hidden" id="blogID" name="blogID" value="'. $bID .'">';
	  echo '<input type="submit" id="chief-editor-bat-send-confirm" name="chief-editor-bat-send-confirm" class="chief-editor-bat-send-confirm button-primary" value="'.__('Send','chief-editor').'"/>';  
	  echo '</div></form>';
	  
	  die();
	}
	
	function load_settings() {
	  $this->general_settings = (array) get_option( $this->general_settings_key );
	  $this->advanced_settings = (array) get_option( $this->advanced_settings_key );
	  
	  // Merge with defaults
	  $this->general_settings = array_merge( array(
		'general_option' => 'General value'
	  ), $this->general_settings );
	  
	  $this->advanced_settings = array_merge( array(
		'advanced_option' => 'Advanced value'
	  ), $this->advanced_settings );
	}
	
	function chief_editor_load_scripts($hook) {
	  global $chief_editor_settings;
	  
	  if ($hook != $chief_editor_settings) {
		return;
	  }
	  
	  //wp_enqueue_script('','');
	}
	
	function register_general_settings() {
	  $this->chief_editor_settings_tabs[$this->general_settings_key] = __('Posts','chief-editor');
	  
	}
	
	function section_general_desc() {
	  echo 'General section description goes here.';
	}
	
	function field_general_option() {
?>
<input type="text" name="<?php echo $this->general_settings_key; ?>[general_option]" value="<?php echo esc_attr( $this->general_settings['general_option'] ); ?>" />
<?php
									}
	
	function register_advanced_settings() {
	  
	  $this->chief_editor_settings_tabs[$this->advanced_settings_key] = __('Comments','chief-editor');
	  
	}
	
	function register_stats_tab() {
	  $this->chief_editor_settings_tabs[$this->stats_key] = __('Authors','chief-editor');
	}
	
	function register_options_tab() {
	  $this->chief_editor_settings_tabs[$this->chief_editor_options_key] = __('Settings','chief-editor');
	}
	
	function section_advanced_desc() {
	  echo 'Advanced section description goes here.';
	}
	
	function field_advanced_option() {
?>
<input type="text" name="<?php echo $this->advanced_settings_key; ?>[advanced_option]" value="<?php echo esc_attr( $this->advanced_settings['advanced_option'] ); ?>" />
<?php
									 }
	
	
	function add_admin_menus() {
	  global $chief_editor_settings;
	  $chief_editor_settings = add_options_page( 'Chief Editor Settings', 'Chief Editor', 'delete_others_pages', $this->chief_editor_admin_page_name, array( &$this, 'chief_editor_options_page' ) );
	}
	
	
	function chief_editor_options_page() {
	  $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
?>
<div class="wrap">
  <?php $this->chief_editor_options_tabs(); ?>
</div>
<?php
	}
	
	function chief_editor_options_tabs() {
	  
	  $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
	  //screen_icon();
	  echo '<div style="text-align:center;padding:5px;">';
	  echo screen_icon() . '<h1>Chief Editor</h1>';
	  echo '<a class="button-primary" href="http://wordpress.org/plugins/chief-editor/" target="_blank">'.__('Visit Plugin Site','chief-editor').'</a>  <a  class="button-primary" style="color:#FFF600;" href="http://wordpress.org/support/view/plugin-reviews/chief-editor" target="_blank">'.__('Rate!','chief-editor').'</a>';
	  //echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
	  echo '</div> ';
	  echo '<h2 class="nav-tab-wrapper">';
	  foreach ( $this->chief_editor_settings_tabs as $tab_key => $tab_caption ) {
		$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
		echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->chief_editor_admin_page_name . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
	  }
	  echo '</h2>';
	  
	  if ($current_tab == 'chief_editor_posts_tab') {
		
		$this->recent_mu_posts();
		
	  }
	  elseif ($current_tab == 'chief_editor_comments_tab') {
		
		//$this->recent_multisite_comments();
		global $wpdb;
		$last_month = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
		$start_date = date('Y-m-d H:i:s', $last_month );
		$end_date = date('Y-m-d H:i:s');
		$intro_text = __('All comments accross the network since ','chief-editor').$start_date.'<br/>';
		
		if ( is_multisite() ) {
		  $allComments = $this->getAllCommentsMultisite('1000',$start_date,$end_date);
		}
		else {
		  $selects = "SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content FROM wp_comments
WHERE comment_date >= '{$start_date}'
AND comment_date < '{$end_date}'
ORDER BY comment_date_gmt DESC LIMIT 1000";
		  // real number is (number * # of blogs)
		  $allComments =  $wpdb->get_results($selects);
		  
		}
		
		
		echo $intro_text . ' ' . count($allComments). __(' item(s)','chief-editor');
		echo $this->formatCommentsFromArray($allComments);
		// $merged = 
		//echo $this->formatCommentsFromArray($merged);
	  }
	  elseif ($current_tab == 'chief_editor_stats_tab') {
		$this->bm_author_stats("alltime");
	  } elseif ($current_tab == 'chief_editor_settings_tab') {
		
		//$this->settings_page_init();
	  	// Set class property
        $this->options = get_option( 'chief_editor_option' );
		//echo $this->options;
        echo '<div class="wrap">'.screen_icon();
		echo '<h2>'.__('Chief Editor','chief-editor').' '.__('Settings','chief-editor').'</h2>';
		echo '<form method="post" action="options.php">';
		// This prints out all hidden setting fields';
		settings_fields( 'chief_editor_option_group' );   
		do_settings_sections( 'chief_editor_plugin_options' );
        submit_button(); 
           
        echo '</form></div>';
        
	  }
	  echo '<div style="text-align:right;">';
	  // echo '<a class="button-primary" href="http://wordpress.org/plugins/chief-editor/" target="_blank">Visit Plugin Site</a>  <a  class="button-primary" style="color:#FFF600;" href="http://wordpress.org/support/view/plugin-reviews/chief-editor" target="_blank">Rate This Plugin</a>';
	  echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
	  echo '</div> ';
	}
	
	/**
     * Register and add settings
     */
    public function settings_page_init()
    {        
        register_setting(
            'chief_editor_option_group', // Option group
            'chief_editor_option', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            __('Automatic Email to authors','chief-editor'), // Title
            array( $this, 'ce_print_section_info' ), // Callback
            'chief_editor_plugin_options' // Page
        );  

	  add_settings_field(
            'sender_email', // ID
            __('Sender email address','chief-editor'), // Title 
            array( $this, 'ce_sender_email_address_callback' ), // Callback
            'chief_editor_plugin_options', // Page
            'setting_section_id' // Section           
        );  
	  add_settings_field(
            'sender_name', // ID
            __('Sender name','chief-editor'), // Title 
            array( $this, 'ce_sender_name_callback' ), // Callback
            'chief_editor_plugin_options', // Page
            'setting_section_id' // Section           
        ); 
	  
        add_settings_field(
            'email_recipients', // ID
            __('Email recipients addresses','chief-editor'), // Title 
            array( $this, 'ce_email_addresses_callback' ), // Callback
            'chief_editor_plugin_options', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'email_content', 
            __('Email content','chief-editor'), 
            array( $this, 'ce_email_content_callback' ), 
            'chief_editor_plugin_options', 
            'setting_section_id'
        );      
	  
	  $this->options = get_option( 'chief_editor_option' );
    }
	
	/**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
	
    public function sanitize( $input )
    {
        $new_input = array();
	  if( isset( $input['sender_email'] ) )
            $new_input['sender_email'] = sanitize_text_field( $input['sender_email'] );

	  if( isset( $input['sender_name'] ) )
            $new_input['sender_name'] = sanitize_text_field( $input['sender_name'] );

        if( isset( $input['email_recipients'] ) )
            $new_input['email_recipients'] = sanitize_text_field( $input['email_recipients'] );

        if( isset( $input['email_content'] ) )
            $new_input['email_content'] = $input['email_content'];

        return $new_input;
    }
	
    /** 
     * Print the Section text
     */
    public function ce_print_section_info()
    {
        print __('The following settings are used to send pre-formatted email to post authors, in order for them to validate it online before publishing','chief-editor');
    }

	
	
	  public function ce_sender_email_address_callback()
    {
        printf(
		  '<input type="text" id="sender_email" name="chief_editor_option[sender_email]" value="%s" />',
            isset( $this->options['sender_email'] ) ? esc_attr( $this->options['sender_email']) : ''
        );
    }
	 public function ce_sender_name_callback()
    {
        printf(
		  '<input type="text" id="sender_name" name="chief_editor_option[sender_name]" value="%s" />',
            isset( $this->options['sender_name'] ) ? esc_attr( $this->options['sender_name']) : ''
        );
    }
	
    /** 
     * Get the settings option array and print one of its values
     */
    public function ce_email_addresses_callback()
    {
        printf(
		  '<input type="text" id="email_recipients" name="chief_editor_option[email_recipients]" value="%s" />',
            isset( $this->options['email_recipients'] ) ? esc_attr( $this->options['email_recipients']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function ce_email_content_callback()
    {
	  $ce_default_mail_content = 'Cher %username%,<br/>
Voici la prévisualisation de votre article pour obtention d\'un Bon A Tirer : <br/>

<h2><a href="%postlink%" target="_blank">%posttitle%</a></h2><br/>

Vous devez être authentifié avec vos identifiants personnels <a href="%blogurl%">sur le site</a> pour visualiser cet article en ligne:
<ul><li>Utiliser votre login : <strong>%userlogin%</strong></li>
<li>et votre mot de passe (si vous l\'avez oublié, demandez-en un nouveau en cliquant ici : <a href="http://www.idweblogs.com/wp-login.php?action=lostpassword">Service de récupération de mot de passe</a>)
</ul>
Si le message suivant apparait:<br/>
<em>Désolé, mais la page demandé ne peut être trouvée.</em>
c\'est que vous n\'êtes pas connecté au site.
<h2>En cas de problème</h2>Merci de suivre la procédure suivante pour visualiser votre post en ligne:<br/>
<ol><li>Se connecter avec vos identifiants <a href="%blogurl%">sur le site idweblogs</a>.</li>
<li>Vérifier que votre nom (ou pseudo) apparait bien en haut à droite de l\'écran, ce qui confirme votre connexion au site.</li>
<li>Ouvrir un nouvel onglet dans le même navigateur (Chrome, Firefox, Internet Explorer,etc...).</li>
<li>Copier/coller le lien ci dessus dans ce nouvel onglet et valider.</li>
<li>Votre post doit s\'afficher correctement, en cas de problème, merci de nous contacter à : <a href="mailto:aide@idweblogs.com">aide@idweblogs.com</a></li>
</ol> 
<h2>Merci de préciser</h2> dans votre mail de réponse, si ce n\'est déjà fait, les éléments suivants:
<ol><li>Vos liens d\'intérêt éventuels pour ce post</li>
<li>Les mots clés qui permettent d\'indexer au mieux votre post</li>
<li>L\'image de Une du post</li>
<li>La catégorie (ou les catégories) du blog dans laquelle doit être publié votre article</li>
<li>Les liens web éventuels à rajouter vers des sites externes ou de la bibliographie</li>
<li>(optionnel) une photo de vous</li>
</ol>

<br/>Cordialement, L\'équipe
';
	  
        printf(
            '<textarea type="text" id="email_content" rows="25" cols="110" name="chief_editor_option[email_content]" value="%s">%s</textarea>',
            isset( $this->options['email_content'] ) ? esc_attr( $this->options['email_content']) : $ce_default_mail_content,
		  isset( $this->options['email_content'] ) ? esc_attr( $this->options['email_content']) : $ce_default_mail_content
		  
        );	 
	}
	
	public function get_all_writers_over_network() {
	  // Set up global variables. Great
	  global $wpdb, $blog_id, $post;
	  
	  // Get a list of blogs in your multisite network
	  $blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY blog_id" ) );
	  
	  $globalcontainer = array();
	  foreach( $blogs as $blog ) {
		
		switch_to_blog( $blog->blog_id );
		
		$globalquery = array_merge (get_users('role=contributor'),get_users('role=author'),get_users('role=editor'));//get_posts( 'numberposts=5&post_type=any' );
		
		$globalcontainer = array_merge( $globalcontainer, $globalquery );
		
		restore_current_blog();
	  }
	  
	  return $globalcontainer;
	}
	
	
	public function bm_author_stats($period) {
	  global $wpdb;
	  
	  $table_class = "border:solid #6B6B6B 1px;width:100%;";
	  $border_class = "border:solid #6B6B6B 1px;";
	  
	  echo '<form>';
	  echo '<INPUT type="button" value="'.__('Trace graph for sorted column','chief-editor').'" name="traceGraphButton" onClick="traceGraph();">';
	  echo '</FORM>';
	  echo '<table class="sortable" id="authorTable" style="border:solid #6B6B6B 1px;width:100%;">';
	  $color_bool = true;
	  $chief_editor_table_header = '<tr style="background-color:#6B6B6B;color:#FFFFFF"><td>Blog</td>';
	  $chief_editor_table_header = $chief_editor_table_header . '<td>'.__('Name','chief-editor').'</td><td>'.__('login','chief-editor').'</td><td>'.__('Month blogging','chief-editor').'</td>';
	  $chief_editor_table_header = $chief_editor_table_header . '<td>'.__('Posts','chief-editor').'</td><td>'.__('Posts/month','chief-editor').'</td>';
	  $chief_editor_table_header = $chief_editor_table_header . '<td>'.__('Words/post','chief-editor').'</td><td>'.__('Comments','chief-editor').'</td>';
	  $chief_editor_table_header = $chief_editor_table_header . '<td>'.__('Comments/post','chief-editor').'</td><td>'.__('Words/comment','chief-editor').'</td><td>'.__('Comments/month','chief-editor').'</td></tr>';
	  
	  
	  echo $chief_editor_table_header;
	  
	  /*
	  $authorquery = "SELECT DISTINCT p.post_author, count(ID) AS posts FROM $wpdb->posts p WHERE p.post_type = 'post'";
	  if ($period == "month") {
	  $authorquery .= " AND p.post_date > date_sub(now(),interval 1 month)";
	  }
	  $authorquery .= "  GROUP BY p.post_author ORDER BY posts DESC";
	  
	  $authors = $wpdb->get_results($authorquery);
	  */
	  //echo 'Number of authors : '.count($authors);
	  //$users = $this->get_all_writers_over_network();
	  
	  //echo 'Number of authors : '.count($users);
	  
	  global $wpdb, $blog_id, $post;
	  
	  // Get a list of blogs in your multisite network
	  $blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY blog_id" ) );
	  
	  $globalcontainer = array();
	  foreach( $blogs as $blog ) {
		
		switch_to_blog( $blog->blog_id );
		$blog_name = get_bloginfo('name');
		$blog_title = get_bloginfo('title');
		$blog_wpurl = get_bloginfo('wpurl');
		$users = array_merge (get_users('role=contributor'),get_users('role=author'),get_users('role=editor'));//get_posts( 'numberposts=5&post_type=any' );
		
		//echo '<tr>';
		foreach ($users as $author) {
		  
		  $user_role = $author->role;
		  if ($user_role == 'subscriber') {
			continue;
		  }
		  
		  $author_stats = $this->bm_get_stats($period,$author->ID);
		  if ($author_stats['posts'] == 0) {
			continue;
		  }
		  $line_color = ($color_bool?'#FFFFFF':'#EDEDED');
		  echo '<tr style="border:solid #6B6B6B 1px;background-color:'.$line_color.'">';
		  //$this->bm_print_stats($this->bm_get_stats($period,$author->post_author));
		  
		  $user_info = get_userdata($author->ID);
		  $userlogin = $user_info->user_login;
		  $userdisplayname = $user_info->display_name;
		  $words_per_post = 0;
		  $words_per_comment = 0;
		  
		  if ($author_stats['posts'] > 0) {
			$words_per_post = round($author_stats['postwords'] / $author_stats['posts']);
		  }
		  if ($author_stats['commentwords'] > 0 && $author_stats['posts'] > 0) {
			$words_per_comment = floor($author_stats['commentwords'] / $author_stats['posts']);
		  }
		  
		  $performance = $author_stats['avgposts'] * $author_stats['avgcomments'];
		  
		  $user_rss_feed = $blog_wpurl.'/author/'.$userlogin.'/feed/';
		  echo '<td>'.$blog_name.'</td><td>'.$userdisplayname.'</td><td>'.$userlogin.' - <a target="_blank" href="'.$user_rss_feed.'">'.$user_rss_feed.'</a></td><td>'.$author_stats['bloggingmonths'].'</td><td>'.$author_stats['posts'].'</td><td>'.$author_stats['avgposts'].'</td><td>'.$words_per_post.'</td><td>'.$author_stats['comments'].'</td><td>'.$author_stats['avgcomments'].'</td><td>'.$words_per_comment.'</td><td>'.$performance.'</td>';
		  //$i++; 
		  echo '</tr>';
		  $color_bool = !$color_bool;
		}
		
		
		restore_current_blog();
	  }
	  
	  echo '</table>';
	  echo '<form>';
	  echo '<INPUT type="button" value="'.__('Trace graph for sorted column','chief-editor').'" name="traceGraphButton" onClick="traceGraph();">';
	  echo '</FORM>';
	  echo '<hr>';
	  echo '<div style="text-align:center;"><canvas id="graphCanvas" height="600" width="1000"></canvas><br><br><canvas id="pieGraphCanvas" height="600" width="1000"></div>';
	  
	}
	
	public function bm_print_stats($stats) {
	  
	  //$options = get_option('BlogMetricsOptions');
	  
	  $option['fullstats'] = 1;
	  
	  if ($stats['period'] == "alltime") {
		$per = "per";
	  }
	  else if ($stats['period'] == "month") {
		$per = "this";
	  }
	  echo '<td style="vertical-align:text-top;width:220px;">';
	  if ( !is_numeric($stats['authors']) ) {
		echo '<h3>'.$stats['authors'].'</h3>';
	  }
	  echo '<h4 style="margin-bottom:2px;">Raw Author Contribution</h4>';
	  
	  if ($stats['avgposts'] == 1) {
		echo $stats['avgposts']." post $per month<br/>\n";
	  }
	  else {
		echo $stats['avgposts']." posts $per month<br/>\n";
	  }
	  if ($stats['posts'] > 0) {
		echo 'Avg: '.round($stats['postwords'] / $stats['posts'])." words per post<br/>\n";
	  }
	  if ($stats['stddevpostwords']) {
		echo 'Std dev: '.round($stats['stddevpostwords']).' words'."<br/>\n";
	  }
	  echo '<h4 style="margin-bottom:2px;">Conversation Rate Per Post</h4>';
	  echo '<table style="border-collapse:collapse;">';
	  echo '<tr><td>Avg: &nbsp;</td><td>'.$stats['avgcomments'].' comments'."</td></tr>\n";
	  if ($stats['stddevcomments']) {
		echo '<tr><td>Std dev: &nbsp;</td><td>'.$stats['stddevcomments'].' comments'."</td></tr>\n";
	  }
	  if ($stats['commentwords'] > 0 && $stats['posts'] > 0) {
		echo '<tr><td>Avg:</td><td>'.floor($stats['commentwords'] / $stats['posts']).' words in comments'."</td></tr>\n";
	  }
	  echo '<tr><td>Avg:</td><td>'.$stats['avgtrackbacks'].' trackbacks'."</td></tr>\n";
	  if ($stats['stddevtrackbacks']) {
		echo '<tr><td>Std dev:</td><td>'.$stats['stddevtrackbacks'].' trackbacks'."</td></tr>\n";
	  }
	  echo '</table>'."\n\n";
	  
	  if ($options['fullstats']) {
		echo '<h4 style="margin-bottom:2px;">Full Stats</h4>';
		echo '<table style="border-collapse:collapse;">';
		if ( is_numeric($stats['authors']) ) {
		  echo '<tr><td>Author(s):</td><td>'.$stats['authors']."</td></tr>";
		}
		if ($stats['period'] == "alltime") {
		  echo '<tr><td>Posts:</td><td>'.$stats['posts']."</td></tr>";
		  
		}
		echo '<tr><td>Words in posts:</td><td>'.$stats['postwords']."</td></tr>";
		echo '<tr><td>Comments:</td><td>'.$stats['comments']."</td></tr>";
		echo '<tr><td>Words in comments:</td><td>'.$stats['commentwords']."</td></tr>";
		echo '<tr><td>Trackbacks:</td><td>'.$stats['trackbacks']."</td></tr>";
		if ($stats['period'] == "alltime") {
		  echo '<tr><td>Months blogging: &nbsp;</td><td>'.$stats['bloggingmonths']."</td></tr>";
		}
		
		echo '</table>';
	  }
	  echo '</td>';
	}
	
	function bm_get_stats($period="alltime",$authorid=0) {
	  global $wpdb;
	  $options = get_option('BlogMetricsOptions');
	  
	  $periodquery = "";
	  $authorquery = "";
	  
	  if ($period == "month") {
		$periodquery = " AND p.post_date > date_sub(now(),interval 1 month)";
	  }
	  if ($authorid != 0) {
		$authorquery = " AND p.post_author = $authorid";
	  }
	  
	  $authorsquery = "SELECT COUNT(DISTINCT post_author) FROM $wpdb->posts p WHERE p.post_type = 'post'".$periodquery;
	  
	  // Override query if an authorid is set, to return display name for author
	  if ($authorid != 0) {
		$authorsquery = "SELECT u.display_name FROM $wpdb->users u WHERE u.ID = $authorid";
	  }
	  
	  $postsquery = "SELECT COUNT(ID) FROM $wpdb->posts p WHERE p.post_type = 'post' AND p.post_status='publish'".$periodquery.$authorquery;
	  
	  $firstpostquery = "SELECT p.post_date FROM $wpdb->posts p WHERE p.post_status = 'publish'$authorquery ORDER BY p.post_date LIMIT 1";
	  
	  $commentfromwhere 	="FROM $wpdb->comments c, $wpdb->posts p, $wpdb->users u "
		."WHERE c.comment_approved = '1'"
		." AND c.comment_author_email != u.user_email"
		." AND c.comment_post_ID = p.ID"
		." AND c.comment_type = ''"
		." AND p.post_type = 'post'"
		." AND p.post_author = u.ID"
		.$periodquery.$authorquery;
	  
	  $commentsquery 		= "SELECT COUNT(c.comment_ID) ".$commentfromwhere;
	  $commentwordsquery 	= $commentfromwhere;
	  
	  $trackbackquery = str_replace("c.comment_type = ''","c.comment_type != ''",$commentsquery);
	  
	  $postwordsquery = "FROM $wpdb->posts p WHERE p.post_status = 'publish' AND p.post_type = 'post'".$periodquery.$authorquery;
	  
	  $stats['authors'] 		= $wpdb->get_var($authorsquery);
	  $stats['posts'] 		= $wpdb->get_var($postsquery);
	  $stats['comments'] 		= $wpdb->get_var($commentsquery);
	  $stats['trackbacks']	= $wpdb->get_var($trackbackquery);
	  $stats['postwords'] 	= $this->bm_wordcount($postwordsquery,"post_content","ID");
	  $stats['commentwords'] 	= $this->bm_wordcount($commentwordsquery,"comment_content","comment_ID");
	  if ($period == "alltime") {
		$stats['firstpost'] = $wpdb->get_var($firstpostquery);
		$stats['bloggingmonths'] 	= floor( ( time() - strtotime($stats['firstpost']) ) / 2628000);
		if ($stats['bloggingmonths'] == 0) {
		  $stats['bloggingmonths'] = 1;
		}
	  }
	  else if ($period == "month") {
		$stats['bloggingmonths']	= 1;
	  }
	  if ($stats['posts'] > 0) {
		$stats['avgposts'] 		= round($stats['posts'] / $stats['bloggingmonths'],1);
	  }
	  
	  if ($stats['comments'] > 0 && $stats['posts'] > 0) {
		$stats['avgcomments'] = round(($stats['comments'] / $stats['posts']),1);
	  }
	  else {
		$stats['avgcomments'] = 0;
	  }
	  if ($stats['avgcomments'] > 1 && $options['stddev']) {
		$commentstddevquery = "SELECT (COUNT(c.comment_ID)-".$stats['avgcomments'].")*(COUNT(c.comment_ID)-".$stats['avgcomments'].") AS commentdiff2 ".$commentfromwhere." GROUP BY c.comment_post_ID";
		$results = $wpdb->get_results($commentstddevquery);
		$totaldev = 0;
		foreach($results as $result) {
		  $totaldev += $result->commentdiff2;
		}
		$stats['stddevcomments'] = round(sqrt($totaldev / $stats['posts']),1);
	  }
	  if ($stats['trackbacks'] > 0) {
		$stats['avgtrackbacks'] = round($stats['trackbacks'] / $stats['posts'],1);
	  }
	  else {
		$stats['avgtrackbacks'] = 0;
	  }
	  if ($stats['avgtrackbacks'] > 1 && $options['stddev']) {
		$trackbacksstddevquery = str_replace("c.comment_type = ''","c.comment_type != ''",$commentstddevquery);
		$results = $wpdb->get_results($trackbacksstddevquery);
		$totaldev = 0;
		if ($results) {
		  foreach($results as $result) {
			$totaldev += $result->commentdiff2;
		  }
		  $stats['stddevtrackbacks'] = round(sqrt($totaldev / $stats['posts']),1);
		}
		else {
		  $stats['stddevtrackbacks'] = 0;
		}
	  }
	  if ($stats['postwords'] > 0 && $options['stddev'] && $stats['posts'] > 1) {
		$stats['stddevpostwords'] 	= $this->bm_wordcount($postwordsquery,"post_content","ID",($stats['postwords'] / $stats['posts']));
	  }
	  
	  $stats['period'] = $period;
	  return $stats;
	}
	function bm_wordcount($statement, $attribute, $countAttribute, $avg = 0) {
	  global $wpdb;
	  $result=0;
	  
	  $countStatement = "SELECT COUNT(".$countAttribute.") " .$statement;
	  $counter = $wpdb->get_var($countStatement);
	  $startLimit = 0;
	  
	  $rows_at_Once=$counter;
	  
	  $incrementStatement = "SELECT ".$attribute." ".$statement;
	  
	  $intermedcount = 0;
	  
	  while( $startLimit < $counter) {
		$query = $incrementStatement." LIMIT ".$startLimit.", ".$rows_at_Once;
		$results = $wpdb->get_col($query);
		//count the words for each statement
		$intermedcount += count($results);
		for ($i=0; $i<count($results);
			 $i++) {
		  $sum = str_word_count($results[$i]);
		  if ($avg == 0) {
			$result += $sum;
		  }
		  else {
			$intermed += ($sum*$sum);
		  }
		}
		$startLimit+=$rows_at_Once;
	  }
	  if ($avg != 0) {
		$result = sqrt($intermed/$intermedcount);
	  }
	  return $result;
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
	  
	  
	}
	// end register_admin_styles
	
	/**
	* Registers and enqueues admin-specific JavaScript.
	*
	* @version		1.0
	* @since 		1.0
	*/	
	public function register_admin_scripts() {
	  
	  wp_enqueue_script( 'jquery-ui-datepicker' );
	  wp_enqueue_script( 'wp-jquery-date-picker', plugins_url( CHIEF_EDITOR_PLUGIN_NAME . '/js/chief-editor.js' ) );
	  
	}
	// end register_admin_scripts
	
		
	public function recent_mu_posts( $howMany = 10 ) {
	  
	  //global $blog_id;
	  // get an array of the table names that our posts will be in
	  // we do this by first getting all of our blog ids and then forming the name of the 
	  // table and putting it into an array
	  if ( !is_multisite() ) {
		
		global $wpdb;
		
		$querystr = "
SELECT DISTINCT $wpdb->posts.* 
FROM $wpdb->posts, $wpdb->postmeta
WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id     
AND ($wpdb->posts.post_status != 'publish' AND $wpdb->posts.post_status != 'inherit' AND $wpdb->posts.post_status != 'auto-draft' AND $wpdb->posts.post_status != 'trash')
AND $wpdb->posts.post_type = 'post'
ORDER BY $wpdb->posts.post_status DESC, $wpdb->posts.post_date DESC
";
		
		$rows = $wpdb->get_results($querystr, OBJECT);
		
		//echo 'count($rows) '.count($rows);
		
	  }
	  else {
		
		$rows = $this->get_all_pending_posts_multisite();
		//echo 'MULTISITE :: count($rows) '.count($rows);
		
	  }
	  // now we need to get each of our posts into an array and return them
	  if ( $rows ) {
		$nb_of_scheduled = 0;
		$nb_of_drafts = 0;
		$nb_of_pending = 0;
		$futureColor = $this->get_post_color_from_status('future');//'#A4F2FF';
		$draftColor = $this->get_post_color_from_status('draft');//'#EDEDED';
		$pendingColor = $this->get_post_color_from_status('pending');//'#9CFFA1';
		$tableHeaderColor = "#6B6B6B";
		//echo '<hr>';
		//echo '<h2>Posts</h2>';
		//echo '<h4>Total non published post(s) found : '. count($rows).'</h4>';
		echo '<br/>';
		$chief_editor_table_header = '<table class="sortable" style="border:solid #6B6B6B 1px;width:100%;">';
		$chief_editor_table_header = $chief_editor_table_header . '<tr style="background-color:'.$tableHeaderColor.';color:#FFFFFF">';
		$chief_editor_table_header = $chief_editor_table_header . '<td>#</td><td>' . __('Blog Title','chief-editor') . '</td><td>' . __('Featured image','chief-editor') . '</td>';
		$chief_editor_table_header = $chief_editor_table_header . '<td>Post</td><td>'.__('Submission date','chief-editor').'</td><td>'.__('Status','chief-editor').'</td>';
		$chief_editor_table_header = $chief_editor_table_header . '<td>'.__('Excerpt','chief-editor').'</td><td>'.__('Author (login)','chief-editor').'</td>';
		$chief_editor_table_header = $chief_editor_table_header . '<td>'.__('Scheduled for date','chief-editor').'</td></tr>';
		echo $chief_editor_table_header;
		$posts = array();
		$countIdx = 0;
		foreach ( $rows as $row ) {
		  $countIdx++;
		  $data = $row->ID;
		  
		  if ( is_multisite() ) {
			$blog_id = $row->blog_id;
			$current_blog_details = get_blog_details( $blog_id );
			$blog_path = $current_blog_details->path;
			$blog_name = $current_blog_details->blogname;
			$permalink = get_blog_permalink( $blog_id, $data );
			$new_post = get_blog_post( $blog_id, $data );
		  }
		  else {
			$blog_id = '0';
			//$bloginfo = get_bloginfo();
			$blog_path = get_bloginfo('url');
			$blog_name = get_bloginfo('name');
			$new_post = get_post( $data );
			$permalink = get_permalink( $data );
		  }
		  
		  $post_id = $new_post->ID;
		  $title = $new_post->post_title;
		  
		  $post_thumbnail = '';
		  $post_thumbnail .= '<a href="' . $permalink . '" title="' . esc_attr( $title) . '">';
		  //$post_thumbnail .= '<img src="'.$this->multisite_get_thumb($post_id,100,100,$blog_id,true,true).'"/>';
		  if ( is_multisite() ) {
			$post_thumbnail .= $this->get_the_post_thumbnail_by_blog($blog_id,$post_id,array(100,100));
		  }
		  else {
			$post_thumbnail .= get_the_post_thumbnail( $post_id, array(100,100));
		  }
		  $post_thumbnail .=  '</a>';
		  //echo $post_thumbnail;
		  //} else {
		  //echo 'no thumbnail... for post '.$post_id;
		  //}
		  $abstract = $new_post->post_excerpt;
		  
		  $author = $new_post->post_author;
		  $user_info = get_userdata($author);
		  $userlogin = $user_info->user_login;
		  $userdisplayname = $user_info->display_name;
		  #echo 'Username: ' . $user_info->user_login . "\n";
		  #echo 'User roles: ' . implode(', ', $user_info->roles) . "\n";
		  #echo 'User ID: ' . $user_info->ID . "\n";
		  $date_format = 'l, F jS, Y';
		  $creation_date = get_the_time( $date_format, $post_id );
		  $date = $new_post->post_date;
		  $post_state = $new_post->post_status;
		  $line_color = $this->get_post_color_from_status($post_state);
		  //$post_state == 'future' ? $futureColor : ( $post_state == 'pending' ? $pendingColor : $draftColor);
		  
		  if ($post_state == 'future') {
			$nb_of_scheduled++;
		  }
		  elseif ($post_state == 'draft') {
			$nb_of_drafts++;
		  }
		  elseif ($post_state == 'pending') {
			$nb_of_pending++;
		  }
		  
		  $complete_new_table_line = '<tr style="background-color:'.$line_color.';">';
		  $complete_new_table_line .= '<td>'.$countIdx.'</td>';
		  $complete_new_table_line .= '<td><a href="'.$blog_path.'" target="_blank"><h4>'.$blog_name.'</h4></a></td>';
		  $complete_new_table_line .= '<td>'.$post_thumbnail.'</td>';
		  $edit_post_link = '';
		  if ( is_multisite() ) {
			$edit_post_link .= $this->get_multisite_post_edit_link($blog_id ,$post_id);
		  }
		  else {
			$edit_post_link .= get_edit_post_link( $post_id);
		  }
		  
		  $complete_new_table_line .= '<td><span style="font-size:16px;"><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$title.'</a></span> (<a href="'.$edit_post_link.'" target="_blank">Edit</a>)</td>';
		  $complete_new_table_line .= '<td>'.$creation_date.'</td>';
		  $status_image = CHIEF_EDITOR_PLUGIN_URL . '/images/'.$post_state.'.png';
		  $status_meaning = $this->get_post_status_meaning_from_status($post_state);
		  $complete_new_table_line .= '<td>'.$status_meaning.'<br/><img src="'.$status_image.'"/></td>';
		  $complete_new_table_line .= '<td>'.$abstract.'</td>';
		  $complete_new_table_line .= '<td>'.$userdisplayname.' ('.$userlogin.')';
		  $complete_new_table_line .= '<div class="wrap"><form id="'.$post_id.'_chief-editor-bat-form" class="chief-editor-bat-form" action="" method="POST">';
		  $complete_new_table_line .= '<div><input type="submit" id="'.$post_id.'_chief-editor-bat-submit" name="chief-editor-bat-submit" class="chief-editor-bat-submit button-primary" value="'.__('Send BAT to author','chief-editor').'"/>';
		  $complete_new_table_line .= '<input type="hidden" id="postID" name="postID" value="'.$post_id.'">';
		  $complete_new_table_line .= '<input type="hidden" id="blogID" name="blogID" value="'.$blog_id.'">';
		  $complete_new_table_line .= '<input type="hidden" id="authorID" name="authorID" value="'.$author.'">';
		  $complete_new_table_line .= '</div></form><div id="ce_dialog_email" class="ce_dialog_email" title="Dialog Title" style="display:none">Some text</div></div>';
		  $complete_new_table_line .= '</td>';
		  
		  if ($post_state == 'future') {
			$complete_new_table_line .= '<td><h3>' . $date . '</h3></td>';
		  }
		  else {
			$complete_new_table_line .= '<td>'.__('not scheduled','chief-editor').'</td>';
		  }
		  
		  /*
		  $date_chooser_name = 'datepicker';//_'.$blog_id.'_'.$new_post->ID;
		  
		  $complete_new_table_line .= '<td><form name="changeDateForm" method="post" action="">';
		  $complete_new_table_line .= '<input type="hidden" name="post_id" value="'.$new_post->ID.'"/>';
		  $complete_new_table_line .= '<input type="hidden" name="blog_id" value="'.$blog_id.'">';
		  $change_date_button = '<input style="float:right;background-color:#2AA2CC;color:#000000;" id="save-post" class="button" type="submit" value="Schedule" name="submitDate"></input>';
		  $unschedule_button = '<input style="float:right;" id="save-post" class="button" type="submit" value="Unchedule" name="unschedulePost"></input>';
		  $complete_new_table_line .= '<input type="text" class="datepicker" name="'.$date_chooser_name.'" value="'.$date.'"/>'.$change_date_button.$unschedule_button.'</form></td>';
		  */
		  $complete_new_table_line .= '</tr>';
		  
		  echo $complete_new_table_line;
		  
		  $posts[] = $new_post;
		  
		}
		
		echo '</table>';
		echo '<hr>';
		echo '<table class="sortable" style="border:solid black 1px;width:50%;">';
		echo '<tr style="background-color:'.$futureColor.';"><td>'.__('Scheduled posts : ','chief-editor').'</td><td>'.$nb_of_scheduled.'</td></tr>';
		echo '<tr style="background-color:'.$pendingColor.';"><td>'.__('Pending posts : ','chief-editor').'</td><td>'.$nb_of_pending.'</td></tr>';
		echo '<tr style="background-color:'.$draftColor.';"><td>'.__('Draft posts : ','chief-editor').'</td><td>'.$nb_of_drafts.'</td></tr>';
		echo '<tr style="background-color:#ffffff;color:#000000;"><td>'.__('Total unpublished posts : ','chief-editor').'</td><td>'.count($rows).'</td></tr>';
		echo '</table>';
		echo '<hr>';
		
		
		//echo "<pre>"; print_r($posts); echo "</pre>"; exit; # debugging code
		return $posts;
	  }
	  
	}
	
	function get_post_status_meaning_from_status($post_state) {
	  $result = $post_state;
	  /*if ($post_state == 'future') {
	  $result = $futureColor;
	  
	  } else if ($post_state == 'pending') {
	  $result = $pendingColor;
	  }else if ($post_state == 'pitch') {
	  $result = $pitchColor;
	  }else if ($post_state == 'assigned') {
	  $result = $assignedColor;
	  } else if ($post_state == 'in-progress') {
	  $result = $inProgressColor;
	  } else if ($post_state == 'bat') {
	  $result = $BATColor;
	  }*/
	  
	  return $result;
	}
	
	function get_post_color_from_status ($post_state) {
	  $futureColor = '#91FEFF';
	  $draftColor = '#EDEDED';
	  $pendingColor = '#CFF09E';
	  $pitchColor = '#FDD87F';
	  $assignedColor = '#FFADFB';
	  $inProgressColor = '#f3f5b1';
	  $BATColor = '#69D947';
	  $result = $draftColor;
	  if ($post_state == 'future') {
		$result = $futureColor;
		
	  } else if ($post_state == 'pending') {
		$result = $pendingColor;
	  }else if ($post_state == 'pitch') {
		$result = $pitchColor;
	  }else if ($post_state == 'assigned') {
		$result = $assignedColor;
	  } else if ($post_state == 'in-progress') {
		$result = $inProgressColor;
	  } else if ($post_state == 'bat') {
		$result = $BATColor;
	  }
	  
	  return $result;
	  //return $post_state == 'future' ? $futureColor : ( $post_state == 'pending' ? $pendingColor : $draftColor);
	  
	}
	
	
	function get_all_pending_posts_multisite() {
	  
	  global $wpdb;
	  global $table_prefix;
	  $rows = $wpdb->get_results( "SELECT blog_id from $wpdb->blogs WHERE
public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0';" );
	  
	  if ( $rows ) {
		$blogPostTableNames = array();
		foreach ( $rows as $row ) {
		  $blogPostTableNames[$row->blog_id] = $wpdb->get_blog_prefix( $row->blog_id ) . 'posts';
		}
		# print_r($blogPostTableNames); # debugging code
		
		// now we need to do a query to get all the posts from all our blogs
		// with limits applied
		if ( count( $blogPostTableNames ) > 0 ) {
		  $query = '';
		  $i = 0;
		  foreach ( $blogPostTableNames as $blogId => $tableName ) {
			if ( $i > 0 ) {
			  $query.= ' UNION ';
			}
			
			$query.= " (SELECT ID, post_status, post_date, $blogId as `blog_id` FROM $tableName WHERE (post_status != 'publish' AND post_status != 'inherit' AND post_status != 'auto-draft' AND post_status != 'trash') AND post_type = 'post')";
			$i++;
		  }
		  
		  #$query.= " ORDER BY post_status DESC, blog_id DESC, post_date DESC";// LIMIT 0,$howMany;";	
		  $query.= " ORDER BY post_status='pitch',post_status='assigned',post_status='draft',post_status='in-progress',post_status='pending',post_status='future', post_date DESC";
		  
		  #x_field='F', x_field='P'
		  # echo $query; # debugging code
		  $rows = $wpdb->get_results( $query );
		}
		return $rows;
	  }
	}
	
	
	
	function recent_multisite_comments() {
	  //echo '<h2>Comments</h2>';
	  $network_sites = wp_get_sites();
	  $number_of_items = '1000';
	  $result = array();
	  foreach ( $network_sites as $network_site ) :
	  //echo '<hr>';
	  $blog_path = $network_site['path'];
	  $blog_id = $network_site['blog_id'];
	  echo '<h2><b><u>Blog '.$blog_id.' : '.$blog_path.'</u></b></h2<br/>';
	  
	  switch_to_blog($blog_id);
	  
	  // $result = array_merge($result, (array)$this->getAllComments());
	  //add_filter('comments_clauses', 'mp_comments_last_week_filter' );
	  //$commentsFromLastWeek = $this->getCommentsFromLastWeek();
	  
	  //$comments = get_comments();
	  
	  
	  
	  echo '<h3>Pending</h3>';
	  echo $this->formatCommentsFromArray($this->getAllComments('hold',$number_of_items));
	  
	  echo '<h3>Approved</h3>';
	  echo $this->formatCommentsFromArray($this->getAllComments('approve',$number_of_items));
	  
	  echo '<h3>Spam</h3>';
	  echo $this->formatCommentsFromArray($this->getAllComments('spam',$number_of_items));
	  
	  echo '<h3>Trash</h3>';
	  echo $this->formatCommentsFromArray($this->getAllComments('trash',$number_of_items));
	  
	  echo '<hr>';
	  
	  //remove_filter( 'comments_clauses', 'mp_comments_last_week_filter' );
	  restore_current_blog();
	  endforeach;
	  
	  
	  return $result;
	  
	}
	
	function mp_comments_last_week_filter( $clauses ){
	  $last_week	= gmdate( 'W' ) - 1;
	  $query_args	= array('w'	=> $last_week);
	  $date_query	= new WP_Date_Query( $query_args, 'comment_date' );
	  echo $date_query;
	  $clauses['where'] .= $date_query->get_sql();
	  return $clauses;
	}
	
	
	
	function getAllCommentsMultisite($number,$start_date,$end_date) {
	  
	  global $wpdb;
	  $selects = array();
	  
	  $table_name = "{$wpdb->base_prefix}comments";
	  if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		//echo $table_name . 'EXISTS !';
		$selects[] = "(SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, 0 as blog_id FROM {$table_name}
WHERE comment_date >= '{
$start_date}'
AND comment_date < '{
$end_date}'
ORDER BY comment_date_gmt DESC LIMIT {$number})"; // real number is (number * # of blogs)
		
	  } else {
		//echo $table_name . 'DOES NOT EXISTS !';
	  }
	  
	  foreach (wp_get_sites() as $blog) {
		
		if ($blog['blog_id'] == '1') {
		  $table_name = "{$wpdb->base_prefix}{$blog['blog_id']}_comments";
		  if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			//echo $table_name . ' skipped !';
			continue;
		  } else {
			//echo $table_name . ' EXISTS !';
		  }
		}
		/*LEFT JOIN {$wpdb->base_prefix}{$blog['blog_id']}_posts
		ON comment_post_id = id
		WHERE post_status = 'publish'
		AND post_password = ''
		AND comment_approved = '1'*/
		// select only the fields you need here!
		$selects[] = "(SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, {$blog['blog_id']} as blog_id FROM {$wpdb->base_prefix}{$blog['blog_id']}_comments
WHERE comment_date >= '{
$start_date}'
AND comment_date < '{
$end_date}'
ORDER BY comment_date_gmt DESC LIMIT {$number})"; // real number is (number * # of blogs)
	  }
	  
	  //echo $selects;
	  $query = implode(" UNION ALL ", $selects)." ORDER BY comment_date_gmt DESC";
	  //echo '<br/>'.$query;
	  
	  $comments = $wpdb->get_results($query);
	  //echo '<br/>count : '. count($comments);
	  
	  return $comments;
	}
	
	
	function getAllComments($status = '', $number = 100) {
	  
	  /*
	  $args = array (
	  'status'         => 'hold',
	  'type'           => 'comment',
	  'number'         => '10',
	  'meta_query'     => array(
	  array(
	  'key'       => 'comment_date',
	  'value'     => 'strtotime(\'2 weeks ago\')',
	  'compare'   => '>=',
	  'type'      => 'DATE',
	  ),
	  ),
	  );
	  
	  */
	  //$start_date = strtotime('1 year ago');
	  //date("Y-m-d H:i:s");
	  $last_month = mktime(0, 0, 0, date("m")-100, date("d"),   date("Y"));
	  //echo $last_month;
	  $start_date = date( 'Y-m-d H:i:s', $last_month );
	  $end_date = date('Y-m-d H:i:s');
	  echo $start_date.'<=>'.$end_date;
	  //echo 'MYSQL format: '.current_time('mysql');
	  if (false) {
		$comment_status = $status;
		global $wpdb;
		$sql = "SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_content, comment_approved, comment_type, comment_parent
FROM wp_comments WHERE comment_date > '".$start_date."' AND comment_approved = ".$comment_status." ORDER BY comment_date_gmt DESC";
		$comments = $wpdb->get_results($sql);
		
		
	  } else {
		$args = array(
		  
		  'author_email' => '',
		  'ID'           => '',
		  'karma'        => '',
		  'number'       => $number,
		  'offset'       => '',
		  'orderby'      => '',
		  'order'        => 'DESC',
		  'parent'       => '',
		  'post_ID'      => '',
		  'post_id'      => 0,
		  'post_author'  => '',
		  'post_name'    => '',
		  'post_parent'  => '',
		  'post_status'  => '',
		  'post_type'    => '',
		  'status'       => $status,
		  'type'         => 'comment',
		  'user_id'      => '',
		  'search'       => '',
		  'count'        => false,
		  'meta_key'     => '',
		  'meta_value'   => '',
		  'meta_query'   => array(
			array(
			  'key'       => 'comment_date',
			  'value'     => $start_date,
			  'compare'   => '>',
			  'type'		=> 'CHAR',
			),
		  ),
		);
		
		
		/*
		$start_date = date('Y'.$month.'01'); // First day of the month
		$end_date = date('Y'.$month.'t'); // 't' gets the last day of the month
		
		$meta_query = array(
		'key'       => 'event_start_date',
		'value'     => array($start_date, $end_date),
		'compare'   => 'BETWEEN',
		'type'      => 'DATE'
		);
		
		array(
		array(
		'key'       => 'comment_date',
		'value'     => '2007-01-01 00:00:00',
		'compare'   => '>',
		'type'		=> 'DATE',
		),
		),
		*/
		
		
		// The Query
		$comments_query = new WP_Comment_Query;
		
		$comments = $comments_query->query( $args );
	  }
	  //$mq_sql = $comments_query->get_search_sql();//->get_sql( 'comment', $wpdb->comments, 'comment_ID');
	  // $this->meta_query->get_sql( 'comment', $wpdb->comments, 'comment_ID', $this );
	  // echo "Last SQL-Query: {$customPosts->request}";
	  //echo 'SQL:<br/>' . $my_sql;
	  
	  
	  return $comments;
	}
	
	function formatCommentsFromArray($comments) {
	  if ( $comments ) {
		$line_color = '#DEDEDE';
		$border_color = '#6B6B6B';
		$out = '<table class="sortable" style="border:solid '.$border_color.' 1px;
width:100%;
border-collapse:collapse;">';
		$out .= '<tr><th>Author</th><th>Answer</th><th>Comment</th><th>Post</th><th>Blog</th></tr>';
		
		foreach ( $comments as $comment ) {
		  
		  $comment_id = $comment->comment_ID;
		  $post_id = $comment->comment_post_ID;
		  //echo $post_id;
		  if (is_multisite()){
			switch_to_blog( $comment->blog_id );
		  }
		  $post_permalink = get_permalink($post_id); // use $blog_id
		  $post_title = get_the_title($post_id);
		  if (is_multisite()){
			$blogdetails = get_blog_details( $comment->blog_id );
			$blog_path = $blogdetails->path;
			$blog_permalink = get_blog_permalink( $comment->blog_id, $post_id );
			restore_current_blog();
		  } else {
			$blog_path = get_bloginfo('url');
			$blog_permalink = get_bloginfo('url');
			
		  }
		  //echo $post_permalink;
		  $out .= '<tr style="background-color:'.$line_color.';
border:solid '.$border_color.' 1px;">';
		  //$out .= '<tr><td>'.$comment->comment_post_ID .'</td>';
		  $out .= '<td style="border:solid '.$border_color.' 1px;">'.$comment->comment_author .'<br/><i>'.$comment->comment_author_email .'</i></td>';
		  $link_to_comment = '<a href="'.$post_permalink.'#comment-'.$comment->comment_ID.'" rel="external nofollow" title="'.$post_title.'" target="_blank">';
		  $out .= '<td style="border:solid '.$border_color.' 1px;
text-align:center;">';
		  $out .= $link_to_comment;//<a href="'.get_comment_link($comment).'" target="_blank">';
		  $out .= '<input style="text-align:center;background-color:#2AA2CC;color:#000000;" id="show-comment" class="button" type="submit" value="Answer" name="showComment"></input></a>';
		  $comment_status = 'spam';
		  // $out .= '<a href="'. wp_set_comment_status( $comment_id, $comment_status ).'" target="_blank"><input style="float:right;background-color:#CC0000;color:#000000;" id="spam-comment" class="button" type="submit" value="Spam" name="spamComment"></input></a>';
		  $out .= '</td>';
		  $out .= '<td style="border:solid '.$border_color.' 1px;">Written on '.$comment->comment_date . '<br/>' . $comment->comment_content . '</td>';
		  $out .= '<td style="border:solid '.$border_color.' 1px;"><a href="'.$post_permalink.'" target="_blank">'.$post_title . '</a></td>';
		  $out .= '<td style="border:solid '.$border_color.' 1px;"><a href="'.$blog_path.'" target="_blank">'.$blog_path . '</a></td>';
		  $out .= '</tr>';
		}
		$out .= '</table>';
	  } else {
		$out = 'No comments found.';
	  }
	  return $out;
	}
	
	function get_multisite_post_edit_link($blogID, $postID) {
	  
	  switch_to_blog($blogID);
	  
	  $out = get_edit_post_link($postID);
	  //echo 'WOW'.$edit_post_link;
	  
	  restore_current_blog();
	  
	  return $out;
	  
	}
	
	function multisite_get_thumb($postID, $w = 400, $h = 300, $blogID = 1, $link = true, $return = false) {
	  
	  switch_to_blog($blogID);
	  $scriptpath = get_bloginfo('template_directory');
	  
	  if( $thumbnail = get_post_meta($postID, 'thumbnail', true) ){
		$iurl = '/wp-content/iptv/img/'.$thumbnail;
	  } else {
		
		//$images = get_children( array( 'post_parent' => $postID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order', 'order' => 'ASC', 'numberposts' => 999 ) );
		
		$images = get_children(array('post_parent' => $postID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order'));
		
		//echo "Getting featured image for post id ".$blogID."/".$postID." nbOfImages :".count($images)."\n";
		
		if ( $images ){
		  $img = array_shift($images);
		  $imagelink = wp_get_attachment_image_src($img->ID,array($w,$h));
		  $iurl = $imagelink[0];
		  echo $blogID.'/'.$postID.' $iurl:'.$iurl;
		}
	  }
	  $out = '';
	  if( $iurl ){
		$img = $iurl;
		$out .= $img;
		/*
		if( $link )
		$out .= '';
		$out .= '';
		if( $link ) 
		$out .= '';
		*/
	  }
	  
	  restore_current_blog();
	  
	  if($return) {
		return $out;
	  } else {
		echo $out;
	  }
	}
	
	//if( !function_exists( 'get_the_post_thumbnail_by_blog' ) ) {
	function get_the_post_thumbnail_by_blog($blog_id=NULL,$post_id=NULL,$size='thumbnail',$attrs=NULL) {
	  global $current_blog;
	  $sameblog = false;
	  
	  if( empty( $blog_id ) || $blog_id == $current_blog->blog_id ) {
		$blog_id = $current_blog->blog_id;
		$sameblog = true;
	  }
	  if( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	  }
	  if( $sameblog )
		return get_the_post_thumbnail( $post_id, $size, $attrs );
	  
	  if( !$this->has_post_thumbnail_by_blog($blog_id,$post_id) )
		return false;
	  
	  global $wpdb;
	  //$oldblog = $wpdb->set_blog_id( $blog_id );
	  switch_to_blog($blog_id);
	  
	  $blogdetails = get_blog_details( $blog_id );
	  // str_replace ( mixed $search , mixed $replace , mixed $subject [, int &$count ] )
	  //echo 'Replace '.$current_blog->domain . $current_blog->path.' by '.$blogdetails->domain . $blogdetails->path.' in '.get_the_post_thumbnail( $post_id, $size, $attrs );
	  $thumbcode = str_replace( $current_blog->domain . $current_blog->path, $blogdetails->domain . $blogdetails->path, get_the_post_thumbnail( $post_id, $size, $attrs ) );
	  
	  //$wpdb->set_blog_id( $oldblog );
	  restore_current_blog();
	  return $thumbcode;
	}
	
	function has_post_thumbnail_by_blog($blog_id=NULL,$post_id=NULL) {
	  if( empty( $blog_id ) ) {
		global $current_blog;
		$blog_id = $current_blog->blog_id;
	  }
	  if( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	  }
	  
	  global $wpdb;
	  $oldblog = $wpdb->set_blog_id( $blog_id );
	  
	  $thumbid = has_post_thumbnail( $post_id );
	  $wpdb->set_blog_id( $oldblog );
	  return ($thumbid !== false) ? true : false;
	}
	
	function the_post_thumbnail_by_blog($blog_id=NULL,$post_id=NULL,$size='post-thumbnail',$attrs=NULL) {
	  echo get_the_post_thumbnail_by_blog($blog_id,$post_id,$size,$attrs);
	}
  }
}