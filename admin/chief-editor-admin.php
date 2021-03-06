<?php 

if ( ! defined( 'ABSPATH' ) ) {
  
  exit;
  // Exit if accessed directly
}

define("CE_SCHEDULED_COLOR", "#91FEFF");
define("CE_INPRESS_COLOR", "#CFF09E");
define("CE_DRAFT_COLOR", "#cccccc");
define("CE_NEW_COLOR", "#FDD87F");
define("CE_INPRESS_SENT_COLOR", "#f3f5b1");
define("CE_ASSIGNED_COLOR", "#FFADFB");
define("CE_PUBLISHED_COLOR","#BAADFB");

$ordered_statuses_array = array('future','pending','in-progress','draft','assigned','pitch');
/*
function filter_where($where = '') {
	$where .= " AND post_date >= '" . '2015-01-01' . "'";
  	$where .= " AND post_date <= '" . '2015-05-31' . "'";
  	return $where;
}
add_filter('posts_where', 'filter_where');
*/
if (!defined('CHIEF_EDITOR_PLUGIN_NAME'))
  define('CHIEF_EDITOR_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (!defined('CHIEF_EDITOR_PLUGIN_DIR'))
  define('CHIEF_EDITOR_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . CHIEF_EDITOR_PLUGIN_NAME);

if (!defined('CHIEF_EDITOR_PLUGIN_URL'))
  define('CHIEF_EDITOR_PLUGIN_URL', WP_PLUGIN_URL . '/' . CHIEF_EDITOR_PLUGIN_NAME);

function log_me($message) {
  if ( WP_DEBUG === true ) {
	if ( is_array($message) || is_object($message) ) {
	  error_log( print_r($message, true) );
	}
	else {
	  error_log( $message );
	}
  }
}


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

/*
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
  
}*/

class Sort_Posts {
  var $order, $orderby;
  
  function __construct( $orderby, $order ) {
	$this->orderby = $orderby;
	$this->order = ( 'desc' == strtolower( $order ) ) ? 'DESC' : 'ASC';
  }
  
  function sort( $a, $b ) {
	if ( $a->{
	  $this->orderby}
		== $b->{
		  $this->orderby}
	   ) {
	  return 0;
	}
	
	if ( $a->{
	  $this->orderby}
		< $b->{
		  $this->orderby}
	   ) {
	  return ( 'ASC' == $this->order ) ? -1 : 1;
	}
	else {
	  return ( 'ASC' == $this->order ) ? 1 : -1;
	}
  }
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
	private $custom_post_type_keys = array();
	private $calendar_settings_key = 'chief_editor_calendar_tab';
	private $advanced_settings_key = 'chief_editor_comments_tab';
	private $stats_key = 'chief_editor_stats_tab';
	private $custom_stats_key = 'ched_custom_stats_tab';
	private $chief_editor_options_key = 'chief_editor_settings_tab';
	private $chief_editor_dashboard_page_name = 'chief-editor-dashboard';
	private $chief_editor_settings_tabs = array();
	/**
	* Start up
	*/
	public function __construct()
	{
	  
	  
	  add_action( 'admin_init', array( $this, 'register_general_settings' ) );
	  
	  add_action( 'admin_init', array ($this, 'register_calendar_tab'));
	  add_action( 'admin_init', array( $this, 'register_advanced_settings' ) );
	  add_action( 'admin_init', array ($this, 'register_stats_tab'));
	  //add_action( 'admin_init', array ($this, 'register_options_tab'));
	  //add_action( 'admin_init', array ($this, 'settings_page_init'));
	  //add_action('admin_menu', array($this,'ched_register_settings_submenu_page'));
	  
	  add_action( 'admin_menu', array( $this, 'add_admin_menus' ));
	  //add_action( 'admin_enqueue_scripts',array( $this,'chief_editor_load_scripts'));
	  add_action( 'wp_ajax_ce_send_author_std_validation_email', array( $this,'ce_process_ajax'));
	  add_action( 'wp_ajax_ce_send_author_std_validation_email_confirmed', array( $this,'ce_process_ajax_bat_confirm'));
	  
	  $this->init();
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
	  
	  $chief_editor_option_name = 'select_blog_'.$blogID.'_chief_editor';
	  $editors_in_chief_concerned = get_site_option($chief_editor_option_name);
	  
	  restore_current_blog();
	  
	  log_me('send_confirmation_email_to_author_of_post::');
	  log_me($editors_in_chief_concerned);
	  $recipients_array = array();
	  // build mail content with std text
	  $recipients_array[] = $user_email;
	  
	  $current_user = wp_get_current_user();
	  if ($current_user instanceof WP_User)
	  {
	  	$recipients_array[] = $current_user->user_email;
		log_me('Adding current user to email recipient : '.$current_user->user_email);
		  
	  }
	  
	  $recipients_array = array_merge($recipients_array,explode(',',get_site_option('email_recipients')));
	  
	  $multiple_to_recipients = $user_email.','.get_site_option('email_recipients');
	  foreach ($editors_in_chief_concerned as $new_user_id) {
	  	$user_info = get_userdata($new_user_id);
		$user_email = $user_info->user_email;
		$recipients_array[] = $user_email;
		log_me('Adding chief editor : '.$user_email);
	  }
	  
	  $recipients_array = array_unique($recipients_array);
	  log_me($recipients_array);
	  $recipients_array = array_values( array_filter($recipients_array));
	  log_me($recipients_array);
	  $multiple_to_recipients = implode(',', $recipients_array);
	  log_me('All recipients of ready for printing email : '.$multiple_to_recipients);
	  	    
	  $msg_object = __("In Press",'chief-editor').' : '.$post_title;
	  
	  // add other email recipients
	  $sender_email = get_site_option('sender_email');
	  $sender_name = get_site_option('sender_name');
	  
	  if (empty($sender_email) || empty($sender_name)) {
	  	$message_to_user = __("Please fill in sender name and email in network settings",'chief-editor');
		log_me($message_to_user);
	  	echo $message_to_user;
		return;
	  }
	  
	  // send email to recipents
	  $headers[] = "From: ".$sender_name." <".$sender_email.">";
	  $headers[] = "Content-type: text/html";
	  
	  $search = array ('/%username%/', '/%userlogin%/','/%useremail%/', '/%postlink%/', '/%posttitle%/','/%blogurl%/','/%n%/');
	  
	  $replace = array ($user_displayname, $user_login,( $user_email == "" ? "no email" : $user_email ), $permalink, $post_title,$blog_url, "\n");
	  
	  $msg_content = preg_replace($search, $replace, get_site_option('email_content-textarea'));
	  $msg_content = stripslashes_deep($msg_content);
	  $success = wp_mail( $recipients_array, $msg_object, $msg_content, $headers );
	  	  
	  // send confirmation for ajax callback
	  $message_to_user = $success ? __('Email sent successfully','chief-editor') .__(' to ')."\n".$multiple_to_recipients : __('Problem sending email...','chief-editor') . "\n" 
		. $multiple_to_recipients . "\n" .$msg_object ."\n" . $msg_content ."\n"."From ".$sender_name."<".$sender_email.">";
	  //. $multiple_to_recipients .'\n' . $msg_object.'\n' . $headers'\n' . $msg_content;
	  
	  log_me($message_to_user);
	  echo $message_to_user;
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
	  
	  $recipients_array = array();
	  // build mail content with std text
	  //$recipients_array[] = $user_email;
	  $cc_emails = get_site_option('email_recipients');
	  if (!empty($cc_emails)) {
	  	$recipients_array = explode(',',$cc_emails);
	  }
	  $chief_editor_option_name = 'select_blog_'.$bID.'_chief_editor';
	  $editors_in_chief_concerned = get_site_option($chief_editor_option_name);
	  
	  //$multiple_to_recipients = $user_email.','.get_site_option('email_recipients');
	  foreach ($editors_in_chief_concerned as $new_user_id) {
	  	$user_info = get_userdata($new_user_id);
		$user_email = $user_info->user_email;
		$recipients_array[] = $user_email;
		log_me('Adding chief editor : '.$user_email);
	  }
	  
	  $recipients_array = array_unique($recipients_array);
	  log_me($recipients_array);
	  
	  echo '<form id="'.$bID.'_'.$pID.'_chief-editor-bat-form-send" class="chief-editor-bat-form-send" action="" method="POST"><div>';
	  echo __('Are you sure you want to sent "ready for printing" email?','chief-editor').'<br/>';
	  echo '<b>'.$title . '</b><br/>';
	  
	  $emailList = '<ul>';
	  
	  foreach ($recipients_array as $user_email) {
		$user = get_user_by( 'email', $user_email );
		//$user_info = get_userdata($user);
		//$user_email = $user_info->user_email;
		$user_display = $user->display_name;
		$emailList .= '<li>' . $user_display.' ( '.$user_email.' )'.'</li>';
	  }
	  
	  $emailList .= '</ul>';
	  
	  echo  $emailList;
	  echo '<input type="hidden" id="postID" name="postID" value="'.$pID.'">';
	  echo '<input type="hidden" id="blogID" name="blogID" value="'. $bID .'">';
	  echo '<input type="submit" id="'.$bID.'_'.$pID.'chief-editor-bat-send-confirm" name="chief-editor-bat-send-confirm" class="chief-editor-bat-send-confirm button-primary" value="';
	  echo __('Send','chief-editor').'"/>';
	  $loading_image =  CHIEF_EDITOR_PLUGIN_URL . '/images/loading_ring_fullframe.gif';
	  echo '<img id="'.$bID.'_'.$pID.'ce_loading_icon" class="ce_loading_icon" src="'.$loading_image.'" style="width:22px;height:auto;display:none;"></img>';
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
	
	
	
	function register_general_settings() {
	  if (current_user_can('edit_others_posts')) {
		$this->chief_editor_settings_tabs[$this->general_settings_key] = __('Posts','chief-editor');
	  }
	  
	  if (current_user_can('delete_others_pages')) {
		$post_types = self::getAllCustomPostTypes();
		
		foreach ( $post_types  as $post_type ) {
		  
		  //echo '<p>' . $post_type . '</p>';
		  //log_me($post_type );
		  $element_name = 'checkbox_'.$post_type;
		  $checked = (get_site_option($element_name) == 1);
		  //log_me('"'.$this->options[$element_name].'"');
		  //log_me($post_type .' => '.$element_name. ' : '.$this->options[$element_name] . ' ' .$checked);
		  if ($checked) {
			$this->custom_post_type_keys[] = $post_type;
			$this->chief_editor_settings_tabs[$post_type] = __($post_type,'chief-editor');
		  }
		}
		
		
	  }
	  
	}
	
	function register_calendar_tab() {
	  if (current_user_can('delete_others_pages')){
		$this->chief_editor_settings_tabs[$this->calendar_settings_key] = __('Calendar','chief-editor');
	  }
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
	  if (current_user_can('delete_others_pages')){
		$this->chief_editor_settings_tabs[$this->advanced_settings_key] = __('Comments','chief-editor');
	  }
	  
	}
	
	function register_stats_tab() {
	  if (current_user_can('delete_others_pages')){
		$this->chief_editor_settings_tabs[$this->stats_key] = __('Authors','chief-editor');
		$this->chief_editor_settings_tabs[$this->custom_stats_key] = __('Custom Stats','chief-editor');
		
	  }
	}
	
	function register_options_tab() {
	  if (current_user_can('edit_users')){
		$this->chief_editor_settings_tabs[$this->chief_editor_options_key] = __('Settings','chief-editor');
	  }
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
	  if (current_user_can('edit_others_posts')) {
		
		//$chief_editor_settings = add_options_page( 'Chief Editor Settings', 'Chief Editor', 'read', $this->chief_editor_dashboard_page_name, array( $this, 'chief_editor_options_page' ) );
	  	$chief_editor_settings =add_menu_page('Chief Editor Dashboard', 'Chief Editor', 'read', $this->chief_editor_dashboard_page_name,  array( $this, 'chief_editor_options_page' ) , '');

	  }
	}
	
	function chief_editor_options_page() {
	  
	  $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
	  //screen_icon();
	  echo '<div style="text-align:center;padding:5px;">';
	  echo screen_icon() . '<h1>Chief Editor</h1>';
	  if (current_user_can('delete_others_pages')){
		echo '<a class="button-primary" href="http://wordpress.org/plugins/chief-editor/" target="_blank">'.__('Visit Plugin Site','chief-editor').'</a>';
		echo '<a  class="button-primary" style="color:#FFF600;" href="http://wordpress.org/support/view/plugin-reviews/chief-editor" target="_blank">'.__('Rate!','chief-editor').'</a>';
		//echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
	  }
	  echo '</div> ';
	  
	  echo '<h2 class="nav-tab-wrapper">';
	  foreach ( $this->chief_editor_settings_tabs as $tab_key => $tab_caption ) {
		$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
		echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->chief_editor_dashboard_page_name . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
	  }
	  echo '</h2>';
	  
	  if ($current_tab == 'chief_editor_posts_tab') {
		
		log_me('chief_editor_posts_tab');
		$allPosts = $this->recent_mu_posts();
		if (count($allPosts) == 0) {
		  echo '<p>'.__('No custom posts of type ','chief-editor').'<b>'.$current_tab.'</b></p>';
		  echo '<p>'.__('or','chief-editor').'</p>';
		  echo '<p>'.__('all of them are published','chief-editor').'</p>';
		}
		
	  }
	  elseif (in_array ($current_tab, $this->custom_post_type_keys)) {
		//echo $current_tab;
		$allPosts = $this->recent_mu_posts($current_tab);
		if (count($allPosts) == 0) {
		  echo '<p>'.__('No custom posts of type ','chief-editor').'<b>'.$current_tab.'</b></p>';
		  echo '<p>'.__('or','chief-editor').'</p>';
		  echo '<p>'.__('all of them are published','chief-editor').'</p>';
		}
		
	  }
	  elseif ($current_tab == 'chief_editor_calendar_tab') {
		
		$this->create_calendar_table();
		
	  }
	  elseif ($current_tab == 'chief_editor_comments_tab') {
		
		//$this->recent_multisite_comments();
		global $wpdb;
		$last_month = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
		$start_date = date('Y-m-d H:i:s', $last_month );
		$end_date = date('Y-m-d H:i:s');
		$intro_text = '<h3>'.__('All comments accross the network since ','chief-editor').$start_date.'</h3><br/>';
		
		if ( is_multisite() ) {
		  
		  echo '<table>';
		  echo '<tr>';
		  echo '<td>';
		  //$mostCommentedPosts = $this->getMostCommentedPosts(10);
		  echo '<h3>'.__('Most commented posts ever').'</h3><br/>'.$this->getMostCommentedPosts(10);
		  echo '</td>';
		  echo '<td>';
		  //$lastMonthIdx = date('m', strtotime('-1 month'));
		  $last_month_most_commented = mktime(0, 0, 0, date("m")-1, date("d"), date("Y"));
		  $current_month = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
		  $startDate = date('Y-m-01 H:i:s', $last_month_most_commented );
		  $endDate = date('Y-m-01 H:i:s', $current_month);
		  $mostCommentedPosts = $this->getMostCommentedPosts(10,$startDate,$endDate);
		  echo '<h3>'.__('Most commented posts last month').'</h3><br/>'.$startDate.' -> '.$endDate.'<br/>'.$mostCommentedPosts;
		  echo '</td>';
		  echo '</tr>';
		  echo '</table>';
		  
		  
		  
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
		
	  } elseif ($current_tab == 'chief_editor_stats_tab') {
		
		$this->bm_author_stats("alltime");
	  
	  } elseif ($current_tab == 'ched_custom_stats_tab') {
		
		/*
		$start = "2015-01-01";//'01-01-2015';
		$end = "2015-05-31";//'05-31-2015';
		$start = "2014-01-01";
		$end = "2014-05-31";*/
		//$settings = self::get_network_settings();
		$start = esc_attr( get_site_option( 'custom_stats_start_date' ));//"2015-05-01";
		$end = esc_attr( get_site_option( 'custom_stats_end_date' ));//"2015-05-31";
		log_me($start .' => '.$end);
		
		$startDate = DateTime::createFromFormat('d-m-Y', $start);
		$endDate = DateTime::createFromFormat('d-m-Y', $end);
		
		log_me($startDate);
		log_me($endDate);
		//$startDate = date('Y-m-d H:i:s', $start );
		//$endDate = date('Y-m-d H:i:s', $end);
		
		echo '<h2>Stats du '.$startDate->format('Y-m-d').' au '.$endDate->format('Y-m-d').'</h2>';
		// ched_custom_author_stats($period,$start_date,$end_date)
		$this->ched_custom_author_stats("alltime",$startDate,$endDate);
	  
	  } elseif ($current_tab == 'chief_editor_settings_tab' ) {
		
		/*echo '<form method="post" action="settings.php">';
		self::show_network_settings();
		submit_button(); 
		echo '</form>';*/
		
		
		/*
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
		*/
	  }
	  if (current_user_can('delete_others_pages')){
		echo '<div style="text-align:right;">';
		echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
		echo '</div> ';
	  }
	}
	
	
	public function get_comments_number_for_blog($blogid, $postid ){
	  
	  //echo "get_comments_number_for_blog : $blogid, $postid ";
	  switch_to_blog($blogid);
	  $result = get_comments_number( $postid );
	  restore_current_blog();
	  return $result;
	}
	
	function postBetweenDates($post,$startDate,$endDate){
	  
	  //$post_date = $post->post_date;
	  $format = 'Y-m-d';
	  $postDate = get_the_time($format,$post->ID);
	  $post_date = new DateTime($postDate);
	  $start_date = new DateTime($startDate);
	  $end_date = new DateTime($endDate);
	  
	  if ($post_date >= $start_date && $post_date <= $end_date) {
		
		return true;
	  } else {
		return false;
	  }
	}
	
	function getAllPostsOfAllBlogs($startDate = NULL, $endDate = NULL) {
	  
	  $network_sites = wp_get_sites();
	  
	  $result = array();
	  foreach ( $network_sites as $network_site ) {
		
		$blog_id = $network_site['blog_id'];
		
		switch_to_blog($blog_id);
		
		$allPostsOfCurrentBlog = get_posts(array(
		  'numberposts' => -1, 
		  'posts_per_page' => -1,
		  'post_type' => 'post',
		  'post_status' => array('publish','future')
		));
		
		if ($startDate != NULL && $endDate != NULL) {
		  
		  foreach ($allPostsOfCurrentBlog as $post) {
			if ($this->postBetweenDates($post,$startDate,$endDate)) {
			  $result[$blog_id][] = $post;
			} 
		  }
		} else {
		  $result[$blog_id] = $allPostsOfCurrentBlog;
		}
		
		// Switch back to the main blog
		restore_current_blog();
	  }
	  
	  
	  return $result;
	}
	
	public function getMostCommentedPosts($maxResults,$startDate = NULL,$endDate = NULL) {
	  
	  
	  $blog_posts_array = $this->getAllPostsOfAllBlogs($startDate,$endDate);
	  $postCommentsArray = array();
	  $postCommentsTitles = array();
	  $postCommentsPermalinks = array();
	  //echo 'count($blog_posts_array) '.count($blog_posts_array) ;
	  foreach ($blog_posts_array as $blogid => $postsOfBlog) {
		
		foreach ($postsOfBlog as $post) {
		  //echo "<br/>$blogid, $post->ID";
		  $nbOfComments = $this->get_comments_number_for_blog($blogid, $post->ID );
		  $postCommentsArray[$blogid .'_'.$post->ID] = $nbOfComments;
		  $postCommentsTitles[$blogid .'_'.$post->ID] = $post->post_title;
		  $postCommentsPermalinks[$blogid .'_'.$post->ID] = get_blog_permalink( $blogid, $post->ID );
		}
	  }
	  $result = '<h4>'.__('Total number of posts accross network: ','chief-editor').count($postCommentsArray).'</h4>';
	  $sortResult = arsort($postCommentsArray);
	  //echo '$sorted : '.count($postCommentsArray);
	  if ($sortResult) {
		
		$postComments = '<ol>';
		$idx = 1;
		foreach ($postCommentsArray as $key => $value) {
		  
		  if ($value) {
			
			$postComments .= '<li><a target="_blank" href="'.$postCommentsPermalinks[$key].'">'.$postCommentsTitles[$key]. '</a> | #comments : '.$value.'</li>';
			if ($idx == $maxResults) {
			  break;
			}
			$idx += 1;
		  }
		  
		}
		$postComments .= '</ol>';
		$result .= $postComments;
	  }
	  else {
		$result .= 'problem sorting...';
	  }
	  
	  return $result;
	}
	
	// sitewide settings
	
	// multisite wide settings
	
	private function init() {
 
        //Adds settings to Network Settings
        add_filter( 'wpmu_options'       , array( $this, 'show_network_settings' ) );
        add_action( 'update_wpmu_options', array( $this, 'save_network_settings' ) );
 
    }
 
    public static function save_network_settings() {
	  
	  //$posted_settings  = array_map( 'sanitize_text_field', $_POST['chief-editor'] );
	  
	  //log_me($_POST['chief-editor']);
	  foreach ($_POST['chief-editor'] as $settingKey => $settingValue) {
		//if ($settingItem)
	  	
		log_me('==> saving '.$settingKey.' =>'.$settingValue);
		if (strpos($settingKey,'textarea') !== false) {
		  
		  $posted_settings[$settingKey] = stripslashes(wp_filter_post_kses(addslashes($settingValue)));
		  
		} else if (strpos($settingKey,'select') !== false) {
		   log_me('Saving SELECT : ' .$settingKey.' =>'.$settingValue);
		  $posted_settings[$settingKey] = $settingValue;
		} else if (  strpos($settingKey,'checkbox') !== false) {
		  
		  log_me('Saving : ' .$settingKey.' =>'.$settingValue);
		  $posted_settings[$settingKey] = $settingValue;// == 'on' ? 1 : 0;
		  
		} else {
			$posted_settings[$settingKey] = sanitize_text_field($settingValue);
		}
	}
	  
	  $settings = self::get_network_settings();
	  foreach ($settings as $setting) {
		$isCheckbox = boolval(strpos($setting['type'],'checkbox') !== false);
		$inArray = boolval(array_key_exists($setting['id'], $posted_settings));
		log_me($setting['id'] .' : '.$isCheckbox. ' '.$inArray);
		if (!$inArray && $isCheckbox ) {
		  
		  //set to false:
		  $posted_settings[$setting['id']] = 0;//$posted_settings[$settingKey] == 1 ? 1 : 0;
		}
	  }
	  
	  log_me($posted_settings);
	  
        foreach ( $posted_settings as $name => $value ) {
		  //$valueToSave = esc_html($value);
            update_site_option( $name, $value );
        }
    }
 
	

	function ched_register_settings_submenu_page() {
		add_submenu_page( 'options-general.php', 'Chief Editor', 'Chief Editor Settings', 'manage_options', 'chief-editor-settings', array($this,'show_network_settings') );
	}


	
    public static function show_network_settings() {
	  echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
		echo '<h2>Chief editor settings</h2>';
        $settings = self::get_network_settings();
    ?>
        <h3><?php _e( 'Chief Editor Settings' ); ?></h3>
        <table id="menu" class="form-table">
            <?php
                foreach ( $settings as $setting ) :
	  			$tag = $setting['tag'] ? $setting['tag'] : 'input';
	  			$type = esc_attr($setting['type']);
	  			$date = $setting['date'] == 1 ? true : false;
	  //$callback = esc_attr($setting['callback']);
            ?>
 
            <tr valign="top">
                <th scope="row"><?php echo $setting['name']; ?></th>
                <td>
				  <?php if ($type === 'select') {
			  $blog_id = esc_attr($setting['blog_id']);
				$setting_id = esc_attr($setting['id']);
			  
			  //call_user_func($callback,$args);
			  $chief_editors_roles = array('contributor','author','editor');
			  $blogusers = array();
			  
			  
			  foreach ($chief_editors_roles as $role) {
				
				$other_blogusers = get_users( 'blog_id='.$blog_id.'&orderby=nicename&role='.$role );
				if ($other_blogusers){
				  $blogusers = array_merge($blogusers, $other_blogusers);
				}
			  }
			  
			  log_me( 'Showing multiple select for blog '.$blog_id.' : '. count($blogusers) . ' '.count($chief_editors_roles). ' : ');
			  
			  $fieldID = 'chief_editors_selector_'.$blog_id;
			  $fieldName = 'chief-editor['.$setting_id.']';
			  
			  printf (
				'<select multiple="multiple" name="%s[]" id="%s" class="widefat" size="5" style="margin-bottom:10px">',
				$fieldName,
				$fieldID
			  );
			  
			  // Each individual option
			  foreach( $blogusers as $user )
			  {
				$id = $user->ID;
				$userEmail = $user->user_email;
				$userLogin = $user->user_login;
				$userNicename = $user->display_name;
				log_me("Setting selected back $id : $userEmail = ");
				$checkedOptions =  get_site_option( $setting['id'] );
				log_me( $checkedOptions);
				
				printf(
				  '<option value="%s" %s style="margin-bottom:3px;">%s</option>',
				  $id,
				  in_array( $id, $checkedOptions) ? 'selected="selected"' : '',
				  $id .' - '.$userLogin.' - '.$userNicename . ' (' . $userEmail .')'
				);
			  }
			  
			  echo '</select>';			  
			  // $callback($args);
			} else {
		$item = '<'.$tag.' type="'. $type .'"';
	  				$item .= ($setting['size'] ? ' size="'.esc_attr($setting['size']) . '"' : '');
	  				$item .= ($setting['cols'] ? ' cols="'.esc_attr($setting['cols']) . '"' : '');
	  				$item .= ($setting['rows'] ? ' rows="'.esc_attr($setting['rows']) .'"' : '');
	  				$item .= ' name="chief-editor['.esc_attr($setting['id']).']"';
	  if ($tag === 'textarea') {
		// esc_textarea()
		$savedValue = esc_textarea( stripslashes_deep(get_site_option( $setting['id'] ) ));
		
		//$item .= ' value="'.$savedValue.'"';
		$item .= '>'.$savedValue.'</'.$tag.'>';
		
	  } else if ($type === 'checkbox') {
		
		$optionVal = get_site_option( $setting['id'] );
		//$currentState = boolval($optionVal);
		$checkPart = checked(1,$optionVal,false);
		$item .= ' value="1" ' . $checkPart;
		log_me('$currentState : '.$optionVal.' html:'.$checkPart);
		$item .= '/>';
		
	  } else if ($date) {
		
		$item .= ' class="datepicker" name="datepicker"';
		$item .= ' value="'.esc_attr( get_site_option( $setting['id'] ) ).'"';
		$item .= '/>';
	  } else {
	  	$item .= ' value="'.esc_attr( get_site_option( $setting['id'] ) ).'"';
		$item .= '/>';
	  }	  	
                    
				  	echo $item;
			}
				  ?>
				  <br /><?php echo '<em>'.$setting['desc'].'</em>'; ?>
                </td>
            </tr>
            <?php
        endforeach;
        echo '</table>';
	  
	  echo '</div>';
    }
 
    public static function get_network_settings() {
 
        $settings[] = array(
                    'id'   => 'sender_email',
                    'name' => __('Sender email address'),
                    'desc' => __( 'Email address used for sendings' ),
                    'type' => 'text',
                    'size' => 'regular'
        );
 
        $settings[] = array(
                    'id'   => 'sender_name',
                    'name' => __( 'Sender name' ),
                    'desc' => __( 'Name, as it will be seen by recipients' ),
                    'std'  => 'regular',
                    'type' => 'text'
        );
	  
	  $settings[] = array(
                    'id'   => 'email_recipients',
                    'name' => __( 'Recipients emails' ),
                    'desc' => __( 'Addresses to which all email will be sent to (use , as separator)' ),
                    'std'  => 'regular',
					'size' => '50',
                    'type' => 'text'
        );
	  
	  $settings[] = array(
                    'id'   => 'custom_stats_start_date',
                    'name' => __( 'Custom stats start date' ),
                    'desc' => __( 'The custom statistics will start from this date' ),
                    'std'  => 'regular',
					'size' => '50',
                    'type' => 'text',
					'date' => 1
        );
	  $settings[] = array(
                    'id'   => 'custom_stats_end_date',
                    'name' => __( 'Custom stats end date' ),
                    'desc' => __( 'The custom statistics will end with this date' ),
                    'std'  => 'regular',
					'size' => '50',
                    'type' => 'text',
					'date' => 1
        );

	  $settings[] = array(
                    'tag'  => 'textarea',
					'rows' => '20',
					'cols' => '110',
					'id'   => 'email_content-textarea',
                    'name' => __( 'Email content' ),
					'desc' => __( 'This is the standard email sent for to authors in order to validate the post' ).
		'<br/>'.
		__( 'You can use the following tags inside:').
		'<br/>'.
		'<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%username%</span>'.
		'<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%userlogin%</span>'.
		'<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%useremail%</span>'.
		'<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%postlink%</span>'.
		'<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%posttitle%</span>'.
		'<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%blogurl%</span>',
                    'std'  => '50',
                    'type' => 'text'
        );
	  
	  
	  
		  
	  $post_types = self::getAllCustomPostTypes();
	  
	  
	  foreach ( $post_types as $post_type ) {
		$args     = array (
		  'post_type' => $post_type
		);
		$element_name = 'checkbox_'.$post_type;
		$settings[] = array(
                    'id'   => $element_name,
		  'name' => __( 'Show posts of type ' ).'<br/><em>'.$post_type.'</em>',
                    'desc' => __( 'give you ability to manage posts of this type in a specific tab' ),
                    'std'  => 'regular',
		  //'size' => '50',
                    'type' => 'checkbox'
        );
		
	  }
	  
	  
	  // ******
	  	  
	  // Iterate through your list of blogs
	  foreach (wp_get_sites() as $blog) {
		//foreach ($blog_ids as $blog_id){
		$blog_id = $blog['blog_id'];
		
		// Switch to the next blog in the loop.
		// This will start at $id == 1 because of your ORDER BY statement.
		switch_to_blog($blog_id);
		
		// Get the 5 latest posts for the blog and store them in the $globalquery variable.
		//$globalquery = get_posts('numberposts=5&post_type=any');
		$blog_details = get_blog_details($blog_id);
		$blog_name = $blog_details->blogname;
		$setting_id = "select_blog_" . $blog_id . '_chief_editor';
		$args     = array (
		  'blog_id' => $blog_id,
		  'setting_id' => $setting_id
		);
		
		//log_me('Adding setting for blog '.$blog_name.' id '.$blog_id.' and setting id '.$setting_id);
		$settings[] = array(
                    'id'   => $setting_id,
		  			'name' => $blog_name,
                    'desc' => __( 'Set chief editor(s) for this blog' ),
                    'std'  => 'regular',
		  			//'size' => '50',
                    'type' => 'select',
		  			'callback' => 'ce_blog_chief_editor_callback',
					'blog_id' =>  $blog_id
		);
				
		// Switch back to the main blog
		restore_current_blog();
	  }
	  
	  
	  
        return apply_filters( 'plugin_settings', $settings );
    }
	
	static function getAllCustomPostTypes() {
	 $post_types = array();
	  /*
	  $network_sites = wp_get_sites();	
	  foreach ( $network_sites as $network_site ) {
		
		$blog_id = $network_site['blog_id'];
		
		switch_to_blog($blog_id);
		*/
		$args = array(
		  
		  '_builtin' => false
		);
		
		$output = 'names';
		// names or objects, note names is the default
		$operator = 'and';
		// 'and' or 'or'
		
		$post_types = array_merge($post_types,get_post_types( $args, $output, $operator ));
		
	
	  /*
		// Switch back to the main blog
		restore_current_blog();
	  }
	  
	  */
	  return array_unique($post_types);
	
	}
	
	/**
	* Register and add settings
	*/
	/*
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
	  
	  
	  // -----------------------------------
	  
	  add_settings_section(
		'custom_posts_section_id', // ID
		__('Custom post types','chief-editor'), // Title
		array( $this, 'ce_print_section_custom_post' ), // Callback
		'chief_editor_plugin_options' // Page
	  );
	  
	  $args = array(
		
		'_builtin' => false
	  );
	  
	  $output = 'names';
	  // names or objects, note names is the default
	  $operator = 'and';
	  // 'and' or 'or'
	  
	  $post_types = get_post_types( $args, $output, $operator );
	  
	  
	  foreach ( $post_types as $post_type ) {
		$args     = array (
		  'post_type' => $post_type
		);
		$element_name = 'checkbox_'.$post_type;
		add_settings_field(  
		  $element_name,  
		  $post_type,  
		  array( $this, 'checkbox_element_callback'),  // callback
		  'chief_editor_plugin_options',   // page
		  'custom_posts_section_id',  //section
		  $args
		);
	  }
	  
	  
	  
	  
	  
	  
	  // -----------------------------------
	  
	  
	  
	  add_settings_section(
		'chief_editors_section_id', // ID
		__('Set users as Chief Editors','chief-editor'), // Title
		array( $this, 'ce_print_section_editors_info' ), // Callback
		'chief_editor_plugin_options' // Page
	  );
	  
	  
	  
	  
	  // Iterate through your list of blogs
	  foreach (wp_get_sites() as $blog) {
		//foreach ($blog_ids as $blog_id){
		$blog_id = $blog['blog_id'];
		
		// Switch to the next blog in the loop.
		// This will start at $id == 1 because of your ORDER BY statement.
		switch_to_blog($blog_id);
		
		// Get the 5 latest posts for the blog and store them in the $globalquery variable.
		//$globalquery = get_posts('numberposts=5&post_type=any');
		$blog_details = get_blog_details($blog_id);
		$blog_name = $blog_details->blogname;
		$setting_id = "blog_" . $blog_id . '_chief_editor';
		$args     = array (
		  'blog_id' => $blog_id,
		  'setting_id' => $setting_id
		);
		
		//log_me('Adding setting for blog '.$blog_name.' id '.$blog_id.' and setting id '.$setting_id);
		
		add_settings_field(
		  $setting_id, // ID
		  $blog_name, // Title 
		  array( $this, 'ce_blog_chief_editor_callback' ), // Callback
		  'chief_editor_plugin_options', // Page
		  'chief_editors_section_id', // Section   
		  $args // args
		);
		
		
		// Switch back to the main blog
		restore_current_blog();
	  }
	  
	  
	  
	  $this->options = get_option( 'chief_editor_option' );
	  
	  //print_r($this->options);
	}
	*/
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
	  
	  
	  $args = array(
		/*'public'   => false,*/
		'_builtin' => false
	  );
	  
	  $output = 'names';
	  // names or objects, note names is the default
	  $operator = 'and';
	  // 'and' or 'or'
	  
	  $post_types = get_post_types( $args, $output, $operator );
	  
	  
	  foreach ( $post_types as $post_type ) {
		$element_name = 'checkbox_'.$post_type;
		if( isset( $input[$element_name] ) )
		  $new_input[$element_name] = $input[$element_name];
	  }
	  
	  foreach (wp_get_sites() as $blog) {
		
		$blog_id = $blog['blog_id'];
		//switch_to_blog($blog_id);
		$setting_id = "blog_" . $blog_id . '_chief_editor';
		if( isset( $input[$setting_id] ) ){
		  $new_input[$setting_id] = $input[$setting_id];
		}
	  }
	  
	  
	  log_me($new_input);
	  
	  return $new_input;
	}
	
	/** 
	* Print the Section text
	*/
	public function ce_print_section_info()
	{
	  print __('The following settings are used to send pre-formatted email to post authors, in order for them to validate it online before publishing','chief-editor');
	}
	
	public function ce_print_section_custom_post()
	{
	  print __('This section allow you to select which custom post types are going to presented in a separate tab for scheduling','chief-editor');
	}
	
	public function ce_print_section_editors_info()
	{
	print __('Attribute Chief editors to each blog in order for them to receive','chief-editor').' '.__('all','chief-editor').' '.__('ready for printing','chief-editor').' '.__('notifications','chief-editor');

	}
	
	function checkbox_element_callback(array $args) {
	  
	  $post_type  = $args['post_type'];
	  $options = get_option( 'checkbox_element_callback' );
	  $element_name = 'checkbox_'.$post_type;
	  //if (in_array($element_name,$this->options)) {
	  $checked = checked( 1, $this->options[$element_name], false );
	  /*} else {
	  $checked = '';
	  }*/
	  //log_me('$checked '.$checked);
	  $html = '<input type="checkbox" id="'.$element_name.'" name="chief_editor_option['.$element_name.']" value="1"' . $checked . '/>';
	  $html .= '<label for="'.$element_name.'"></label>';
	  
	  print __( $html,'chief-editor');
	}
	
	function ce_blog_chief_editor_callback(array $args){
	  
	  $blog_id  = $args['blog_id'];
	  $setting_id = $args['setting_id'];
	  log_me( $setting_id);
	  $chief_editors_roles = array('contributor','author','editor');
	  $blogusers = array();
	  
	  
	  foreach ($chief_editors_roles as $role) {
		
		$other_blogusers = get_users( 'blog_id='.$blog_id.'&orderby=nicename&role='.$role );
		if ($other_blogusers){
		  $blogusers = array_merge($blogusers, $other_blogusers);
		}
	  }
	  
	  
	  
	  log_me( count($blogusers) . ' '.count($chief_editors_roles). ' : ');
	  /*
	  echo isset($this->options[$setting_id]) ? $this->options[$setting_id] : 'not set<br/>';
	  $chief_editor_array = $this->options[$setting_id];
	  
	  echo count($chief_editor_array) . '<ul>';
	  foreach ($chief_editor_array as $chief_editor) {  
	  echo '<li>'.$chief_editor.'</li>';
	  }
	  echo '</ul>';
	  */
	  $fieldID = 'chief_editors_selector_'.$blog_id;
	  $fieldName = 'chief_editor_option['.$setting_id.']';
	  
	  
	  
	  printf (
		'<select multiple="multiple" name="%s[]" id="%s" class="widefat" size="5" style="margin-bottom:10px">',
		$fieldName,
		$fieldID
	  );
	  
	  // Each individual option
	  foreach( $blogusers as $user )
	  {
		$id = $user->ID;
		$userEmail = $user->user_email;
		$userLogin = $user->user_login;
		$userNicename = $user->display_name;
		log_me("$id : $userEmail = ");
		$checkedOptions =  $this->options[$setting_id];
		log_me( $checkedOptions);
		
		printf(
		  '<option value="%s" %s style="margin-bottom:3px;">%s</option>',
		  $id,
		  in_array( $id, $checkedOptions) ? 'selected="selected"' : '',
		  $id .' - '.$userLogin.' - '.$userNicename . ' (' . $userEmail .')'
		);
	  }
	  
	  echo '</select>';
	  
	  
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
Voici la previsualisation de votre article pour obtention d\'un Bon A Tirer : <br/>

<h2><a href="%postlink%" target="_blank">%posttitle%</a></h2><br/>

Vous devez etre authentifie avec vos identifiants personnels <a href="%blogurl%">sur le site</a> pour visualiser cet article en ligne:
<ul><li>Utiliser votre login : <strong>%userlogin%</strong></li>
<li>et votre mot de passe (si vous l\'avez oublie, demandez-en un nouveau en cliquant ici : <a href="http://www.termel.com/wp-login.php?action=lostpassword">Service de recuperation de mot de passe</a>)
</ul>
Si le message suivant apparait:<br/>
<em>Desole, mais la page demande ne peut etre trouvee.</em>
c\'est que vous n\'etes pas connecte au site.
<h2>En cas de probleme</h2>Merci de suivre la procedure suivante pour visualiser votre post en ligne:<br/>
<ol><li>Se connecter avec vos identifiants <a href="%blogurl%">sur le site idweblogs</a>.</li>
<li>Verifier que votre nom (ou pseudo) apparait bien en haut a droite de l\'ecran, ce qui confirme votre connexion au site.</li>
<li>Ouvrir un nouvel onglet dans le meme navigateur (Chrome, Firefox, Internet Explorer,etc...).</li>
<li>Copier/coller le lien ci dessus dans ce nouvel onglet et valider.</li>
<li>Votre post doit s\'afficher correctement, en cas de probleme, merci de nous contacter : <a href="mailto:aide@idweblogs.com">aide@idweblogs.com</a></li>
</ol> 
<h2>Merci de preciser</h2> dans votre mail de reponse, si ce n\'est deja fait, les elements suivants:
<ol><li>Vos liens d\'interet eventuels pour ce post</li>
<li>Les mots cles qui permettent d\'indexer au mieux votre post</li>
<li>L\'image de Une du post</li>
<li>La categorie (ou les categories) du blog dans laquelle doit etre publie votre article</li>
<li>Les liens web eventuels a rajouter vers des sites externes ou de la bibliographie</li>
<li>(optionnel) une photo de vous</li>
</ol>

<br/>Cordialement, L\'equipe';
	  
	  printf(
		'<textarea type="text" id="email_content" rows="25" cols="110" name="chief_editor_option[email_content]" value="%s">%s</textarea>',
		isset( $this->options['email_content'] ) ? esc_attr( $this->options['email_content']) : $ce_default_mail_content,
		isset( $this->options['email_content'] ) ? esc_attr( $this->options['email_content']) : $ce_default_mail_content
		
	  );
	  
	}
	
	
	public function create_calendar_table() {
	  // Set up global variables. Great
	  //global $wpdb, $blog_id, $post;
	  $sumsArray = array();
	  
	  // Get a list of blogs in your multisite network
	  //$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	  $weekNumber = date("W");
	  $weeksInPast = 4;
	  $weeksInFuture = 4;
	  $thisWeekColor = "#F5B800";
	  $backgroundColor = "#6B747A";//"#6B6B6B";
	  $lightGreyColor = "#E0E0E0";
	  $startingWeek = max($weekNumber - $weeksInPast,1);
	  $currentYear = date("Y");
	  echo '<table class="sortable" id="calendar_table" style="border:solid #6B6B6B 1px;width:100%;">';
	  //$color_bool = true;
	  $chief_editor_table_header = '<tr style="color:#FFAF30;background-color:'.$backgroundColor.';color:#FFFFFF">';
	  $chief_editor_table_header .= '<td>#</td>';
	  $chief_editor_table_header .= '<td>'.__('Blog','chief-editor').'</td>';
	  for ($week = $startingWeek; $week <= $weekNumber + $weeksInFuture; $week++) {
		
		$sumsArray[$week] = 0;
		$weekArray = $this->getStartAndEndDate($week,$currentYear);
		$color='';
		if ($week == $weekNumber) {
		  $color = $thisWeekColor;
		}
		else {
		  $color = $backgroundColor;
		}
		
		$chief_editor_table_header .= '<td style="background-color:'.$color.';">'.$weekArray['week_start'].' => '.$weekArray['week_end'].'</td>';
	  }
	  $chief_editor_table_header .= '</tr>';
	  
	  echo $chief_editor_table_header;
	  
	  $idx = 0;
	  
	  // Iterate through your list of blogs
	  foreach (wp_get_sites() as $blog) {
		$public = $blog['public'];
		if ($public == 0) {
		  
		  continue;
		}
		$blog_id = $blog['blog_id'];
		
		$idx += 1;
		// Switch to the next blog in the loop.
		// This will start at $id == 1 because of your ORDER BY statement.
		switch_to_blog($blog_id);
		//$posts_of_current_blog = array();
		$posts_of_current_blog = get_posts(array(
		  'numberposts' => -1,
		  'posts_per_page' => -1,
		  'post_type' => 'post',
		  'post_status' => array('publish','future')
		));
		
		// Get the 5 latest posts for the blog and store them in the $globalquery variable.
		//$globalquery = get_posts('numberposts=5&post_type=any');
		$blog_details = get_blog_details($blog_id);
		/*
		if ($this->noPostPublishedBetweenDates($posts_of_current_blog,$startingWeek,$weekNumber + $weeksInFuture)) {
		continue;
		}
		*/
		
		$new_line = '<tr>';
		$new_line .= '<td>'.$idx.'</td>';
		$new_line .= '<td>'.$blog_details->blogname.'</td>';
		
		for ($week = $startingWeek; $week <= $weekNumber + $weeksInFuture; $week++) {
		  
		  $weekArray = $this->getStartAndEndDate($week,$currentYear);
		  $startDate = $weekArray['week_start'];
		  $endDate = $weekArray['week_end'];
		  //echo 'New Week ' . $startDate . ' ' . $endDate;
		  $currentWeekPosts = array();
		  
		  //echo '<ul>';
		  foreach ( $posts_of_current_blog as $new_post ) {
			$format = 'Y-m-d';
			$postDate = get_the_time($format,$new_post->ID);
			$post_date = new DateTime($postDate);
			$start_date = new DateTime($startDate);
			$end_date = new DateTime($endDate);
			
			if ($post_date >= $start_date && $post_date <= $end_date) {
			  
			  $currentWeekPosts[] = $new_post;
			}
		  }
		  
		  
		  $numberOfPosts = count($currentWeekPosts);
		  //echo $numberOfPosts;
		  if ($numberOfPosts) {
			
			if ($week < $weekNumber) {
			  // post published
			  $color = CE_PUBLISHED_COLOR;
			}
			else {
			  $color = CE_SCHEDULED_COLOR;
			}
			
			
			$sumsArray[$week] += $numberOfPosts;
			$new_line .= '<td class="ce_calendar_post_cell" style="background-color:'.$color.';">';
			$new_line .= '<div class="ce_calendar_post_title">';
			$new_line .= '<ol>';
			foreach ($currentWeekPosts as $weekPost) {
			  
			  $permalink = get_blog_permalink( $blog_id, $weekPost->ID );
			  $new_line .= '<li>';
			  $new_line .= '<a title="'.__('published on ','chief-editor').$weekPost->post_date.'" href="'.$permalink.'" target="_blank">'.$weekPost->post_title.'</a>';
			  $new_line .= '</li>';
			  
			}
			$new_line .= '</ol>';
			$new_line .= '</div>';
			
			$new_line .= '</td>';
			
		  }
		  else {
			$new_line .= '<td class="empty-cell"></td>';
		  }
		  
		  
		}
		
		$new_line .= '</tr>';
		echo $new_line;
		
		
		
	  }
	  // Switch back to the main blog
	  restore_current_blog();
	  
	  $last_line = '<tr>';
	  $last_line .= '<td></td>';
	  $last_line .= '<td>Total:</td>';
	  for ($week = $startingWeek; $week <= $weekNumber + $weeksInFuture; $week++) {
		
		
		$last_line .= '<td class="table_footer">'.$sumsArray[$week].'</td>';
	  }
	  $last_line .= '</tr>';
	  
	  echo $last_line;
	  echo '</table>';
	  
	}
	
	function noPostPublishedBetweenDates($posts,$startW,$endW) {
	  //echo "count($posts) posts between $startW and $endW";
	  $currentYear = date("Y");
	  $weekArray1 = $this->getStartAndEndDate($startW,$currentYear);
	  $startDate = $weekArray['week_start'];
	  //$endDate = $weekArray['week_end'];
	  
	  $weekArray1 = $this->getStartAndEndDate($endW,$currentYear);
	  //$startDate = $weekArray['week_start'];
	  $endDate = $weekArray['week_end'];
	  
	  foreach ($posts as $post) {
		//if ($post->post_date)
		$format = 'Y-m-d';
		$postDate = get_the_time($format,$post->ID);
		$post_date = new DateTime($postDate);
		$start_date = new DateTime($startDate);
		$end_date = new DateTime($endDate);
		
		if ($post_date >= $start_date && $post_date <= $end_date) {
		  
		  return 1;
		}
	  }
	  
	  return 0;
	  
	}
	
	function getStartAndEndDate($week, $year) {
	  $dto = new DateTime();
	  $dto->setISODate($year, $week);
	  $ret['week_start'] = $dto->format('Y-m-d');
	  $dto->modify('+6 days');
	  $ret['week_end'] = $dto->format('Y-m-d');
	  return $ret;
	}
	
	
	public function get_all_writers_over_network() {
	  // Set up global variables. Great
	  //global $wpdb;//, $blog_id, $post;
	  
	  // Get a list of blogs in your multisite network
	  //$blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY $s",'blog_id' ) );
	  
	  $network_sites = wp_get_sites();
	  log_me('Network has '.count($network_sites).' blog(s)');
	  
	  $globalcontainer = array();
	  //foreach( $blogs as $blog ) {
	   foreach( $network_sites as $blog ) {
		$blog_id = $blog['blog_id'];
		log_me($blog_id .' -> '.$blog['path']);
		switch_to_blog( $blog_id );
		 
		//switch_to_blog( $blog->blog_id );
		$globalquery = array_merge (get_users('role=contributor'),get_users('role=author'),get_users('role=editor'));//get_posts( 'numberposts=5&post_type=any' );
		$globalcontainer = array_merge( $globalcontainer, $globalquery );
		
		restore_current_blog();
	  }
	  
	  return $globalcontainer;
	}
	
	public function ched_custom_author_stats($period, $start_date, $end_date) {
	  //global $wpdb,$blog_id, $post;
	  
	  $table_class = "border:solid #6B6B6B 1px;width:100%;";
	  $border_class = "border:solid #6B6B6B 1px;";
	  
	  echo '<form>';
	  echo '<INPUT type="button" value="'.__('Trace graph for sorted column','chief-editor').'" name="traceGraphButton" onClick="traceGraph();">';
	  echo '</FORM>';
	  echo '<table class="sortable" id="authorTable" style="border:solid #6B6B6B 1px;width:100%;">';
	  $color_bool = true;
	  $chief_editor_table_header = '<tr style="background-color:#6B6B6B;color:#FFFFFF">';
	  $chief_editor_table_header .= '<td>'.__('Nom','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Prnom','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Blog','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Posts','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Commentaires','chief-editor').'</td>';
	  $chief_editor_table_header .= '</tr>';
	  	  
	  // Get a list of blogs in your multisite network
	  //$blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY %d",$blog_id ) );
	  $network_sites = wp_get_sites();
	  
	  log_me('Network has '.count($network_sites).' blog(s)');
	  
	  echo $chief_editor_table_header;
	  $globalcontainer = array();
	  
	  $totalNbOfPosts = 0;
	  $totalNbOfComments = 0;
	  
	  foreach( $network_sites as $blog ) {
		
		$blog_id = $blog['blog_id'];
		log_me($blog_id .' -> '.$blog['path']);
		switch_to_blog( $blog_id );
		
		$blog_name = get_bloginfo('name');
		$blog_title = get_bloginfo('title');
		$blog_wpurl = get_bloginfo('wpurl');
		$users = array_merge (get_users('role=administrator'),get_users('role=contributor'),get_users('role=author'),get_users('role=editor'));//get_posts( 'numberposts=5&post_type=any' );
		
		//echo '<tr>';
		//echo '<p>Users : '.count($users).'</p>';
		log_me(count($users).' users on blog '.$blog_title );
		foreach ($users as $author) {
		  
		  $user_role = $author->role;
		  if ($user_role == 'subscriber') {
			continue;
		  }
		  
		  $author_stats = $this->bm_get_stats($period, $author->ID, $start_date,$end_date);
		  if ($author_stats['posts'] == 0) {
			continue;
		  }
		  $line_color = ($color_bool?'#FFFFFF':'#EDEDED');
		  $newLine = '<tr style="border:solid #6B6B6B 1px;background-color:'.$line_color.'">';
		  
		  $user_info = get_userdata($author->ID);
		  $userlogin = $user_info->user_login;
		  $userLastname = $user_info->last_name;
		  $userFirstname = $user_info->first_name;
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
		  
		  $cat_posts = $author_stats['categories'];
		  
		  
		  $newLine .= '<td>'.$userLastname.'</td>';
		  $newLine .= '<td>'.$userFirstname.'</td>';
		  $newLine .= '<td>'.$blog_name.'</td>';
		  $newLine .= '<td>';
		  $totalNbOfPosts += $author_stats['posts'];
		  $newLine .= '<ul><li><b>Total : '.$author_stats['posts'].'</b>';
		  foreach ($cat_posts as $key => $value){
			$newLine .= '<li>'.$key.' : '.$value.'</li>';
		  
		  }
		  $newLine .= '</ul>';
		  $newLine .= '</li></ul>';
		  $newLine .= '</td>';
		  $newLine .= '<td>'.$author_stats['comments'].'</td>';
			$totalNbOfComments += $author_stats['comments'];
		  $newLine .= '</tr>';
		  
		  echo $newLine;
		  $color_bool = !$color_bool;
		}
		
		
		restore_current_blog();
	  }
	  
	  echo '<tr><td>Total:</td><td></td><td></td><td>'.$totalNbOfPosts.'</td><td>'.$totalNbOfComments.'</td></tr>';
	  echo '</table>';
	  echo '<form>';
	  echo '<INPUT type="button" value="'.__('Trace graph for sorted column','chief-editor').'" name="traceGraphButton" onClick="traceGraph();">';
	  echo '</FORM>';
	  echo '<hr>';
	  echo '<div style="text-align:center;"><canvas id="graphCanvas" height="600" width="1000"></canvas><br><br><canvas id="pieGraphCanvas" height="600" width="1000"></div>';
	  
	}
	
	
	public function bm_author_stats($period) {
	  //global $wpdb;
	  
	  $table_class = "border:solid #6B6B6B 1px;width:100%;";
	  $border_class = "border:solid #6B6B6B 1px;";
	  
	  echo '<form>';
	  echo '<INPUT type="button" value="'.__('Trace graph for sorted column','chief-editor').'" name="traceGraphButton" onClick="traceGraph();">';
	  echo '</FORM>';
	  echo '<table class="sortable" id="authorTable" style="border:solid #6B6B6B 1px;width:100%;">';
	  $color_bool = true;
	  $chief_editor_table_header = '<tr style="background-color:#6B6B6B;color:#FFFFFF"><td>Blog</td>';
	  $chief_editor_table_header .= '<td>'.__('Name','chief-editor').'</td><td>'.__('login','chief-editor').'</td><td>'.__('Month blogging','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Posts','chief-editor').'</td><td>'.__('Posts/month','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Words/post','chief-editor').'</td><td>'.__('Comments','chief-editor').'</td>';
	  $chief_editor_table_header .= '<td>'.__('Comments/post','chief-editor').'</td><td>'.__('Words/comment','chief-editor').'</td><td>'.__('Comments/month','chief-editor').'</td></tr>';
	  
	  
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
	  
	  //global $wpdb, $blog_id, $post;
	  
	  // Get a list of blogs in your multisite network
	  //$blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY %d",$blog_id ) );
	  $network_sites = wp_get_sites();
	  log_me('Network has '.count($network_sites).' blog(s)');
	  
	  $globalcontainer = array();
	  foreach( $network_sites as $blog ) {
		$blog_id = $blog['blog_id'];
		switch_to_blog( $blog_id );
		
		//switch_to_blog( $blog->blog_id );
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
	
	function get_per_category_stats($author_id,$startDate,$endDate) {
	
	  $result = array();
	  /*if ($startDate != NULL && $endDate != NULL){
	  $start_date = $startDate->format('Y-m-d');
	  $end_date = $endDate->format('Y-m-d');
	  }*/
	  /*
	  $all_posts = get_posts(array (
		'posts_per_page' => -1,
		'post_status'	=> 'publish',
		'author' => $author_id,
	  'date_query' => array(
								 array(
								   'after'     => array(
									 'year' => $startDate->format('Y'),
									 'month' => $startDate->format('m'),
									 'day' => $startDate->format('d'),
								   ),
								   'before'    => array(
									 'year'  => $endDate->format('Y'),
									 'month' => $endDate->format('m'),
									 'day'   => $endDate->format('d'),
								   ),
								   'inclusive' => true,
								   ),
							  ),
	  ));
	  
	  $result['all'] = count($all_posts);*/
	  
	  
	  $args = array(
	'type'                     => 'post',
	'child_of'                 => 0,
	'parent'                   => '',
	'orderby'                  => 'name',
	'order'                    => 'ASC',
	'hide_empty'               => 1,
	'hierarchical'             => 1,
	'exclude'                  => '',
	'include'                  => '',
	'number'                   => '',
	'taxonomy'                 => 'category',
	'pad_counts'               => false,
		
	); 
	  
	  $categories = get_categories( $args );
	  
	  foreach ($categories as $category) {		
		
		$cat_slug = $category->slug;		
		if ($startDate != NULL && $endDate != NULL) {

		$argsForQuery = array ('category_name' => $cat_slug,
							   'author' => $author_id,
							   'post_status'	=> 'publish',
							   'posts_per_page' => -1,
							   'date_query' => array(
								 array(
								   'after'     => array(
									 'year' => $startDate->format('Y'),
									 'month' => $startDate->format('m'),
									 'day' => $startDate->format('d'),
								   ),
								   'before'    => array(
									 'year'  => $endDate->format('Y'),
									 'month' => $endDate->format('m'),
									 'day'   => $endDate->format('d'),
								   ),
								   'inclusive' => true,
								   ),
							  )
							  );
		} else {
			$argsForQuery = array('category_name' => $cat_slug,
								  'post_status'	=> 'publish',
								  'posts_per_page' => -1,
							   			 'author' => $author_id,);
		}
		//log_me($argsForQuery);
		$queried_posts = get_posts($argsForQuery );
		
		$nb_of_posts = count($queried_posts);
		if ($nb_of_posts) {
		  
		  $result[$cat_slug] = $nb_of_posts;
	  		
		  foreach($queried_posts as $post){
			log_me($cat_slug.' => '.$nb_of_posts .' '.$post->post_title);
		  }
		}
	  }
	  
	  return $result;
	
	}
	
	
	
	function bm_get_stats($period="alltime", $authorid=0, $startDate = NULL, $endDate = NULL) {
	  global $wpdb;
	  $options = get_option('BlogMetricsOptions');
	  
	  $periodquery = "";
	  $authorquery = "";
	  	  
	  
	  if ($startDate != NULL) {
		
		$start_date = $startDate->format('Y-m-d');
		if ($endDate != NULL) {
		  $end_date = $endDate->format('Y-m-d');
		}
		else {
		  $end_date = date("Y-m-d");
		}
		
		if (!empty($start_date) && !empty($end_date)) {
			$periodquery = " AND p.post_date BETWEEN '$start_date' AND '$end_date'";
			  /*" AND p.post_date >= '{$start_date}'
			  AND p.post_date < '{$end_date}'";*/
		  }
			
	  } else if ($period == "month") {
		$periodquery = " AND p.post_date > date_sub(now(),interval 1 month)";
	  }
	  
	  if ($authorid != 0) {
		$authorquery = " AND p.post_author = $authorid";
	  }
	  
	  $authorsquery = "SELECT COUNT(DISTINCT post_author) FROM $wpdb->posts AS p WHERE p.post_type = 'post'".$periodquery;
	  
	  // Override query if an authorid is set, to return display name for author
	  if ($authorid != 0) {
		$authorsquery = "SELECT u.display_name FROM $wpdb->users AS u WHERE u.ID = $authorid";
	  }
	  
	  $postsquery = "SELECT COUNT(ID) FROM $wpdb->posts AS p WHERE p.post_type = 'post' AND p.post_status = 'publish'".$periodquery.$authorquery;
	  
	  $args = array ('post_status' => 'publish',
		'author' => $authorid,
		'posts_per_page' => -1,);
	  if ($startDate != NULL && $endDate != NULL){
	  	$args['date_query'] = array(
								 array(
								   'after'     => array(
									 'year' => $startDate->format('Y'),
									 'month' => $startDate->format('m'),
									 'day' => $startDate->format('d'),
								   ),
								   'before'    => array(
									 'year'  => $endDate->format('Y'),
									 'month' => $endDate->format('m'),
									 'day'   => $endDate->format('d'),
								   ),
								   'inclusive' => true,
								   ),
							  );
	  }
	  //echo $args;
	  $all_posts_for_user_on_blog = get_posts($args);
	  
	  //$result['all'] = count($all_posts);
	  
	  //echo $postsquery;
	  
	  $firstpostquery = "SELECT p.post_date FROM $wpdb->posts AS p WHERE p.post_status = 'publish'$authorquery ORDER BY p.post_date LIMIT 1";
	  
	  $commentfromwhere = "FROM $wpdb->comments AS c, $wpdb->posts AS p, $wpdb->users AS u "
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
	  
	  $stats['authors'] 	= $wpdb->get_var($authorsquery);
	  $stats['posts'] 		= count($all_posts_for_user_on_blog); //$wpdb->get_var($postsquery);
	  if ($startDate != NULL) {
	  	$stats['categories'] = $this->get_per_category_stats($authorid, $startDate, $endDate);
	  }
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
	
	
	
	
	private function findBlogIdFromPostId($postId, $postTable){
	  
	  foreach ($postTable as $key => $value) {
		if (in_array($postId,$value)) {
		  return $key;
		}
	  }
	}
	
	public function recent_mu_posts( $post_type = 'post', $howMany = 10 ) {
	  
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
		
		log_me('!is_multisite :: count($rows) '.count($rows));
		
	  }
	  else {
		
		//$rows = $this->get_all_pending_posts_multisite();
		
		$resultsArray = $this->getAllPostsOfAllBlogsOfType($post_type);
		
		$rows = $resultsArray[0];
		$resultTable = $resultsArray[1];
		
		log_me('MULTISITE :: count($rows) '.count($rows));
		
	  }
	  // now we need to get each of our posts into an array and return them
	  if ( $rows ) {
		$nb_of_scheduled = 0;
		$nb_of_drafts = 0;
		$nb_of_pending = 0;
		$futureColor = $this->get_post_color_from_status('future');//'#A4F2FF';
		$draftColor = $this->get_post_color_from_status('draft');//'#EDEDED';
		$pendingColor = $this->get_post_color_from_status('pending');//'#9CFFA1';
		$tableHeaderColor = "#6B747A";
		//echo '<hr>';
		//echo '<h2>Posts</h2>';
		//echo '<h4>Total non published post(s) found : '. count($rows).'</h4>';
		echo '<br/>';
		$chief_editor_table_header = '<table class="sortable" style="border:solid #6B6B6B 1px;width:100%;">';
		$chief_editor_table_header .= '<tr style="color:#FFAF30;background-color:'.$tableHeaderColor.';">';
		$chief_editor_table_header .= '<td>#</td><td>' . __('Blog Title','chief-editor') . '</td><td>' . __('Featured image','chief-editor') . '</td>';
		$chief_editor_table_header .= '<td>Post</td><td>'.__('Submission date','chief-editor').'</td><td>'.__('Status','chief-editor').'</td>';
		//$chief_editor_table_header .= '<td>'.__('Excerpt','chief-editor').'</td>';
		$chief_editor_table_header .= '<td>'.__('Author (login)','chief-editor').'</td>';
		$chief_editor_table_header .= '<td style="min-width: 100px;">'.__('Scheduled for date','chief-editor').'</td></tr>';
		echo $chief_editor_table_header;
		$posts = array();
		$countIdx = 0;
		foreach ( $rows as $row ) {
		  $countIdx++;
		  $data = $row->ID;
		  
		  if ( is_multisite() ) {
			$blog_id = $this->findBlogIdFromPostId($data,$resultTable);
			$current_blog_details = get_blog_details( $blog_id );
			$blog_path = $current_blog_details->path;
			$blog_name = $current_blog_details->blogname;
			$permalink = get_blog_permalink( $blog_id, $data );
			log_me('Find post '.$data.' on blog '.$blog_id);
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
		  log_me($post_id .' : '.$title);
		  $post_thumbnail = '';
		  $post_thumbnail .= '<a class="ce_post_thumbnail" target="_blank" href="' . $permalink . '" title="' . esc_attr( $title) . '">';
		  //$post_thumbnail .= '<img src="'.$this->multisite_get_thumb($post_id,100,100,$blog_id,true,true).'"/>';
		  if ( is_multisite() ) {
			$post_thumbnail .= $this->get_the_post_thumbnail_by_blog($blog_id,$post_id,array(100,100));
		  }
		  else {
			$post_thumbnail .= get_the_post_thumbnail( $post_id, array(100,100));
		  }
		  $post_thumbnail .=  '</a>';
		  $abstract = $new_post->post_excerpt;
		  $author = $new_post->post_author;
		  
		  
		  $user_info = $this->get_userdata_for_blog($author,$blog_id);//get_userdata($author);
		  //log_me( $user_info);
		  $userlogin = $user_info->user_login;
		  $userdisplayname = $user_info->display_name;
		  
		  $date_format = 'l, jS F Y';
		  $creation_date = get_the_time( $date_format, $new_post );
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
		  
		  // current_user_can('delete_others_pages')
		  
		  $complete_new_table_line .= '<td><span style="font-size:16px;"><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$title.'</a></span>';
		  if (current_user_can('delete_others_pages')) {
			$complete_new_table_line .= ' (<a href="'.$edit_post_link.'" target="_blank">'.__('Edit').'</a>)';
		  }
		  $complete_new_table_line .= '</td>';
		  $complete_new_table_line .= '<td>'.$creation_date.'</td>';
		  $status_image = CHIEF_EDITOR_PLUGIN_URL . '/images/'.$post_state.'.png';
		  $status_meaning = $this->get_post_status_meaning_from_status($post_state);
		  $complete_new_table_line .= '<td>'.$status_meaning.'<br/><img src="'.$status_image.'"/></td>';
		  //$complete_new_table_line .= '<td>'.$abstract.'</td>';
		  $complete_new_table_line .= '<td>'.$userdisplayname.' ('.$userlogin.')';
		  if (current_user_can('delete_others_pages')) {
			$complete_new_table_line .= '<div class="wrap"><form id="'.$post_id.'_chief-editor-bat-form" class="chief-editor-bat-form" action="" method="POST">';
			$complete_new_table_line .= '<div><input type="submit" id="'.$post_id.'_chief-editor-bat-submit" name="chief-editor-bat-submit" class="chief-editor-bat-submit button-primary" value="'.__('Send BAT to author','chief-editor').'"/>';
			$complete_new_table_line .= '<input type="hidden" id="postID" name="postID" value="'.$post_id.'">';
			$complete_new_table_line .= '<input type="hidden" id="blogID" name="blogID" value="'.$blog_id.'">';
			$complete_new_table_line .= '<input type="hidden" id="authorID" name="authorID" value="'.$author.'">';
			$complete_new_table_line .= '</div></form><div id="ce_dialog_email" class="ce_dialog_email" title="Dialog Title" style="display:none">Some text</div></div>';
		  }
		  $complete_new_table_line .= '</td>';
		  
		  if ($post_state == 'future') {
			$complete_new_table_line .= '<td><h3 style="color:#002EB8">' . date_i18n( get_option( 'date_format' ) , strtotime( $date ) ) . '</h3>';
			$complete_new_table_line .= '<h4 style="color:#B8008A">'.date_i18n( 'G:i', strtotime( $date ) ).'</h4></td>';
		  }
		  else {
			$complete_new_table_line .= '<td>'.__('not scheduled','chief-editor').'</td>';
		  }
		  
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
	  $futureColor = CE_SCHEDULED_COLOR;
	  $draftColor = CE_DRAFT_COLOR;
	  $pendingColor = CE_INPRESS_COLOR;
	  $pitchColor = CE_NEW_COLOR;
	  $assignedColor = CE_ASSIGNED_COLOR;//'#FFADFB';
	  $inProgressColor = CE_INPRESS_SENT_COLOR;//'#f3f5b1';
	  $BATColor = CE_INPRESS_COLOR;//'#69D947';
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
	
	function getAllPostsOfAllBlogsOfType($post_type = 'post', $startDate = NULL, $endDate = NULL) {
	  global  $ordered_statuses_array;
	  $network_sites = wp_get_sites();
	  $resultTable = array();
	  $result = array();
	  log_me('Network has '.count($network_sites).' blog(s)');
	  
	  foreach ( $network_sites as $network_site ) {
		
		$blog_id = $network_site['blog_id'];
		
		switch_to_blog($blog_id);
		
		log_me('get_posts of type ' . $post_type . ' on blog '.$blog_id);
		
		$allPostsOfCurrentBlog = get_posts(array(
		  'numberposts' => -1, 
		  'post_type' => $post_type,
		  'post_status' =>  $ordered_statuses_array,
		  'posts_per_page' => -1,
		));
		
		// (post_status != 'publish' AND post_status != 'inherit' AND post_status != 'auto-draft' AND post_status != 'trash') AND post_type = 'post'
		// ORDER BY post_status='pitch',post_status='assigned',post_status='draft',post_status='in-progress',post_status='pending',post_status='future', post_date DESC";
		
		/*
		if ($startDate != NULL && $endDate != NULL) {
		
		foreach ($allPostsOfCurrentBlog as $post) {
		if ($this->postBetweenDates($post,$startDate,$endDate)) {
		$result[$blog_id][] = $post;
		} 
		}
		} else {
		$result[$blog_id] = $allPostsOfCurrentBlog;
		}
		*/
		foreach ($allPostsOfCurrentBlog as $post) {
		  log_me($post->ID . ' : '.$post->post_title);
		  $resultTable[$blog_id][] = $post->ID;
		}
		
		
		log_me($post_type . ' Before merge '.count($result));
		$result = array_merge($result,$allPostsOfCurrentBlog);
		log_me($post_type . ' After merge '.count($result));
		
		// Switch back to the main blog
		restore_current_blog();
	  }
	  
	  //$this->sort_posts($result,'post_status','ASC',false);
	  
	  usort($result, array($this,'status_cmp')); 
	  
	  return array($result,$resultTable);
	}
	
	//custom function for comparing the data we want to sort by
	function status_cmp($a, $b){
	  global $ordered_statuses_array;
	  if ($a->post_status == $b->post_status) {
		$a_schedule_date = strtotime($a->post_date);
		$b_schedule_date = strtotime($b->post_date);
		
		
		return ( $a_schedule_date < $b_schedule_date) ? 1 : -1;
	  }
	  
	  $a_key = array_search ($a->post_status, $ordered_statuses_array);
	  $b_key = array_search ($b->post_status, $ordered_statuses_array);
	  return ( $a_key > $b_key) ? 1 : -1;
	}
	
	
	
	function sort_posts( $posts, $orderby, $order = 'ASC', $unique = true ) {
	  if ( ! is_array( $posts ) ) {
		return false;
	  }
	  
	  usort( $posts, array( new Sort_Posts( $orderby, $order ), 'sort' ) );
	  
	  // use post ids as the array keys
	  if ( $unique && count( $posts ) ) {
		$posts = array_combine( wp_list_pluck( $posts, 'ID' ), $posts );
	  }
	  
	  return $posts;
	}
	
	
	
	function get_all_pending_posts_multisite($post_type = 'post') {
	  
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
	  foreach ( $network_sites as $network_site ) {
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
	  }
	  
	  
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
WHERE comment_date >= '{$start_date}'
AND comment_date < '{$end_date}'
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
WHERE comment_date >= '{$start_date}'
AND comment_date < '{$end_date}'
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
	  }
	  else {
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
	
	
	function get_userdata_for_blog($author,$blog_id) {
	  switch_to_blog($blog_id);
	  
	  $result = get_userdata($author);
	  
	  restore_current_blog();
	  return $result;
	  
	}
	
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
?>
