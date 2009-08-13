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
* @author 		info@wpdashboardtwitter.com/
*/
function wpdt_load_replies( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	require_once( dirname(__FILE__) . '/twitter.class.php');
	$options = WPDashboardTwitter::dashboard_widget_options();
	$twitter = new Twitter($options['twitter_login'], WPDashboardTwitter_Helper::decrypt( $options['twitter_pwd'] ));
	$replies_xml = $twitter->getMentions(array("count" => $options['items']));
	$xml_replies = simplexml_load_string( $replies_xml );
	
	$ajaxCall = $_POST['ajaxCall'];
		
	$replyoutput = '';
	if( count($xml_replies->status) == 0 ) {
		$replyoutput .= '<li>' . __('No mentions!', 'wp-dashboard-twitter') . '</li>';
	}
	foreach ($xml_replies->status as $replies) {
		if ( seems_utf8($replytext) == true ) {
			$replytext = utf8_decode($replytext);
		}
		$replytext = WPDashboardTwitter::hyperlinkit( js_escape( $replies->text ) );
		
		// for testing purposes only
		#$replies->user->screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $replies->user->screen_name);
			
		$replyurl = sprintf('http://twitter.com/home?status=@%s &in_reply_to_status_id=%s&in_reply_to=%s', $replies->user->name, $replies->id, $replies->user->name);
		//$favoriteurl = $twitter->createFavorite('xml', $replies->id);
		
		$replyoutput .= '<li id="wpdtreply-' . $replies->id . '"><div class="comment-item wpdt-reply-item">';
		if( $options['show_avatars'] )
			$replyoutput .= '<div class="avatar"><img src="' . urldecode( $replies->user->profile_image_url ) . '" border="0" alt="" /></div>';
			
		$replyoutput .= '<h4 class="wpdt-sender comment-meta">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urldecode( $replies->user->screen_name ) . '" class="url">' . wp_specialchars( $replies->user->screen_name ) . '</a></h4>';
		$replyoutput .= '<blockquote class="wpdt-text"><p>' . $replytext . '</p></blockquote>';
		$replyoutput .= '<p class="row-actions"><a href="' . urldecode( $replyurl ) . '" replytoname="' . $replies->user->screen_name . '" onclick="WPDashboardTwitter.reply(0, ' . $replies->id . '); return false;" class="meta-reply" title="' . attribute_escape( __('Reply to a user', 'wp-dashboard-twitter') ) . '">' . __('Reply', 'wp-dashboard-twitter') . '</a> | <a href="#" onclick="WPDashboardTwitter.reply(2, ' . $replies->id . '); return false;" title="' . attribute_escape( __('Retweet this message', 'wp-dashboard-twitter') ) . '">' . __('Retweet', 'wp-dashboard-twitter') . '</a></p>';
		$replyoutput .= '<div class="wpdt-meta">';
		$replyoutput .= WPDashboardTwitter::human_diff_time_l10n( $replies->created_at );
		if( !empty( $replies->in_reply_to_screen_name ) ) {
			$replyoutput .= ' ' . __( 'in reply to', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . wp_specialchars( $replies->in_reply_to_screen_name ) . '/status/' . wp_specialchars( $replies->in_reply_to_status_id ) . '" target="_blank">' . wp_specialchars( $replies->in_reply_to_screen_name ) . '</a>';
		}
		$replyoutput .= '</div>';
		$replyoutput .= '<div style="clear:both;"></div>';
		$replyoutput .= '</div></li>';
		$i_reply++;
	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-replies-wrapper').html('" . $replyoutput . "').hide().fadeIn();" );
	else
		return $replyoutput;
}


/**
* Retrieve twitter direct messages
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @return 		string $directoutput
* @author 		info@wpdashboardtwitter.com/
*/
function wpdt_load_direct_messages( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	require_once( dirname(__FILE__) . '/twitter.class.php');
	$options = WPDashboardTwitter::dashboard_widget_options();
	$twitter = new Twitter($options['twitter_login'], WPDashboardTwitter_Helper::decrypt( $options['twitter_pwd'] ));
	$direct_xml = $twitter->getMessages(array("count" => $options['items']));
	$xml_direct = simplexml_load_string( $direct_xml );
	
	$ajaxCall = $_POST['ajaxCall'];
		
	$directoutput = '';
	if( count($xml_direct->direct_message) == 0 ) {
		$directoutput .= '<li>' . __('No direct messages!', 'wp-dashboard-twitter') . '</li>';
	}
	foreach ($xml_direct->direct_message as $messages) {
		// for testing purposes only
		#$messages->sender_screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $messages->sender_screen_name);
		
		$directtext = WPDashboardTwitter::hyperlinkit( js_escape( $messages->text ) );
		$directoutput .= '<li id="wpdtdm-' . $messages->id . '"><div class="comment-item wpdt-dm-item">';
		if( $options['show_avatars'] )
			$directoutput .= '<div class="avatar"><img src="' . urldecode( $messages->sender->profile_image_url ) . '" border="0" alt="" /></div>';
			
		$directoutput .= '<h4 class="wpdt-sender">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urlencode( $messages->sender_screen_name ) . '" class="url">' . wp_specialchars( $messages->sender_screen_name ) . '</a></h4>';
		$directoutput .= '<blockquote class="wpdt-text"><p>' . $directtext . '</p></blockquote>';
		$directoutput .= '<p class="row-actions"><a href="#" replytoname="' . $messages->sender_screen_name . '" onclick="WPDashboardTwitter.reply(1, ' . $messages->id . '); return false;" class="meta-reply" title="' . attribute_escape( sprintf(__('Compose a new Direct Message to %s', 'wp-dashboard-twitter'), $messages->sender_screen_name) ) . '">' . __('Reply', 'wp-dashboard-twitter') . '</a></p>';
		$directoutput .= '<div class="wpdt-meta">';
		$directoutput .= WPDashboardTwitter::human_diff_time_l10n( $messages->created_at );
		$directoutput .= '</div>';
		$directoutput .= '<div style="clear:both;"></div></div></li>';
		$i_direct++;
	}
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
* @author 		info@wpdashboardtwitter.com/
*/
function wpdt_load_sent_messages( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	require_once( dirname(__FILE__) . '/twitter.class.php');
	$options = WPDashboardTwitter::dashboard_widget_options();
	$twitter = new Twitter($options['twitter_login'], WPDashboardTwitter_Helper::decrypt( $options['twitter_pwd'] ));
	$sent_xml = $twitter->getUserTimeline(array("count" => $options['items']));
	$usr = $twitter->showUser(array("screen_name" => $options['twitter_login']));
	$xml_usr = simplexml_load_string( $usr );
	$xml_sent = simplexml_load_string( $sent_xml );
	
	$ajaxCall = $_POST['ajaxCall'];
		
	$sentoutput = '';
	if( count($xml_sent->status) == 0 ) {
		$sentoutput .= '<li>' . __('No sent messages!', 'wp-dashboard-twitter') . '</li>';
	}
	foreach ($xml_sent->status as $sent) {
		// for testing purposes only
		#$sent->user->screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $sent->user->screen_name);
		
		$senttext = WPDashboardTwitter::hyperlinkit( js_escape( $sent->text ) );
		$sentoutput .= '<li>';
		if( $options['show_avatars'] )
			$sentoutput .= '<div class="avatar"><img src="' . urldecode( $xml_usr->profile_image_url ) . '" border="0" alt="" /></div>';
			
		$sentoutput .= '<h4 class="wpdt-sender">' . __( 'From', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urlencode( $sent->user->screen_name ) . '" class="url">' . wp_specialchars( $sent->user->screen_name ) . '</a> ';
		if( !empty( $sent->in_reply_to_screen_name ) )
			$sentoutput .= __( 'to', 'wp-dashboard-twitter' ) . ' <a href="http://twitter.com/' . urlencode( $sent->in_reply_to_screen_name ) . '" class="url">' . wp_specialchars( $sent->in_reply_to_screen_name ) . '</a>';
			
		$sentoutput .= '</h4>';
		$sentoutput .= '<blockquote class="wpdt-text"><p>' . $senttext . '</p></blockquote>';
		$sentoutput .= '<div class="wpdt-meta">';
		$sentoutput .= WPDashboardTwitter::human_diff_time_l10n( $sent->created_at );
		$sentoutput .= '</div>';
		$sentoutput .= '<div style="clear:both;"></div></li>';
		$i_sent++;
	}
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
* @author 		info@wpdashboardtwitter.com/
*/
function wpdt_load_favorites( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	require_once( dirname(__FILE__) . '/twitter.class.php');
	$options = WPDashboardTwitter::dashboard_widget_options();
	$twitter = new Twitter($options['twitter_login'], WPDashboardTwitter_Helper::decrypt( $options['twitter_pwd'] ));
	$favorites_xml = $twitter->getFavorites(array("count" => $options['items']), "xml");
	$xml_favorites = simplexml_load_string( $favorites_xml );
	
	$ajaxCall = $_POST['ajaxCall'];
		
	$favoritesoutput = ''; $i_fav = 1;
	if( count($xml_favorites->status) == 0 ) {
		$favoritesoutput .= '<li>' . __('No favorites!', 'wp-dashboard-twitter') . '</li>';
	}
	foreach ($xml_favorites->status as $favorite) {
		// for testing purposes only
		#$favorite->user->screen_name = str_replace(array('ratterobert', 'pfotenhauer'), 'randomname', $favorite->user->screen_name);
		
		if( $i_fav > $options['items'] ) {
			break;
		}
		$favoritestext = WPDashboardTwitter::hyperlinkit( js_escape( $favorite->text ) );
		$favoritesoutput .= '<li>';
		if( $options['show_avatars'] )
			$favoritesoutput .= '<div class="avatar"><img src="' . urldecode( $favorite->user->profile_image_url ) . '" border="0" alt="" /></div>';
			
		$favoritesoutput .= '<h4 class="wpdt-sender">' . sprintf(__( 'By %s' ), '<a href="http://twitter.com/' . urlencode( $favorite->user->screen_name ) . '" class="url">' . wp_specialchars( $favorite->user->screen_name ) . '</a>') . '</h4>';
		$favoritesoutput .= '<blockquote class="wpdt-text"><p>' . $favoritestext . '</p></blockquote>';
		$favoritesoutput .= '<div class="wpdt-meta">';
		$favoritesoutput .= WPDashboardTwitter::human_diff_time_l10n( $favorite->created_at );
		$favoritesoutput .= '</div>';
		$favoritesoutput .= '<div style="clear:both;"></div></li>';
		$i_fav++;
	}
	if( $ajaxCall )
		die( "jQuery('#wpdt-fav-wrapper').html('" . $favoritesoutput . "').hide().fadeIn();" );
	else
		return $favoritesoutput;
}


/**
* Send a status update
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @author 		info@wpdashboardtwitter.com/
*/
function wpdt_send_update( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	if( !isset($_POST['in_reply_to_statusid']) || $_POST['in_reply_to_statusid'] == '' )
		$in_reply_to = '';
	else
		$in_reply_to = $_POST['in_reply_to_statusid'];
	
	require_once( dirname(__FILE__) . '/twitter.class.php');
	$options = WPDashboardTwitter::dashboard_widget_options();
	$twitter = new Twitter($options['twitter_login'], WPDashboardTwitter_Helper::decrypt( $options['twitter_pwd'] ));
	$twitter->application_source = 'wpdashboardtwitter';
	$twitter->updateStatus( stripslashes($_POST['status_text']), $in_reply_to );
}


/**
* Shortens an URL, what else?
* 
* SACK response function
*
* @since 		0.8
* @param 		boolean $ajaxCall
* @author 		info@wpdashboardtwitter.com/
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
* @author 		info@wpdashboardtwitter.com/
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


/**
* Validates credentials
* 
* SACK response function
*
* @since 		0.8.2
* @param 		boolean $ajaxCall
* @author 		info@wpdashboardtwitter.com/
*/
function wpdt_verify_credentials( $ajaxCall ) {
	// security check
	check_ajax_referer( 'wpdt_woelfi_nonce' );
	
	require_once( dirname(__FILE__) . '/twitter.class.php');
	$twitter = new Twitter($_POST['username'], base64_decode($_POST['password']));
	$verify = $twitter->verifyCredentials();
	$xml_verify = simplexml_load_string( $verify );
	if( $xml_verify->error ) {
		die("jQuery('#wp_dashboard_twitter .wpdt_credentials').css('background-color', '#FFE4E4');");
	} else {
		die("jQuery('#wp_dashboard_twitter .wpdt_credentials').css('background-color', '#E6FFE4');");
	}
	$twitter->endSession('xml');
}
?>