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

	// limit access to the add links
	register_elgg_event_handler('pagesetup', 'system', 'customizations_remove_add_links');
	register_plugin_hook('action', 'bookmarks/add', 'customizations_stop_add');
	register_plugin_hook('action', 'pages/edit', 'customizations_stop_add');
	//register_plugin_hook('action', 'pages/editwelcome', 'customizations_stop_add');

	// override the pages page handler to pull out link to add welcome message
	register_page_handler('pages','customizations_pages_page_handler');
	//unregister_action("pages/editwelcome");
	unset($CONFIG->actions["pages/editwelcome"]);

	// profile spam
	register_plugin_hook('action', 'profile/edit', 'customizations_profile_filter');

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

/**
 * Is this a new user
 * @return bool
 */
function customizations_is_new_user() {
	$user = get_loggedin_user();

	// 2 days
	$cutoff = time() - 2 * 24 * 60 * 60;
	if ($user->getTimeCreated() > $cutoff) {
		return true;
	} else {
		return false;
	}
}

/**
 * Remove some add links for new users
 */
function customizations_remove_add_links() {
	if (isloggedin()) {
		if (customizations_is_new_user()) {
			// remove bookmark links
			remove_submenu_item(elgg_echo('bookmarks:add'));
			remove_submenu_item(elgg_echo('bookmarks:bookmarklet'));
			remove_submenu_item(elgg_echo('bookmarks:bookmarklet:group'));

			// pages links
			remove_submenu_item(elgg_echo('pages:new'), 'pagesactions');
			remove_submenu_item(elgg_echo('pages:welcome'), 'pagesactions');
		}
	}
}

/**
 * Catch new users trying to post content before allowed
 */
function customizations_stop_add() {
	if (customizations_is_new_user()) {
		// spammer tried to directly hit the action
		ban_user(get_loggedin_userid(), 'tried to post content before allowed');
		return false;
	}
}

/**
 * Filter profile fields by blacklist
 */
function customizations_profile_filter() {
	$blacklist = get_plugin_setting('blacklist', 'community_customizations');
	$blacklist = explode(",", $blacklist);
	$blacklist = array_map('trim', $blacklist);

	foreach ($_REQUEST as $key => $value) {
		if (is_string($value)) {
			foreach ($blacklist as $word) {
				if (stripos($value, $word) !== false) {
					ban_user(get_loggedin_userid(), "used '$word' on profile");
					return false;
				}
			}
		}
	}
}

/**
 * Poor design requires us to override the pages plugin's page handler just to
 * remove the "add welcome message" link on the owner page. People are using it
 * for spam so we're just going to remove it
 */
function customizations_pages_page_handler($page) {
	global $CONFIG;

	if (isset($page[0])) {
		// See what context we're using
		switch ($page[0]) {
			case "new":
				include($CONFIG->pluginspath . "pages/new.php");
				break;
			case "welcome":
				if (isset($page[1])) {
					set_input('username', $page[1]);
				}
				include($CONFIG->pluginspath . "pages/welcome.php");
				break;
			case "all":
				include($CONFIG->pluginspath . "pages/world.php");
				break;
			case "owned":
				// Owned by a user
				if (isset($page[1])) {
					set_input('username', $page[1]);
				}

				include($CONFIG->pluginspath . "community_customizations/pages/pages_owner_page.php");
				break;
			case "edit":
				if (isset($page[1])) {
					$guid = (int) $page[1];
					set_input('page_guid', $guid);

					$entity = get_entity($guid);
					add_submenu_item(elgg_echo('pages:label:view'), $CONFIG->url . "pg/pages/view/$guid", 'pageslinks');
					// add_submenu_item(elgg_echo('pages:user'), $CONFIG->wwwroot . "pg/pages/owned/" . $_SESSION['user']->username, 'pageslinksgeneral');
					if (($entity) && ($entity->canEdit())) {
						add_submenu_item(elgg_echo('pages:label:edit'), $CONFIG->url . "pg/pages/edit/$guid", 'pagesactions');
					}
					add_submenu_item(elgg_echo('pages:label:history'), $CONFIG->url . "pg/pages/history/$guid", 'pageslinks');
				}

				include($CONFIG->pluginspath . "pages/edit.php");
				break;
			case "view":

				if (isset($page[1])) {
					$guid = (int) $page[1];
					set_input('page_guid', $guid);

					elgg_extend_view('metatags', 'pages/metatags');

					$entity = get_entity($guid);
					//add_submenu_item(elgg_echo('pages:label:view'), $CONFIG->url . "pg/pages/view/$guid", 'pageslinks');
					if (($entity) && ($entity->canEdit())) {
						add_submenu_item(elgg_echo('pages:label:edit'), $CONFIG->url . "pg/pages/edit/$guid", 'pagesactions');
					}
					add_submenu_item(elgg_echo('pages:label:history'), $CONFIG->url . "pg/pages/history/$guid", 'pageslinks');
				}

				include($CONFIG->pluginspath . "pages/view.php");
				break;
			case "history":
				if (isset($page[1])) {
					$guid = (int) $page[1];
					set_input('page_guid', $guid);

					elgg_extend_view('metatags', 'pages/metatags');

					$entity = get_entity($guid);
					add_submenu_item(elgg_echo('pages:label:view'), $CONFIG->url . "pg/pages/view/$guid", 'pageslinks');
					if (($entity) && ($entity->canEdit())) {
						add_submenu_item(elgg_echo('pages:label:edit'), $CONFIG->url . "pg/pages/edit/$guid", 'pagesactions');
					}
					add_submenu_item(elgg_echo('pages:label:history'), $CONFIG->url . "pg/pages/history/$guid", 'pageslinks');
				}

				include($CONFIG->pluginspath . "pages/history.php");
				break;
			default:
				include($CONFIG->pluginspath . "pages/new.php");
				break;
		}
	}
}
