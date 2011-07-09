<?php
/**
 * Display a way to send a private message to user instead of messageboard
 */

if (!isloggedin()) {
	echo '<div class="contentWrapper">';
	echo elgg_echo('customizations:pm:logged_out');
	echo '<div>';
	return true;
}

$owner = get_entity(page_owner());
$viewer = get_loggedin_user();

$body = elgg_view('input/plaintext', array(
	'internalname' => 'message',
));

$body .= elgg_view('input/hidden', array(
	'internalname' => 'title',
	'value' => sprintf(elgg_echo('customizations:pm:subject'), $viewer->name),
));

$body .= elgg_view('input/hidden', array(
	'internalname' => 'send_to',
	'value' => $owner->getGUID(),
));

$body .= elgg_view('input/hidden', array(
	'internalname' => 'pm_widget',
	'value' => true,
));

$body .= elgg_view('input/submit', array(
	'value' => elgg_echo('messages:fly'),
));

echo '<div class="contentWrapper">';
echo elgg_view('input/form', array(
	'action' => $vars['url'] . 'action/messages/send',
	'body' => $body,
	'internalid' => 'pm_widget',
));
echo '</div>';
