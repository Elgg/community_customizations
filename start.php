<?php
/**
 * General customizations for the Elgg community site
 *
 */

register_elgg_event_handler('init', 'system', 'customizations_init');

function customizations_init() {
	global $CONFIG;

	elgg_extend_view('css', 'customizations/css');

	unexpose_function('auth.gettoken');

	// turn off site notifications for performance reasons
	unregister_notification_handler('site');

	register_elgg_event_handler('create', 'object', 'messages_throttle');

	// convert messageboard to private message interface
	add_widget_type('messageboard', elgg_echo("customizations:widget:pm"), elgg_echo("customizations:widget:pm:desc"), "profile");
	register_plugin_hook('forward', 'system', 'customizations_pm_forward');

	$action_path = "{$CONFIG->pluginspath}community_customizations/actions";
	register_action('comment/edit', FALSE, "$action_path/edit_comment.php", TRUE);
}

/**
 * ban user if sending too many messages
 *
 * @param string $event
 * @param string $object_type
 * @param ElggObject $object
 * @return boolean
 */
function messages_throttle($event, $object_type, $object) {
	if ($object->getSubtype() !== 'messages') {
		return;
	}

	$msg_limit = get_plugin_setting('msg_limit', 'community_customizations');
	if (!$msg_limit) {
		return;
	}
	// two message objects created per message but after they are saved,
	// both are set to private so we only have access to one later on
	$msg_limit = $msg_limit + 1;

	$params = array(
		'type' => 'object',
		'subtype' => 'messages',
		'created_time_lower' => time() - (5*60), // 5 minutes
		'metadata_names' => 'fromId',
		'metadata_values' => get_loggedin_userid(),
		'count' => TRUE,
	);
	$num_msgs = elgg_get_entities_from_metadata($params);
	if ($num_msgs > $msg_limit) {

		$report = new ElggObject;
		$report->subtype = "reported_content";
		$report->owner_guid = get_loggedin_userid();
		$report->title = "Private message throttle";
		$report->address = get_loggedin_user()->getURL();
		$report->description = "this user exceeded the limit by sending $msg_limit messages in 5 minutes";
		$report->access_id = ACCESS_PRIVATE;
		$report->save();

		ban_user(get_loggedin_userid(), 'messages throttle');
	}
}

/**
 * Forward to referrer if posting a pm from widget
 */
function customizations_pm_forward() {
	if (get_input('pm_widget') == true) {
		return $_SERVER['HTTP_REFERER'];
	}
}
