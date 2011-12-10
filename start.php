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

	register_elgg_event_handler('delete', 'user', 'customizations_purge_messages');

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

	// do not want the pages link in hover menu
	elgg_unextend_view('profile/menu/links', 'pages/menu');

	// move bookmarks to left column on groups profile
	elgg_unextend_view('groups/right_column', 'bookmarks/groupprofile_bookmarks');
	elgg_extend_view('groups/left_column', 'bookmarks/groupprofile_bookmarks');

	$action_path = "{$CONFIG->pluginspath}community_customizations/actions";
	register_action('comment/edit', FALSE, "$action_path/edit_comment.php", TRUE);
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
			elgg_unextend_view('owner_block/extend', 'bookmarks/owner_block');

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

/**
 * Delete messages from a user who is being deleted
 *
 * @param string   $event
 * @param string   $type
 * @param ElggUser $user
 */
function customizations_purge_messages($event, $type, $user) {

	// make sure we delete them all
	$entity_disable_override = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	$messages = elgg_get_entities_from_metadata(array(
		'type' => 'object',
		'subtype' => 'messages',
		'metadata_name' => 'fromId',
		'metadata_value' => $user->getGUID(),
	));
	if ($messages) {
		foreach ($messages as $e) {
			$e->delete();
		}
	}

	access_show_hidden_entities($entity_disable_override);
}
