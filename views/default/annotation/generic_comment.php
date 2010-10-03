<?php
/**
 * Elgg generic comment
 *
 * @package Elgg
 * @subpackage Core
 * @author Curverider Ltd
 * @link http://elgg.org/
 *
 */

$owner = get_user($vars['annotation']->owner_guid);

?>
<div class="generic_comment"><!-- start of generic_comment div -->

	<div class="generic_comment_icon">
		<?php
			echo elgg_view("profile/icon",
				array(
					'entity' => $owner,
					'size' => 'small'
				)
			);
		?>
	</div>
	<div class="generic_comment_details">

		<!-- output the actual comment -->
		<?php echo elgg_view("output/longtext",array("value" => $vars['annotation']->value)); ?>

		<p class="generic_comment_owner">
			<a href="<?php echo $owner->getURL(); ?>"><?php echo $owner->name; ?></a> <?php echo elgg_view_friendly_time($vars['annotation']->time_created); ?>
		</p>

	</div><!-- end of generic_comment_details -->
<?php

// if the user looking at the comment can edit, show the delete link
if ($vars['annotation']->canEdit()) {

?>
	<p>
<?php

	echo elgg_view("output/confirmlink",array(
		'href' => $vars['url'] . "action/comments/delete?annotation_id=" . $vars['annotation']->id,
		'text' => elgg_echo('delete'),
		'confirm' => elgg_echo('deleteconfirm'),
	));

	if (isadminloggedin()) {

		// this really should be done with css
		echo ' ';

		echo elgg_view('output/url', array(
			'class' => 'collapsibleboxlink',
			'text' => elgg_echo('edit'),
			'href' => '#',
		));
		echo elgg_view('customizations/edit_comment', array(
			'annotation' => $vars['annotation'],
		));
	}
?>
	</p>
<?php
}
?>
</div><!-- end of generic_comment div -->
