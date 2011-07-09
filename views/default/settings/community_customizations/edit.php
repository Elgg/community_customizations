<?php
/**
 * Community customization plugin settings
 */

$msg_limit = get_plugin_setting('msg_limit', 'community_customizations');

echo '<label>' . elgg_echo('customizations:msg_limit') . ':</label>';
echo elgg_view('input/text', array(
	'internalname' => 'params[msg_limit]',
	'value' => $msg_limit,
));

$blacklist = get_plugin_setting('blacklist', 'community_customizations');

echo '<label>' . elgg_echo('customizations:blacklist') . ':</label>';
echo elgg_view('input/plaintext', array(
	'internalname' => 'params[blacklist]',
	'value' => $blacklist,
));
