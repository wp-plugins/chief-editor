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

    } elseif ( $status == 'future' ) {
		
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
	  
    } elseif ('edit' == $operation) {
	  
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
	  private $general_settings_key = 'chief_editor_posts_tab';
    private $advanced_settings_key = 'chief_editor_comments_tab';
	  private $stats_key = 'chief_editor_stats_tab';
    private $chief_editor_options_key = 'chief_editor_plugin_options';
	  //private $plugin_settings_tabs = array();
	  	private $chief_editor_settings_tabs = array();
    	/**
     	* Start up
     	*/
    	public function __construct()
    	{
		  /*
		  add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        	add_action( 'admin_init', array( $this, 'page_init' ) );
			*/
		  
		  add_action( 'init', array( &$this, 'load_settings' ) );
    add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
    add_action( 'admin_init', array( &$this, 'register_advanced_settings' ) );
		  add_action( 'admin_init', array (&$this, 'register_stats_tab'));
    add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
		  
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
    	$this->chief_editor_settings_tabs[$this->general_settings_key] = 'Posts';
	  /*
	  register_setting( $this->general_settings_key, $this->general_settings_key );
	  add_settings_section( 'section_general', 'General Plugin Settings', array( &$this, 'section_general_desc' ), $this->general_settings_key );
    	add_settings_field( 'general_option', 'A General Option', array( &$this, 'field_general_option' ), $this->general_settings_key, 'section_general' );
		*/
	  
	}
	  
	  function section_general_desc() { echo 'General section description goes here.'; }
	  
	function field_general_option() {
    	?>
    	<input type="text" name="<?php echo $this->general_settings_key; ?>[general_option]" value="<?php echo esc_attr( $this->general_settings['general_option'] ); ?>" />
    	<?php
	}

	function register_advanced_settings() {
	  
    	$this->chief_editor_settings_tabs[$this->advanced_settings_key] = 'Comments';
	  /*
    	register_setting( $this->advanced_settings_key, $this->advanced_settings_key );
    	add_settings_section( 'section_advanced', 'Advanced Plugin Settings', array( &$this, 'section_advanced_desc' ), $this->advanced_settings_key );
    	add_settings_field( 'advanced_option', 'An Advanced Option', array( &$this, 'field_advanced_option' ), $this->advanced_settings_key, 'section_advanced' );
		*/
	}
	  
	  function register_stats_tab() {
	  	$this->chief_editor_settings_tabs[$this->stats_key] = 'Authors';
	  }

function section_advanced_desc() { echo 'Advanced section description goes here.'; }

function field_advanced_option() {
    ?>
    <input type="text" name="<?php echo $this->advanced_settings_key; ?>[advanced_option]" value="<?php echo esc_attr( $this->advanced_settings['advanced_option'] ); ?>" />
    <?php
}
	  
	  
	  function add_admin_menus() {
    		add_options_page( 'Chief Editor Settings', 'Chief Editor', 'delete_others_pages', $this->chief_editor_options_key, array( &$this, 'plugin_options_page' ) );
}
	  
	  
	  function plugin_options_page() {
    $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
    ?>
    <div class="wrap">
        <?php $this->plugin_options_tabs(); ?>
        <form method="post" action="options.php">
            <?php wp_nonce_field( 'update-options' ); ?>
            <?php settings_fields( $tab ); ?>
            <?php do_settings_sections( $tab ); ?>
		  <?php /*submit_button();*/ ?>
        </form>
    </div>
    <?php
}
	  
	  function plugin_options_tabs() {
		
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
		//screen_icon();
		echo '<div style="text-align:center;padding:5px;">';
		echo screen_icon() . '<h1>Chief Editor</h1>';
      	echo '<a class="button-primary" href="http://wordpress.org/plugins/chief-editor/" target="_blank">Visit Plugin Site</a>  <a  class="button-primary" style="color:#FFF600;" href="http://wordpress.org/support/view/plugin-reviews/chief-editor" target="_blank">Rate!</a>';
		//echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
		echo '</div> ';
    	echo '<h2 class="nav-tab-wrapper">';
    	foreach ( $this->chief_editor_settings_tabs as $tab_key => $tab_caption ) {
        	$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
        	echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->chief_editor_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
    	}
    	echo '</h2>';
		  
		if ($current_tab == 'chief_editor_posts_tab') {
		  
			$this->recent_mu_posts();
		  
		} elseif ($current_tab == 'chief_editor_comments_tab') {
		  
		  //$this->recent_multisite_comments();
		  global $wpdb;
		  $last_month = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
		$start_date = date('Y-m-d H:i:s', $last_month );
		$end_date = date('Y-m-d H:i:s');
		  $intro_text = 'All comments accross the network since '.$start_date.'<br/>';
		  
		  if ( is_multisite() ) {
		 $allComments = $this->getAllCommentsMultisite('1000',$start_date,$end_date);
		  } else {
			$selects = "SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content FROM wp_comments
      			WHERE comment_date >= '{$start_date}'
					AND comment_date < '{$end_date}'
      			ORDER BY comment_date_gmt DESC LIMIT 1000"; // real number is (number * # of blogs)
			$allComments =  $wpdb->get_results($selects);

		  }
		  
		  
		  echo $intro_text . ' ' . count($allComments). ' item(s)';
		 echo $this->formatCommentsFromArray($allComments);
		  // $merged = 
		  	//echo $this->formatCommentsFromArray($merged);
		} elseif ($current_tab == 'chief_editor_stats_tab') {
		  	$this->bm_author_stats("alltime");
		}
		
		echo '<div style="text-align:right;">';
		// echo '<a class="button-primary" href="http://wordpress.org/plugins/chief-editor/" target="_blank">Visit Plugin Site</a>  <a  class="button-primary" style="color:#FFF600;" href="http://wordpress.org/support/view/plugin-reviews/chief-editor" target="_blank">Rate This Plugin</a>';
		echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
		echo '</div> ';
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
    echo '<INPUT type="button" value="Trace graph for sorted column" name="traceGraphButton" onClick="traceGraph();">';
	echo '</FORM>';
		echo '<table class="sortable" id="authorTable" style="border:solid #6B6B6B 1px;width:100%;">';
		$color_bool = true;
		echo '<tr style="background-color:#6B6B6B;color:#FFFFFF"><td>Blog</td><td>Name</td><td>login</td><td>Month blogging</td><td>Posts</td><td>Posts/month</td><td>Words/post</td><td>Comments</td><td>Comments/post</td><td>Words/comment</td><td>Comments/month</td></tr>';
	
		/*
		$authorquery = "SELECT DISTINCT p.post_author, count(ID) AS posts FROM $wpdb->posts p WHERE p.post_type = 'post'";
	if ($period == "month") {
		$authorquery .= " AND p.post_date > date_sub(now(),interval 1 month)";
	}
	$authorquery .= "  GROUP BY p.post_author ORDER BY posts DESC";
	
	$authors = $wpdb->get_results($authorquery);*/
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
    echo '<INPUT type="button" value="Trace graph for sorted column" name="traceGraphButton" onClick="traceGraph();">';
	echo '</FORM>';
	echo '<hr>';
	echo '<div style="text-align:center;"><canvas id="graphCanvas" height="600" width="1000"></canvas><br><br><canvas id="pieGraphCanvas" height="600" width="1000"></div>';
	
}
	  
	  public function bm_print_stats($stats) {
		
		//$options = get_option('BlogMetricsOptions');
		
		$option['fullstats'] = 1;
	
	if ($stats['period'] == "alltime") {
		$per = "per";
	} else if ($stats['period'] == "month") {
		$per = "this";
	}
	echo '<td style="vertical-align:text-top;width:220px;">';
	if ( !is_numeric($stats['authors']) ) {
		echo '<h3>'.$stats['authors'].'</h3>';
	}
	echo '<h4 style="margin-bottom:2px;">Raw Author Contribution</h4>';
	
	if ($stats['avgposts'] == 1) {
		echo $stats['avgposts']." post $per month<br/>\n";
	} else {
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
	} else if ($period == "month") {
		$stats['bloggingmonths']	= 1;
	}
	if ($stats['posts'] > 0) {
		$stats['avgposts'] 		= round($stats['posts'] / $stats['bloggingmonths'],1);
	}
	
	if ($stats['comments'] > 0 && $stats['posts'] > 0) {
		$stats['avgcomments'] = round(($stats['comments'] / $stats['posts']),1);
	} else {
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
	} else {
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
		} else {
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
		for ($i=0; $i<count($results); $i++) {
			$sum = str_word_count($results[$i]);
			if ($avg == 0) {
				$result += $sum;
			} else {
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
		
	
	$this->recent_mu_posts();
?>
        </div>
        <?php
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
    AND ($wpdb->posts.post_status = 'draft' OR $wpdb->posts.post_status = 'pending' OR $wpdb->posts.post_status = 'pitch' OR $wpdb->posts.post_status = 'future')
    AND $wpdb->posts.post_type = 'post'
    ORDER BY $wpdb->posts.post_status DESC, $wpdb->posts.post_date DESC
 ";

 		$rows = $wpdb->get_results($querystr, OBJECT);
		
		//echo 'count($rows) '.count($rows);
		
	  } else {
		
		$rows = $this->get_all_pending_posts_multisite();
		//echo 'MULTISITE :: count($rows) '.count($rows);
		
	  }
      // now we need to get each of our posts into an array and return them
	  if ( $rows ) {
	  	$nb_of_scheduled = 0;
	  	$nb_of_drafts = 0;
	  	$nb_of_pending = 0;
	  	$futureColor = '#A4F2FF';
	  	$draftColor = '#EDEDED';
	  	$pendingColor = '#9CFFA1';
	  	$tableHeaderColor = "#6B6B6B";
	  	//echo '<hr>';
	  	//echo '<h2>Posts</h2>';
	  	//echo '<h4>Total non published post(s) found : '. count($rows).'</h4>';
	  	echo '<br/>';
	  	echo '<table class="sortable" style="border:solid #6B6B6B 1px;width:100%;"><tr style="background-color:'.$tableHeaderColor.';color:#FFFFFF">';
	  	echo '<td>Blog Title</td><td>Featured image</td><td>Post</td><td>Status</td><td>Excerpt</td><td>Author (login)</td>';
	  	echo '<td>Scheduled for date</td>';//<td>Change scheduling</td></tr>';
        $posts = array();
		foreach ( $rows as $row ) {
			
			$data = $row->ID;      
	   		if ( is_multisite() ) {
				$blog_id = $row->blog_id;
				$current_blog_details = get_blog_details( $blog_id );
	  			$blog_path = $current_blog_details->path;
				$blog_name = $current_blog_details->blogname;
		 		$permalink = get_blog_permalink( $blog_id, $data );
		 		$new_post = get_blog_post( $blog_id, $data );
	   		} else {
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
			} else {
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
	  		$complete_new_table_line .= '<td><a href="'.$blog_path.'" target="_blank"><h4>'.$blog_name.'</h4></a></td>';
	  		$complete_new_table_line .= '<td>'.$post_thumbnail.'</td>';
	  		$edit_post_link = '';
	  		if ( is_multisite() ) {
	  			$edit_post_link .= $this->get_multisite_post_edit_link($blog_id ,$post_id);
	  		} else {
	  			$edit_post_link .= get_edit_post_link( $post_id);
	  		}
	  		//echo 'WOW'.$edit_post_link;
	  		$complete_new_table_line .= '<td><span style="font-size:16px;"><a href="'.$permalink.'" target="blank_" title="'.$title.'">'.$title.'</a></span> (<a href="'.$edit_post_link.'" target="_blank">Edit</a>)</td>';
	  		$status_image = CHIEF_EDITOR_PLUGIN_URL . '/images/'.$post_state.'.png';
	  		$complete_new_table_line .= '<td><img src="'.$status_image.'"/></td>';
			$complete_new_table_line .= '<td>'.$abstract.'</td><td>'.$userdisplayname.' ('.$userlogin.')</td>';
	  
	  		if ($post_state == 'future') {
				$complete_new_table_line .= '<td><h3>' . $date . '</h3></td>';
	  		} else {
				$complete_new_table_line .= '<td>not scheduled</td>';
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
	  echo '<tr style="background-color:'.$futureColor.';"><td>Scheduled posts : </td><td>'.$nb_of_scheduled.'</td></tr>';
	  echo '<tr style="background-color:'.$pendingColor.';"><td>Pending posts : </td><td>'.$nb_of_pending.'</td></tr>';
	  echo '<tr style="background-color:'.$draftColor.';"><td>Draft posts : </td><td>'.$nb_of_drafts.'</td></tr>';
	  echo '<tr style="background-color:#ffffff;color:#000000;"><td>Total unpublished posts : </td><td>'.count($rows).'</td></tr>';
	  echo '</table>';
	  echo '<hr>';
	  
	 
        //echo "<pre>"; print_r($posts); echo "</pre>"; exit; # debugging code
        return $posts;
}
	
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

        			$query.= " (SELECT ID, post_status, post_date, $blogId as `blog_id` FROM $tableName WHERE (post_status = 'draft' OR post_status = 'pending' OR post_status = 'pitch' OR post_status = 'future') AND post_type = 'post')";
        			$i++;
				}
			
			  $query.= " ORDER BY post_status DESC, blog_id DESC, post_date DESC";// LIMIT 0,$howMany;";	
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
		  $out = '<table class="sortable" style="border:solid '.$border_color.' 1px;width:100%;border-collapse:collapse;">';
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
			  $out .= '<tr style="background-color:'.$line_color.';border:solid '.$border_color.' 1px;">';
			  //$out .= '<tr><td>'.$comment->comment_post_ID .'</td>';
			  $out .= '<td style="border:solid '.$border_color.' 1px;">'.$comment->comment_author .'<br/><i>'.$comment->comment_author_email .'</i></td>';
			  $link_to_comment = '<a href="'.$post_permalink.'#comment-'.$comment->comment_ID.'" rel="external nofollow" title="'.$post_title.'" target="_blank">';
			  $out .= '<td style="border:solid '.$border_color.' 1px;text-align:center;">';
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