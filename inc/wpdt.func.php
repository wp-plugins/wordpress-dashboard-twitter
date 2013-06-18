<?php
if ( !function_exists('is_admin') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

/**
* Retrieve twitter mentions
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @return 		string $replyoutput
* @author 		scripts@schloebe.de
*/
function wpdt_load_replies( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	$options = WPDashboardTwitter::dashboard_widget_options();
	if( !class_exists('TwitterOAuth') )
		require_once( dirname(__FILE__) . '/twitteroauth.php');
	
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$replies_json = $twitter->get('statuses/mentions_timeline', array('count' => $options['items']));
	$ajaxCall = $_POST['ajaxCall'];
		
	$replyoutput = '';
	if( count($replies_json) == 0 ) {
		$replyoutput .= '<li>' . __('No mentions!', 'wp-dashboard-twitter') . '</li>';
	} else {
		foreach ($replies_json as $replies) {
			$replytext = WPDashboardTwitter::hyperlinkit( WPDashboardTwitter_Helper::esc_js( $replies->text ) );
			
			$replyurl = sprintf('http://twitter.com/home?status=@%s &in_reply_to_status_id=%s&in_reply_to=%s', $replies->user->name, $replies->id, $replies->user->name);
			
			$replyoutput .= '<li id="wpdtreply-' . $replies->id . '"><div class="comment-item wpdt-reply-item">';
			if( $options['show_avatars'] )
				$replyoutput .= '<div class="avatar"><img src="' . urldecode( $replies->user->profile_image_url ) . '" border="0" width="48" height="48" alt="" /></div>';
				
			$replyoutput .= '<h4 class="wpdt-sender comment-meta">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urldecode( $replies->user->screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $replies->user->screen_name ) . '</a> | <a href="' . urldecode( $replyurl ) . '" replytoname="' . $replies->user->screen_name . '" onclick="WPDashboardTwitter.reply(this, 0, ' . $replies->id . '); return false;" class="meta-reply" title="' . WPDashboardTwitter_Helper::esc_attr( __('Reply to a user', 'wp-dashboard-twitter') ) . '"><img src="' . WPDashboardTwitter_Helper::plugins_url('img/reply.png', __FILE__) . '" border="0" alt="' . WPDashboardTwitter_Helper::esc_attr( __('Reply', 'wp-dashboard-twitter') ) . '" /></a> <a href="#" onclick="WPDashboardTwitter.reply(this, 2, ' . $replies->id . '); return false;" title="' . WPDashboardTwitter_Helper::esc_attr( __('Retweet this message', 'wp-dashboard-twitter') ) . '"><img src="' . WPDashboardTwitter_Helper::plugins_url('img/retweet.png', __FILE__) . '" border="0" alt="' . WPDashboardTwitter_Helper::esc_attr( __('Retweet this message', 'wp-dashboard-twitter') ) . '" /></a></h4>';
			$replyoutput .= '<blockquote class="wpdt-text"><p>' . $replytext . '</p></blockquote>';
			$replyoutput .= '<div class="wpdt-meta">';
			$replyoutput .= WPDashboardTwitter::human_diff_time_l10n( $replies->created_at );
			if( !empty( $replies->in_reply_to_screen_name ) ) {
				$replyoutput .= ' ' . __( 'in reply to', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . WPDashboardTwitter_Helper::esc_html( $replies->in_reply_to_screen_name ) . '/status/' . $replies->in_reply_to_status_id . '" target="_blank">' . WPDashboardTwitter_Helper::esc_html( $replies->in_reply_to_screen_name ) . '</a>';
			}
			$replyoutput .= '</div>';
			$replyoutput .= '<div style="clear:both;"></div>';
			$replyoutput .= '</div></li>';
		}	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-replies-wrapper').html('" . $replyoutput . "').hide().fadeIn();" );
	else
		return $replyoutput;
}
/**
* Retrieve twitter friends timeline
* 
* SACK response function
*
* @since 		1.0
* @param 		boolean $ajaxCall
* @return 		string $replyoutput
* @author 		scripts@schloebe.de
*/
function wpdt_load_timeline( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	$options = WPDashboardTwitter::dashboard_widget_options();
	if( !class_exists('TwitterOAuth') )
		require_once( dirname(__FILE__) . '/twitteroauth.php');
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$timeline_json = $twitter->get('statuses/home_timeline', array('count' => $options['items'], 'include_entities' => 0));
	
	$ajaxCall = $_POST['ajaxCall'];
		
	$timelineoutput = '';
	if( count($timeline_json) == 0 ) {
		$timelineoutput .= '<li>' . __('No statuses!', 'wp-dashboard-twitter') . '</li>';
	} else {
		foreach ($timeline_json as $timeline) {
			$timelinetext = WPDashboardTwitter::hyperlinkit( WPDashboardTwitter_Helper::esc_js( $timeline->text ) );
			$timelineurl = sprintf('http://twitter.com/home?status=@%s &in_reply_to_status_id=%s&in_reply_to=%s', $timeline->user->name, $timeline->id, $timeline->user->name);	
			$timelineoutput .= '<li id="wpdttimeline-' . $timeline->id . '"><div class="comment-item wpdt-reply-item">';
			if( $options['show_avatars'] )
				$timelineoutput .= '<div class="avatar"><img src="' . urldecode( $timeline->user->profile_image_url ) . '" border="0" width="48" height="48" alt="" /></div>';	
			$timelineoutput .= '<h4 class="wpdt-sender comment-meta">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urldecode( $timeline->user->screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $timeline->user->screen_name ) . '</a> | <a href="' . urldecode( $timelineurl ) . '" replytoname="' . $timeline->user->screen_name . '" onclick="WPDashboardTwitter.reply(this, 0, ' . $timeline->id . '); return false;" class="meta-reply" title="' . WPDashboardTwitter_Helper::esc_attr( __('Reply to a user', 'wp-dashboard-twitter') ) . '"><img src="' . WPDashboardTwitter_Helper::plugins_url('img/reply.png', __FILE__) . '" border="0" alt="' . WPDashboardTwitter_Helper::esc_attr( __('Reply', 'wp-dashboard-twitter') ) . '" /></a> <a href="#" onclick="WPDashboardTwitter.reply(this, 2, ' . $timeline->id . '); return false;" title="' . WPDashboardTwitter_Helper::esc_attr( __('Retweet this message', 'wp-dashboard-twitter') ) . '"><img src="' . WPDashboardTwitter_Helper::plugins_url('img/retweet.png', __FILE__) . '" border="0" alt="' . WPDashboardTwitter_Helper::esc_attr( __('Retweet this message', 'wp-dashboard-twitter') ) . '" /></a></h4>';
			$timelineoutput .= '<blockquote class="wpdt-text"><p>' . $timelinetext . '</p></blockquote>';
			$timelineoutput .= '<div class="wpdt-meta">';
			$timelineoutput .= WPDashboardTwitter::human_diff_time_l10n( $timeline->created_at );
			if( !empty( $timeline->in_reply_to_screen_name ) ) {
				$timelineoutput .= ' ' . __( 'in reply to', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . WPDashboardTwitter_Helper::esc_html( $timeline->in_reply_to_screen_name ) . '/status/' . $timeline->in_reply_to_status_id . '" target="_blank">' . WPDashboardTwitter_Helper::esc_html( $timeline->in_reply_to_screen_name ) . '</a>';
			}
			$timelineoutput .= '</div>';
			$timelineoutput .= '<div style="clear:both;"></div>';
			$timelineoutput .= '</div></li>';
		}	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-timeline-wrapper').html('" . $timelineoutput . "').hide().fadeIn();" );
	else
		return $timelineoutput;
}
/**
* Retrieve twitter direct messages
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @return 		string $directoutput
* @author 		scripts@schloebe.de
*/
function wpdt_load_direct_messages( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	$options = WPDashboardTwitter::dashboard_widget_options();
	if( !class_exists('TwitterOAuth') )
		require_once( dirname(__FILE__) . '/twitteroauth.php');
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$direct_json = $twitter->get('direct_messages', array('count' => $options['items']));
	print_r($direct_json);
	$ajaxCall = $_POST['ajaxCall'];
		
	$directoutput = '';
	if( count($direct_json) == 0 ) {
		$directoutput .= '<li>' . __('No direct messages!', 'wp-dashboard-twitter') . '</li>';
	} else {
		$i_direct = 0;
		foreach ($direct_json as $messages) {
			// for testing purposes only
			#$messages->sender_screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $messages->sender_screen_name);	
			$directtext = WPDashboardTwitter::hyperlinkit( WPDashboardTwitter_Helper::esc_js( $messages->text ) );
			$directoutput .= '<li id="wpdtdm-' . $messages->id . '"><div class="comment-item wpdt-dm-item">';
			if( $options['show_avatars'] )
				$directoutput .= '<div class="avatar"><img src="' . urldecode( $messages->sender->profile_image_url ) . '" width="48" height="48" border="0" alt="" /></div>';	
			$directoutput .= '<h4 class="wpdt-sender">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urlencode( $messages->sender_screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $messages->sender_screen_name ) . '</a></h4>';
			$directoutput .= '<blockquote class="wpdt-text"><p>' . $directtext . '</p></blockquote>';
			$directoutput .= '<p class="row-actions"><a href="#" replytoname="' . $messages->sender_screen_name . '" onclick="WPDashboardTwitter.reply(this, 1, ' . $messages->id . '); return false;" class="meta-reply" title="' . WPDashboardTwitter_Helper::esc_attr( sprintf(__('Compose a new Direct Message to %s', 'wp-dashboard-twitter'), $messages->sender_screen_name) ) . '">' . __('Reply', 'wp-dashboard-twitter') . '</a></p>';
			$directoutput .= '<div class="wpdt-meta">';
			$directoutput .= WPDashboardTwitter::human_diff_time_l10n( $messages->created_at );
			$directoutput .= '</div>';
			$directoutput .= '<div style="clear:both;"></div></div></li>';
			$i_direct++;
		}	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-direct-wrapper').html('" . $directoutput . "').hide().fadeIn();" );
	else
		return $directoutput;
}

/**
* Retrieve twitter sent messages
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @return 		string $sentoutput
* @author 		scripts@schloebe.de
*/
function wpdt_load_sent_messages( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	$options = WPDashboardTwitter::dashboard_widget_options();
	if( !class_exists('TwitterOAuth') )
		require_once( dirname(__FILE__) . '/twitteroauth.php');
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$sent_json = $twitter->get('statuses/user_timeline', array('count' => $options['items']));
	$usr = $twitter->get('account/verify_credentials');
	
	$ajaxCall = $_POST['ajaxCall'];
		
	$sentoutput = '';
	if( count($sent_json) == 0 ) {
		$sentoutput .= '<li>' . __('No sent messages!', 'wp-dashboard-twitter') . '</li>';
	} else {		$i_sent = 0;
		foreach ($sent_json as $sent) {
			// for testing purposes only
			#$sent->user->screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $sent->user->screen_name);
			$senttext = WPDashboardTwitter::hyperlinkit( WPDashboardTwitter_Helper::esc_js( $sent->text ) );
			$sentoutput .= '<li>';
			if( $options['show_avatars'] )
				$sentoutput .= '<div class="avatar"><img src="' . urldecode( $usr->profile_image_url ) . '" width="48" height="48" border="0" alt="" /></div>';	
			$sentoutput .= '<h4 class="wpdt-sender">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urlencode( $sent->user->screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $sent->user->screen_name ) . '</a> ';
			if( !empty( $sent->in_reply_to_screen_name ) )
				$sentoutput .= __( 'to', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urlencode( $sent->in_reply_to_screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $sent->in_reply_to_screen_name ) . '</a>';	
			$sentoutput .= '</h4>';
			$sentoutput .= '<blockquote class="wpdt-text"><p>' . $senttext . '</p></blockquote>';
			$sentoutput .= '<div class="wpdt-meta">';
			$sentoutput .= WPDashboardTwitter::human_diff_time_l10n( $sent->created_at );
			$sentoutput .= '</div>';
			$sentoutput .= '<div style="clear:both;"></div></li>';
			$i_sent++;
		}	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-sent-wrapper').html('" . $sentoutput . "').hide().fadeIn();" );
	else
		return $sentoutput;
}

/**
* Retrieve twitter sent messages
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @return 		string $favoritesoutput
* @author 		scripts@schloebe.de
*/
function wpdt_load_favorites( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	$options = WPDashboardTwitter::dashboard_widget_options();
	if( !class_exists('TwitterOAuth') )
		require_once( dirname(__FILE__) . '/twitteroauth.php');
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$favorites_json = $twitter->get('favorites/list', array('count' => $options['items']));	#print_r($favorites_json);
	$ajaxCall = $_POST['ajaxCall'];
	$favoritesoutput = ''; $i_fav = 1;
	if( count($favorites_json) == 0 ) {
		$favoritesoutput .= '<li>' . __('No favorites!', 'wp-dashboard-twitter') . '</li>';
	} else {
		$i_fav = 0;
		foreach ($favorites_json as $favorite) {
			// for testing purposes only
			#$favorite->user->screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $favorite->user->screen_name);	
			if( $i_fav > $options['items'] ) {
				break;
			}
			$favoritestext = WPDashboardTwitter::hyperlinkit( WPDashboardTwitter_Helper::esc_js( $favorite->text ) );
			$favoritesoutput .= '<li>';
			if( $options['show_avatars'] )
				$favoritesoutput .= '<div class="avatar"><img src="' . urldecode( $favorite->user->profile_image_url ) . '" width="48" height="48" border="0" alt="" /></div>';	
			$favoritesoutput .= '<h4 class="wpdt-sender">' . sprintf(__( 'By %s' ), '<a href="http://twitter.com/' . urlencode( $favorite->user->screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $favorite->user->screen_name ) . '</a>') . '</h4>';
			$favoritesoutput .= '<blockquote class="wpdt-text"><p>' . $favoritestext . '</p></blockquote>';
			$favoritesoutput .= '<div class="wpdt-meta">';
			$favoritesoutput .= WPDashboardTwitter::human_diff_time_l10n( $favorite->created_at );
			$favoritesoutput .= '</div>';
			$favoritesoutput .= '<div style="clear:both;"></div></li>';
			$i_fav++;
		}	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-fav-wrapper').html('" . $favoritesoutput . "').hide().fadeIn();" );
	else
		return $favoritesoutput;
}
/**
* Retrieve user twitter messages that have been retweeted by others
* 
* SACK response function
*
* @since 		1.0
* @param 		boolean $ajaxCall
* @return 		string $favoritesoutput
* @author 		scripts@schloebe.de
*/
function wpdt_load_retweets( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	$options = WPDashboardTwitter::dashboard_widget_options();
	if( !class_exists('TwitterOAuth') )
		require_once( dirname(__FILE__) . '/twitteroauth.php');
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$retweets_json = $twitter->get('statuses/retweets_of_me', array('count' => $options['items']));
	$ajaxCall = $_POST['ajaxCall'];
	$retweetsoutput = '';
	if( count($retweets_json) == 0 ) {
		$retweetsoutput .= '<li>' . __('No retweets of your statuses yet!', 'wp-dashboard-twitter') . '</li>';
	} else {		$i_retweets = 0;
		foreach ($retweets_json as $retweet) {	
			$retweetstext = WPDashboardTwitter::hyperlinkit( WPDashboardTwitter_Helper::esc_js( $retweet->text ) );
			$retweetsoutput .= '<li>';
			if( $options['show_avatars'] )
				$retweetsoutput .= '<div class="avatar"><img src="' . urldecode( $retweet->user->profile_image_url ) . '" width="48" height="48" border="0" alt="" /></div>';	
			$retweetsoutput .= '<h4 class="wpdt-sender">' . sprintf(__( 'By %s' ), '<a href="http://twitter.com/' . urlencode( $retweet->user->screen_name ) . '" class="url">' . WPDashboardTwitter_Helper::esc_html( $retweet->user->screen_name ) . '</a>') . '</h4>';
			$retweetsoutput .= '<blockquote class="wpdt-text"><p>' . $retweetstext . '</p></blockquote>';
			$retweetsoutput .= '<div class="wpdt-meta">';
			$retweetsoutput .= WPDashboardTwitter::human_diff_time_l10n( $retweet->created_at );
			$retweetsoutput .= '</div>';
			$retweetsoutput .= '<div style="clear:both;"></div></li>';
			$i_retweets++;
		}	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-retweets-wrapper').html('" . $retweetsoutput . "').hide().fadeIn();" );
	else
		return $retweetsoutput;
}
/**
* Send a status update
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @author 		scripts@schloebe.de
*/
function wpdt_send_update( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	if( !isset($_POST['in_reply_to_statusid']) || $_POST['in_reply_to_statusid'] == '' )
		$in_reply_to = '';
	else
		$in_reply_to = $_POST['in_reply_to_statusid'];
	
	$options = WPDashboardTwitter::dashboard_widget_options();
	require_once( dirname(__FILE__) . '/twitteroauth.php');
	require_once( dirname(__FILE__) . '/config.php');
	$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $options['wpdt_oauth_token'], $options['wpdt_oauth_secret']);
	$twitter->useragent = 'WordPress Dashboard Twitter';
	$twitter->post('statuses/update', array('status' => stripslashes($_POST['status_text']), 'in_reply_to_status_id' => $in_reply_to));
}
/**
* Shortens an URL, what else?
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @author 		scripts@schloebe.de
*/
function wpdt_shorten_url( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	$options = WPDashboardTwitter::dashboard_widget_options();
	$shorteners = WPDashboardTwitter_Helper::get_url_shorteners();
	$apiurl = $shorteners[$options['url_service']]['apiurl'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiurl . $_POST['longurl']);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress Dashboard Twitter');
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$url_data = curl_exec($ch);
	switch( $options['url_service'] ) {
		case 'wpgd':
			$url = $url_data;
			break;
		case 'trim':
			$url_data = simplexml_load_string( $url_data );
			$url = $url_data->url;
			break;
		case 'bitly':
			$url_data = simplexml_load_string( $url_data );
			$url = $url_data->results->nodeKeyVal->shortUrl;
			break;
	}
	curl_close($ch);
	die( "jQuery('#wpdt-txtarea').val(jQuery('#wpdt-txtarea').val() + ' " . $url . "');" );
}
/**
* Shortens an image URL
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @author 		scripts@schloebe.de
*/
function wpdt_shorten_imgurl( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );

	$options = WPDashboardTwitter::dashboard_widget_options();
	$img = str_replace('inc/', '', dirname( __FILE__ ) . '/uploads/') . $_POST['imgbasename'];
	$params = array(
		"media" => "@$img",
		"username" => $options['twitter_login'],
		"password" => WPDashboardTwitter_Helper::decrypt( $options['twitter_pwd'] ),
		"source" => "wordpressdashboardtwitter"
	);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://twitpic.com/api/upload");
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress Dashboard Twitter');
	$twitpic_data = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$twitpic_data = simplexml_load_string( $twitpic_data );
	curl_close($ch);
	if( $twitpic_data['stat'] == 'fail' )
		die( "alert('TwitPic: " . $twitpic_data->err['msg'] . "');" );
	else
		die( "jQuery('#wpdt-txtarea').val(jQuery('#wpdt-txtarea').val() + ' " . $twitpic_data->mediaurl . "');" );
}
?>