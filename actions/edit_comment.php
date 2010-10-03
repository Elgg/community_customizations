<?php

$annotation_id = get_input("annotation_id");
$comment = get_input("postComment{$annotation_id}");

$annotation = get_annotation($annotation_id);
$comment_owner = $annotation->owner_guid;
$access_id = $annotation->access_id;

if ($annotation) {
	update_annotation($annotation_id, "generic_comment", $comment, "", $comment_owner, $access_id);
	system_message(elgg_echo("customizations:comment:edit:success"));
} else {
	system_message(elgg_echo("customizations:comment:edit:error"));
}

forward(REFERER);
