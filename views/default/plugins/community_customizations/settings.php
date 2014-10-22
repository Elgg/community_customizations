<?php

$dbprefix = elgg_get_config('dbprefix');
$count = elgg_get_entities(array(
	'type' => 'object',
	'subtype' => 'comment',
	'joins' => array(
		"JOIN {$dbprefix}entities e2 ON e.container_guid = e2.guid"
	),
	'wheres' => array(
		"e.enabled = 'yes' AND e2.enabled = 'no'"
	),
	'count' => true
));
		
$link = elgg_view('output/confirmlink', array(
	'text' => elgg_echo('customizations:comments:fix'),
	'href' => 'action/comments/disable',
	'is_action' => true,
	'class' => 'elgg-button elgg-button-action'
));
		
if ($count) {
	echo elgg_echo('customizations:comments:disable:fix', array($count, $link));
	
	echo '<br><br>';
}
		