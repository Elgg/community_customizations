<?php
/**
 * collapsible edit comment form
 */

echo "<div class=\"collapsible_box\">";

$submit_input = elgg_view('input/submit', array(
	'name' => 'submit',
	'value' => elgg_echo('save'))
);
$text_textarea = elgg_view('input/longtext', array(
	'name' => 'postComment' . $vars['annotation']->id,
	'value' => $vars['annotation']->value)
);
$field = elgg_view('input/hidden', array(
	'name' => 'annotation_id',
	'value' => $vars['annotation']->id)
);

$form_body = <<<EOT

	<div class='edit_forum_comments'>
		<p class='longtext_editarea'>
			$text_textarea
		</p>
		$field
		<p>
			$submit_input
		</p>
	</div>

EOT;

echo elgg_view('input/form', array(
	'action' => "{$vars['url']}action/comments/edit",
	'body' => $form_body,
	'id' => 'editCommentForm')
);

echo '</div>';
