<?php
/**
 * The WPDashboardTwitter class plugin file
 *
 * @package 	WordPress_Plugins
 * @subpackage 	WPDashboardTwitter
 */

if ( !defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/**
 * Define the plugin version
 */
define("WPDT_VERSION", "0.8.2");

/**
 * Define the global var WPDTISWP27, returning bool if at least WP 2.7 is running
 */
define('WPDTISWP27', version_compare($GLOBALS['wp_version'], '2.6.999', '>'));

/**
 * Define the global var WPDTHASPHP5, returning bool if PHP 5 is running
 */
define('WPDTHASPHP5', version_compare(phpversion(), '5.0.0', '>='));

/**
 * Define the plugin path slug
 */
define("WPDT_PLUGINPATH", "/" . plugin_basename( dirname(__FILE__) ) . "/");

/**
 * Define the plugin full url
 */
define("WPDT_PLUGINFULLURL", WP_PLUGIN_URL . WPDT_PLUGINPATH );

/**
 * Define the plugin full directory
 */
define("WPDT_PLUGINFULLDIR", WP_PLUGIN_DIR . WPDT_PLUGINPATH );

/**
 * Define the spinning loading image
 */
define("WPDT_LOADINGIMG", admin_url('images/loading.gif') );

/** 
* The WPDashboardTwitter class
*
* @package 		WordPress_Plugins
* @subpackage 	WPDashboardTwitter
* @since 		0.8
* @author 		info@wpdashboardtwitter.com
*/
class WPDashboardTwitter {

	/**
	 * Our unique nonce key
	 * Beware: It will contain the evil w-word. :-(
	 * @access private
	 */
	private $nonce;
	
	/**
	 * The plugin's upload dir to store
	 * files locally uploaded to TwitPic
	 * @access private
	 */
	private $upload_url;

	/**
 	* The WPDashboardTwitter class constructor
 	* initializing required stuff for the plugin
 	* 
 	* We don't really need this since the plugin requires
 	* PHP5 to run, but well... ;-)
 	* 
	* PHP 4 Compatible Constructor
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function wpdashboardtwitter() {
		$this->__construct();
	}
	
	
	/**
 	* The WPDashboardTwitter class constructor
 	* initializing required stuff for the plugin
 	* 
	* PHP 5 Constructor
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function __construct() {
		
		$this->upload_url = WPDT_PLUGINFULLDIR . 'uploads/';
			
		if ( !function_exists("add_action") ) return;
		
		if ( !WPDTISWP27 ) {
			add_action('admin_notices', array(&$this, 'wp27Notice'));
			return;
		}
		if ( !WPDTHASPHP5 ) {
			add_action('admin_notices', array(&$this, 'php5Notice'));
			return;
		}
		
		/** 
 		* This file holds all of the general information and functions
 		*/
		require_once(WPDT_PLUGINFULLDIR . 'inc/wpdt.func.php');
		
		/** 
 		* This file holds all of the AJAX file upload functions
 		*/
		require_once(WPDT_PLUGINFULLDIR . 'inc/upload.func.php');
		
		/** 
 		* This file holds all of the compatibility and helper methods
 		*/
		require_once(WPDT_PLUGINFULLDIR . 'inc/wpdt-helper.class.php');
			
		add_action('admin_init', array(&$this, 'load_textdomain'), 20);
		add_action('admin_init', array(&$this, 'admin_init'), 20);
		add_action('wp_ajax_wpdt_load_replies', 'wpdt_load_replies' );
		add_action('wp_ajax_wpdt_load_direct_messages', 'wpdt_load_direct_messages' );
		add_action('wp_ajax_wpdt_load_sent_messages', 'wpdt_load_sent_messages' );
		add_action('wp_ajax_wpdt_load_favorites', 'wpdt_load_favorites' );
		add_action('wp_ajax_wpdt_send_update', 'wpdt_send_update' );
		add_action('wp_ajax_wpdt_shorten_url', 'wpdt_shorten_url' );
		add_action('wp_ajax_wpdt_shorten_imgurl', 'wpdt_shorten_imgurl' );
		add_action('wp_ajax_wpdt_verify_credentials', 'wpdt_verify_credentials' );
	}
	
	
	
	/**
 	* Initialize and load the plugin stuff for administration panel only
 	*
 	* @since 		0.8
 	* @uses 		$pagenow
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function admin_init() {
		global $pagenow;
		$this->nonce = wp_create_nonce('wpdt_woelfi_nonce'); // Includes the evil w-word, errr :-(
		
		if ( !function_exists("add_action") ) return;
		$options = $this->dashboard_widget_options();
		
		if( current_user_can('level_10') ) {
			add_action('wp_dashboard_setup', array (&$this, 'init_dashboard_setup'));
			if( $pagenow == 'index.php' ) {
				add_action('admin_print_scripts', array(&$this, 'js_admin_header') );
				// will be loaded at runtime
				//wp_enqueue_script('wpdt-charcounter-js', WPDashboardTwitter_Helper::plugins_url('inc/js/charcounter.js', __FILE__), array(), WPDT_VERSION);
				if( $options['use_twitpic'] ) {
					// will be loaded at runtime
					//wp_enqueue_script('wpdt-ajaxupload-js', WPDashboardTwitter_Helper::plugins_url('inc/js/ajaxupload.3.5.js', __FILE__), array(), WPDT_VERSION);
					//wp_enqueue_script('wpdt-ajaxupload-loader-js', WPDashboardTwitter_Helper::plugins_url('inc/js/scripts_ajaxupload.js', __FILE__), array( 'jquery' ), WPDT_VERSION);
				}
				if( isset( $_GET['edit'] ) ) {
					wp_enqueue_script('wpdt-js-helper', WPDashboardTwitter_Helper::plugins_url('inc/js/scripts_helper.js', __FILE__), array(), WPDT_VERSION);
				}
				wp_enqueue_script('wpdt-js', WPDashboardTwitter_Helper::plugins_url('inc/js/scripts_general.js', __FILE__), array( 'jquery', 'jquery-ui-tabs' ), WPDT_VERSION);
				wp_enqueue_style('jquery-ui-tabs-wpdt', WPDashboardTwitter_Helper::plugins_url('inc/css/tabs.style.css', __FILE__));
				wp_enqueue_style('misc-css-wpdt', WPDashboardTwitter_Helper::plugins_url('inc/css/misc.style.css', __FILE__));
			}
		}
	}
	
	
	/**
 	* Initialize and load the dashboard widget setup stuff
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function init_dashboard_setup() {
		wp_add_dashboard_widget( 'wp_dashboard_twitter', __('WordPress Dashboard Twitter', 'wp-dashboard-twitter'), array(&$this, 'init_dashboard_widget'), array(&$this, 'init_dashboard_widget_setup') );
	}
	
	
	
	/**
 	* Initialize and load the dashboard widget stuff
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function init_dashboard_widget() {
		$errors = array();
		
		require_once( dirname(__FILE__) . '/inc/twitter.class.php');
		$options = $this->dashboard_widget_options();
		$twitter = new Twitter($options['twitter_login'], $options['twitter_pwd']);
		$usr = $twitter->showUser(array("screen_name" => $options['twitter_login']));
		$xml_usr = simplexml_load_string( $usr );
		$ratelimit = $twitter->rateLimitStatus(true);
		$xml_ratelimit = simplexml_load_string( $ratelimit );
		$shorteners = WPDashboardTwitter_Helper::get_url_shorteners();
		
		if( !is_writable( $this->upload_url ) && $options['use_twitpic'] )
			$errors[] = sprintf(__("The following directory needs to be writable: %s [<a href='%s'>Recheck</a>]", 'wp-dashboard-twitter'), plugin_basename( $this->upload_url ), admin_url('/'));
		if( $options['twitter_login'] == '' || $options['twitter_pwd'] == '' )
			$errors[] = __("Please enter your Twitter username and password by clicking the widget's 'configure' link!", 'wp-dashboard-twitter');
		if( $twitter->lastStatusCode() == '400' )
			$errors[] = __( '<strong>NOTE:</strong> The Twitter API only allows clients to make a limited number of calls in a given period. You just exceeded the rate limit.', 'wp-dashboard-twitter' );
		
		if( count($errors) == 0 )
			echo '<p class="account-info">' . $this->get_account_info( $usr ) . '</p>';
			
		foreach( $errors as $error ) {
			echo '<span class="error fade">' . $error . '</span>';
		}
		?>
		<img src="<?php echo WPDashboardTwitter_Helper::plugins_url('inc/img/twitter.gif', __FILE__); ?>" border="0" alt="" id="twitterbird" />
		<div id="wpdt-tabs" class="ui-tabs">
			<ul>
				<li><a href="#wpdt-replies"><?php _e('Mentions', 'wp-dashboard-twitter'); ?></a></li>
				<li><a href="#wpdt-dm"><?php _e('Direct', 'wp-dashboard-twitter'); ?></a></li>
				<li><a href="#wpdt-sent"><?php _e('Sent', 'wp-dashboard-twitter'); ?></a></li>
				<li><a href="#wpdt-favorites"><?php _e('Favorites', 'wp-dashboard-twitter'); ?></a></li>
			</ul>
			<div id="wpdt-replies" class="wpdt-container">
				<ul id="wpdt-replies-wrapper">
				</ul>
				<p class="textright wpdt-loader">
					<img src="<?php echo WPDT_LOADINGIMG; ?>" border="0" alt="" align="left" class="wpdt-ajax-loader" style="display:none;" /> <?php if( count($errors) == 0 ) { ?><a class="button-primary wpdt-btn-update-status" href="#"><?php _e('Update Status', 'wp-dashboard-twitter'); ?></a><?php } ?> <a class="button" href="#" id="wpdt-btn-load-replies" title="<?php printf(__('Remaining API for account %s: %d/%d', 'wp-dashboard-twitter'), $xml_usr->screen_name, $xml_ratelimit->{'remaining-hits'}, $xml_ratelimit->{'hourly-limit'}); ?>"><?php _e('Reload', 'wp-dashboard-twitter'); ?></a>
				</p>
			</div>
			<div id="wpdt-dm" class="ui-tabs-hide wpdt-container">
				<ul id="wpdt-direct-wrapper">
				</ul>
				<p class="textright wpdt-loader">
					<img src="<?php echo WPDT_LOADINGIMG; ?>" border="0" alt="" align="left" class="wpdt-ajax-loader" style="display:none;" /> <?php if( count($errors) == 0 ) { ?><a class="button-primary wpdt-btn-update-status"><?php _e('Update Status', 'wp-dashboard-twitter'); ?></a><?php } ?> <a class="button" href="#" id="wpdt-btn-load-direct-messages" title="<?php printf(__('Remaining API for account %s: %d/%d', 'wp-dashboard-twitter'), $xml_usr->screen_name, $xml_ratelimit->{'remaining-hits'}, $xml_ratelimit->{'hourly-limit'}); ?>"><?php _e('Reload', 'wp-dashboard-twitter'); ?></a>
				</p>
			</div>
			<div id="wpdt-sent" class="ui-tabs-hide wpdt-container">
				<ul id="wpdt-sent-wrapper">
				</ul>
				<p class="textright wpdt-loader">
					<img src="<?php echo WPDT_LOADINGIMG; ?>" border="0" alt="" align="left" class="wpdt-ajax-loader" style="display:none;" /> <?php if( count($errors) == 0 ) { ?><a class="button-primary wpdt-btn-update-status"><?php _e('Update Status', 'wp-dashboard-twitter'); ?></a><?php } ?> <a class="button" href="#" id="wpdt-btn-load-sent-messages" title="<?php printf(__('Remaining API for account %s: %d/%d', 'wp-dashboard-twitter'), $xml_usr->screen_name, $xml_ratelimit->{'remaining-hits'}, $xml_ratelimit->{'hourly-limit'}); ?>"><?php _e('Reload', 'wp-dashboard-twitter'); ?></a>
				</p>
			</div>
			<div id="wpdt-favorites" class="ui-tabs-hide wpdt-container">
				<ul id="wpdt-fav-wrapper">
				</ul>
				<p class="textright wpdt-loader">
					<img src="<?php echo WPDT_LOADINGIMG; ?>" border="0" alt="" align="left" class="wpdt-ajax-loader" style="display:none;" /> <?php if( count($errors) == 0 ) { ?><a class="button-primary wpdt-btn-update-status"><?php _e('Update Status', 'wp-dashboard-twitter'); ?></a><?php } ?> <a class="button" href="#" id="wpdt-btn-load-favorites" title="<?php printf(__('Remaining API for account %s: %d/%d', 'wp-dashboard-twitter'), $xml_usr->screen_name, $xml_ratelimit->{'remaining-hits'}, $xml_ratelimit->{'hourly-limit'}); ?>"><?php _e('Reload', 'wp-dashboard-twitter'); ?></a>
				</p>
			</div>
			
			<div id="wpdt-update-wrapper" class="ui-tabs-panel ui-widget-content ui-corner-bottom" style="display:none;">
				<p class="account-info wpdt-toolbar">
					<?php _e('Shorten URL', 'wp-dashboard-twitter'); ?> (<?php echo $shorteners[$options['url_service']]['name']; ?>): <input id="wpdt-long-url" size="25" type="text" value="" name="wpdt-long-url" /> <a href="#" id="wpdt-btn-shorten-url"><img src="<?php echo WPDashboardTwitter_Helper::plugins_url('inc/img/shorten-url.gif', __FILE__); ?>" border="0" width="10" height="10" alt="<?php _e('Shorten URL', 'wp-dashboard-twitter'); ?>" title="<?php _e('Shorten URL', 'wp-dashboard-twitter'); ?>" /></a> 
					<?php if( $options['use_twitpic'] ) { ?>
					| <a href="#" id="wpdt-imgupload_button"><img src="<?php echo WPDashboardTwitter_Helper::plugins_url('inc/img/image.gif', __FILE__); ?>" border="0" width="10" height="10" alt="<?php _e('Upload &amp; Shorten Image', 'wp-dashboard-twitter'); ?>" title="<?php _e('Upload &amp; Shorten Image', 'wp-dashboard-twitter'); ?>" /></a>
					<?php } ?>
					<span id="wpdt-charcount">140</span>
				</p>
				<textarea id="wpdt-txtarea" class="widefat mceEditor" cols="15" rows="2" name="wpdt-txtarea"></textarea>
				<input type="hidden" name="wpdt_in_reply_to_statusid" id="wpdt_in_reply_to_statusid" value="" />
				<p class="textright">
					<img src="<?php echo WPDT_LOADINGIMG; ?>" border="0" alt="" align="left" id="wpdt-ajax-loader-update" style="display:none;" /> <a class="button-primary" id="wpdt-btn-send-status-update"><?php _e('Send Update', 'wp-dashboard-twitter'); ?></a> <a class="button" href="#" id="wpdt-btn-cancel-status-update"><?php _e('Cancel'); ?></a>
				</p>
			</div>
		</div>
		<?php
	}
	
	
	
	/**
 	* Initialize and load the dashboard widget options stuff
 	*
 	* @since 		0.8
 	* @return 		array
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function dashboard_widget_options() {
		$defaults = array( 'items' => 5, 'twitter_login' => '', 'twitter_pwd' => '', 'show_avatars' => 0, 'startup_tab' => 0, 'use_twitpic' => 1, 'url_service' => 'wpgd' );
		if ( ( !$options = get_option( 'dashboard_twitter_widget_options' ) ) || !is_array($options) )
			$options = array();
		return array_merge( $defaults, $options );
	}
	
	
	
	/**
 	* Initialize and load the dashboard widget options output
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function init_dashboard_widget_setup() {
		$options = $this->dashboard_widget_options();
		
		if ( 'post' == strtolower($_SERVER['REQUEST_METHOD']) && isset( $_POST['widget_id'] ) && 'wp_dashboard_twitter' == $_POST['widget_id'] ) {
			foreach ( array( 'items', 'twitter_login', 'twitter_pwd', 'show_avatars', 'startup_tab', 'use_twitpic', 'url_service' ) as $key )
				$options[$key] = $_POST[$key];
				
			update_option( 'dashboard_twitter_widget_options', $options );
		}
		?>
		<p>
			<label for="twitter_login"><?php _e('Twitter Username', 'wp-dashboard-twitter' ); ?></label>
			<input id="twitter_login" class="widefat wpdt_credentials" type="text" value="<?php echo $options['twitter_login']; ?>" name="twitter_login" />
		</p>
		<p>
			<label for="twitter_pwd"><?php _e('Twitter Password', 'wp-dashboard-twitter' ); ?></label>
			<input id="twitter_pwd" class="widefat wpdt_credentials" type="password" value="<?php echo $options['twitter_pwd']; ?>" name="twitter_pwd" />
		</p>
		<p class="textright">
			<img src="<?php echo WPDT_LOADINGIMG; ?>" border="0" alt="" align="left" id="wpdt-verify-userdata-ajax-loader" style="display:none;" /> <a class="button" href="#" id="wpdt-btn-verify-userdata"><?php _e('Verify Credentials', 'wp-dashboard-twitter' ); ?></a>
		</p>
		<p>
			<label for="items"><?php _e('How many items?', 'wp-dashboard-twitter' ); ?></label>
			<select id="items" name="items">
				<option value="3"<?php echo ( $options['items'] == '3' ? " selected='selected'" : '' ) ?>>3</option>
				<option value="5"<?php echo ( $options['items'] == '5' ? " selected='selected'" : '' ) ?>>5</option>
				<option value="10"<?php echo ( $options['items'] == '10' ? " selected='selected'" : '' ) ?>>10</option>
			</select>
		</p>
		<p>
			<label for="startup_tab"><?php _e('Tab to open by default', 'wp-dashboard-twitter' ); ?></label>
			<select id="startup_tab" name="startup_tab">
				<option value="0"<?php echo ( $options['startup_tab'] == '0' ? " selected='selected'" : '' ) ?>><?php _e('Mentions', 'wp-dashboard-twitter'); ?></option>
				<option value="1"<?php echo ( $options['startup_tab'] == '1' ? " selected='selected'" : '' ) ?>><?php _e('Direct', 'wp-dashboard-twitter'); ?></option>
				<option value="2"<?php echo ( $options['startup_tab'] == '2' ? " selected='selected'" : '' ) ?>><?php _e('Sent', 'wp-dashboard-twitter'); ?></option>
				<option value="3"<?php echo ( $options['startup_tab'] == '3' ? " selected='selected'" : '' ) ?>><?php _e('Favorites', 'wp-dashboard-twitter'); ?></option>
			</select>
		</p>
		<p>
			<label for="url_service"><?php _e('URL Shortener', 'wp-dashboard-twitter' ); ?></label>
			<select id="url_service" name="url_service">
				<option value="wpgd"<?php echo ( $options['url_service'] == 'wpgd' ? " selected='selected'" : '' ) ?>><?php _e('wp.gd', 'wp-dashboard-twitter'); ?></option>
				<option value="trim"<?php echo ( $options['url_service'] == 'trim' ? " selected='selected'" : '' ) ?>><?php _e('tr.im', 'wp-dashboard-twitter'); ?></option>
			</select>
		</p>
		<p>
			<input id="show_avatars" name="show_avatars" type="checkbox" value="1"<?php if( 1 == $options['show_avatars'] ) echo ' checked="checked"'; ?> />
			<label for="show_avatars"><?php _e('Show Avatars?', 'wp-dashboard-twitter' ); ?></label>
		</p>
		<p>
			<input id="use_twitpic" name="use_twitpic" type="checkbox" value="1"<?php if( 1 == $options['use_twitpic'] ) echo ' checked="checked"'; ?> />
			<label for="use_twitpic"><?php _e('Use Twitpic to share photos on Twitter?', 'wp-dashboard-twitter' ); ?></label>
		</p>
		<?php
	}
	
	
	/**
 	* Turns plain text links into hyperlinks
 	*
 	* @since 		0.8
 	* @param 		string $text
 	* @return 		string
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function hyperlinkit( $text ) {
		// make URLs clickable
		$text = make_clickable($text);
	   	// #hashtags
		#$hashtag_expr = "/(^|\s)#(\w*)/i";
		$hashtag_expr = "/(^|\s)#([a-zA-ZöäüÖÄÜß_0-9]*)/i";
		$hashtag_replace = "$1<a href=\"http://twitter.com/search?q=%23$2\" target=\"_blank\">#$2</a>";
		$text = preg_replace($hashtag_expr, $hashtag_replace, $text);
		// @mentions
		$text = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)@{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/$2\" target=\"_blank\">@$2</a>$3 ", $text);
    	return $text;
	}
	
	
	
	/**
 	* Returns twitter account info
 	* of the authenticated user
 	*
 	* @since 		0.8
 	* @param 		array $usr
 	* @return 		string
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function get_account_info( $usr ) {
		$xml_usr = simplexml_load_string( $usr );
		return sprintf(__('Hello %s', 'wp-dashboard-twitter') . '! ' . __('You have %d followers', 'wp-dashboard-twitter'), $xml_usr->screen_name, $xml_usr->followers_count) . '.';
	}
	
	
	
	/**
 	* Determines the difference between two timestamps, output localized
 	*
 	* @since 		0.8
 	* @param 		string $time
 	* @return 		string
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function human_diff_time_l10n( $time ) {
		if ( ( abs( time() - strtotime($time)) ) < 86400 )
			return sprintf( __('%s ago', 'wp-dashboard-twitter'), human_time_diff( strtotime($time) ) );
		else
			return date_i18n( sprintf('%s %s', get_option( 'date_format' ), get_option( 'time_format' )), strtotime($time));
	}
	
	
	
	/**
 	* Changes url scheme from http to https
 	* if constant FORCE_SSL_ADMIN is set to true
 	* in wp-config.php
 	*
 	* @since 		0.8
 	* @deprecated	Used for testing purposes only
 	* @param 		string 	$url
 	* @return 		string 	$url
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function url_scheme( $url ) {
		if( force_ssl_admin() ) {
			$url = preg_replace('|^http://|', 'https://', $url);
		}
		return $url;
	}
	
	
	
	/**
 	* Writes javascript stuff into page header needed for the plugin and prints the SACK library
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function js_admin_header() {
		wp_print_scripts( array( 'sack' ));
		$options = $this->dashboard_widget_options();
		?>
<script type="text/javascript">
//<![CDATA[
wpdtAjaxL10n = {
	requestUrl: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
	uploadFileURI: "<?php echo WPDashboardTwitter_Helper::plugins_url('inc/', __FILE__) ?>",
	startupTab: <?php echo $options['startup_tab']; ?>,
	twitPicEnabled: <?php echo ($options['use_twitpic'] == 1 ? '1' : '0'); ?>,
	emptyTweetMsg: "<?php _e('An empty tweet would not make sense, eh?', 'wp-dashboard-twitter'); ?>",
	updateStatusMsg: "<?php _e('Send Update', 'wp-dashboard-twitter'); ?>",
	sendDMMsg: "<?php _e('Send Direct Message', 'wp-dashboard-twitter'); ?>",
	verifyCredentialsMsg: "<?php _e('Verify Credentials', 'wp-dashboard-twitter'); ?>",
	sendingTweetMsg: "<?php _e('Sending...', 'wp-dashboard-twitter'); ?>",
	emptyLongUrlMsg: "<?php _e('Please enter a long URL!', 'wp-dashboard-twitter'); ?>",
	invalidFileExtMsg: "<?php _e('Invalid file extension!', 'wp-dashboard-twitter'); ?>",
	_ajax_nonce: "<?php echo $this->nonce; ?>"
}
//]]>
</script>
	<?php
	}
	
	
	
	/**
 	* Initialize and load the plugin textdomain
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function load_textdomain() {
		load_plugin_textdomain('wp-dashboard-twitter', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}
	
	
	/**
 	* Checks for the version of WordPress,
 	* and adds a message to inform the user
 	* if required WP version is less than 2.7
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function wp27Notice() {
		echo "<div id='wpversionfailedmessage' class='error fade'><p>" . __('WordPress Dashboard Twitter requires at least WordPress 2.7!', 'wp-dashboard-twitter') . "</p></div>";
	}
	
	
	/**
 	* Checks for the version of PHP interpreter,
 	* and adds a message to inform the user
 	* if required PHP version is less than 5.0.0
 	*
 	* @since 		0.8
 	* @author 		info@wpdashboardtwitter.com
 	*/
	function php5Notice() {
		echo "<div id='phpversionfailedmessage' class='error fade'><p>" . __('WordPress Dashboard Twitter requires at least PHP5!', 'wp-dashboard-twitter') . "</p></div>";
	}
	
}

if ( class_exists('WPDashboardTwitter') ) {
	$WPDashboardTwitter = new WPDashboardTwitter();
}
?>