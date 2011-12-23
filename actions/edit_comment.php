<?php
/**
 * Save a comment after editing
 */

$annotation_id = get_input("annotation_id");
$comment = get_input("postComment{$annotation_id}");

$annotation = new ElggAnnotation($annotation_id);
$comment_owner = $annotation->getOwnerGUID();
$access_id = $annotation->access_id;

if ($annotation) {
	update_annotation($annotation_id, "generic_comment", $comment, "", $comment_owner, $access_id);
	system_message(elgg_echo("customizations:comment:edit:success"));
} else {
	system_message(elgg_echo("customizations:comment:edit:error"));
}

forward(REFERER);
