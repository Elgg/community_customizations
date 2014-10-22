<?php

access_show_hidden_entities(true);

$dbprefix = elgg_get_config('dbprefix');
$options = array(
	'type' => 'object',
	'subtype' => 'comment',
	'joins' => array(
		"JOIN {$dbprefix}entities e2 ON e.container_guid = e2.guid"
	),
	'wheres' => array(
		"e.enabled = 'yes' AND e2.enabled = 'no'"
	),
	'limit' => false
);
	
$batch = new ElggBatch('elgg_get_entities', $options, '', 50, false);

foreach ($batch as $comment) {
	$comment->disable();
}

system_message(elgg_echo('customizations:comments:fixed'));