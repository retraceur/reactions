<?php

/**
 * Moves comments for a post to the Trash.
 *
 * @since WP 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return mixed|void False on failure.
 */
function wp_trash_post_comments( $post = null ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_id = $post->ID;

	/**
	 * Fires before comments are sent to the Trash.
	 *
	 * @since WP 2.9.0
	 *
	 * @param int $post_id Post ID.
	 */
	do_action( 'trash_post_comments', $post_id );

	$comments = $wpdb->get_results( $wpdb->prepare( "SELECT comment_ID, comment_approved FROM $wpdb->comments WHERE comment_post_ID = %d", $post_id ) );

	if ( ! $comments ) {
		return;
	}

	// Cache current status for each comment.
	$statuses = array();
	foreach ( $comments as $comment ) {
		$statuses[ $comment->comment_ID ] = $comment->comment_approved;
	}
	add_post_meta( $post_id, '_wp_trash_meta_comments_status', $statuses );

	// Set status for all comments to post-trashed.
	$result = $wpdb->update( $wpdb->comments, array( 'comment_approved' => 'post-trashed' ), array( 'comment_post_ID' => $post_id ) );

	clean_comment_cache( array_keys( $statuses ) );

	/**
	 * Fires after comments are sent to the Trash.
	 *
	 * @since WP 2.9.0
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $statuses Array of comment statuses.
	 */
	do_action( 'trashed_post_comments', $post_id, $statuses );

	return $result;
}

/**
 * Restores comments for a post from the Trash.
 *
 * @since WP 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return true|void
 */
function wp_untrash_post_comments( $post = null ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	$post_id = $post->ID;

	$statuses = get_post_meta( $post_id, '_wp_trash_meta_comments_status', true );

	if ( ! $statuses ) {
		return true;
	}

	/**
	 * Fires before comments are restored for a post from the Trash.
	 *
	 * @since WP 2.9.0
	 *
	 * @param int $post_id Post ID.
	 */
	do_action( 'untrash_post_comments', $post_id );

	// Restore each comment to its original status.
	$group_by_status = array();
	foreach ( $statuses as $comment_id => $comment_status ) {
		$group_by_status[ $comment_status ][] = $comment_id;
	}

	foreach ( $group_by_status as $status => $comments ) {
		// Confidence check. This shouldn't happen.
		if ( 'post-trashed' === $status ) {
			$status = '0';
		}
		$comments_in = implode( ', ', array_map( 'intval', $comments ) );
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->comments SET comment_approved = %s WHERE comment_ID IN ($comments_in)", $status ) );
	}

	clean_comment_cache( array_keys( $statuses ) );

	delete_post_meta( $post_id, '_wp_trash_meta_comments_status' );

	/**
	 * Fires after comments are restored for a post from the Trash.
	 *
	 * @since WP 2.9.0
	 *
	 * @param int $post_id Post ID.
	 */
	do_action( 'untrashed_post_comments', $post_id );
}
