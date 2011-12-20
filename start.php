<?php
/**
 * General customizations for the Elgg community site
 *
 */

elgg_register_event_handler('init', 'system', 'customizations_init');

function customizations_init() {
	global $CONFIG;

	elgg_extend_view('css', 'customizations/css');

	unexpose_function('auth.gettoken');

	// turn off site notifications for performance reasons
	unregister_notification_handler('site');

	elgg_register_event_handler('delete', 'user', 'customizations_purge_messages');

	// convert messageboard to private message interface
	elgg_register_widget_type('messageboard', elgg_echo("customizations:widget:pm"), elgg_echo("customizations:widget:pm:desc"), "profile");
	elgg_register_plugin_hook_handler('forward', 'system', 'customizations_pm_forward');

	// limit access to the add links
	elgg_register_event_handler('pagesetup', 'system', 'customizations_remove_add_links');
	elgg_register_plugin_hook_handler('action', 'bookmarks/add', 'customizations_stop_add');
	elgg_register_plugin_hook_handler('action', 'pages/edit', 'customizations_stop_add');

	// do not want the pages link in hover menu
	elgg_unextend_view('profile/menu/links', 'pages/menu');

	// move bookmarks to left column on groups profile
	elgg_unextend_view('groups/right_column', 'bookmarks/groupprofile_bookmarks');
	elgg_extend_view('groups/left_column', 'bookmarks/groupprofile_bookmarks');

	$action_path = "{$CONFIG->pluginspath}community_customizations/actions";
	elgg_register_action('comment/edit', "$action_path/edit_comment.php", 'admin');
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
	$user = elgg_get_logged_in_user_entity();

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
	if (elgg_is_logged_in()) {
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
		ban_user(elgg_get_logged_in_user_guid(), 'tried to post content before allowed');
		return false;
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
