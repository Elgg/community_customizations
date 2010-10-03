<?php
/**
 * collapsible edit comment form
 */

echo "<div class=\"collapsible_box\">";

$submit_input = elgg_view('input/submit', array(
	'internalname' => 'submit',
	'value' => elgg_echo('save'))
);
$text_textarea = elgg_view('input/longtext', array(
	'internalname' => 'postComment' . $vars['annotation']->id,
	'value' => $vars['annotation']->value)
);
$field = elgg_view('input/hidden', array(
	'internalname' => 'annotation_id',
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
	'action' => "{$vars['url']}action/comment/edit",
	'body' => $form_body,
	'internalid' => 'editCommentForm')
);

echo '</div>';
