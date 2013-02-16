<?php
/**
 * List replies with optional add form
 *
 * @mod If not showing the form because they can't reply, show a message why.
 *
 * @uses $vars['entity']        ElggEntity
 * @uses $vars['show_add_form'] Display add form or not
 */

$show_add_form = elgg_extract('show_add_form', $vars, true);
$topic = elgg_extract('entity', $vars);

echo '<div id="group-replies" class="mtl">';

$options = array(
	'guid' => $vars['entity']->getGUID(),
	'annotation_name' => 'group_topic_post',
);
$html = elgg_list_annotations($options);
if ($html) {
	echo '<h3>' . elgg_echo('group:replies') . '</h3>';
	echo $html;
}

if ($show_add_form) {
	$form_vars = array('class' => 'mtm');
	echo elgg_view_form('discussion/reply/save', $form_vars, $vars);
} elseif ($topic->status != 'closed') {
	$group = $topic->getContainerEntity();

	// if not a member
	if (!elgg_is_logged_in()) {
		$log_in = elgg_view('output/url', array(
			'href' => '/login',
			'text' => 'log in'
		));

		echo "You must $log_in to post replies.";
	} elseif (!$group->isMember()) {
		// @todo override action to redirect back to thread.
		$url = current_page_url();
		$join_group = elgg_view('output/confirmlink', array(
			'href' => '/action/groups/join?group_guid=' . $group->getGUID(),
			'text' => 'join this group',
			'confirm' => 'Join this group?'
		));

		echo "You must $join_group to post replies.";
	}
}

echo '</div>';
