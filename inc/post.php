<?php

function retraceur_reaction_initial_post_types_creation( $args ) {
	$args['supports']['editor'] = array( 'notes' => true );

	return $args;
}
add_filter( 'register_post_post_type_args', 'retraceur_reaction_initial_post_types_creation' );
add_filter( 'register_page_post_type_args', 'retraceur_reaction_initial_post_types_creation' );

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

/**
 * Adds a URL to those already pinged.
 *
 * @since WP 1.5.0
 * @since WP 4.7.0 `$post` can be a WP_Post object.
 * @since WP 4.7.0 `$uri` can be an array of URIs.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|WP_Post  $post Post ID or post object.
 * @param string|array $uri  Ping URI or array of URIs.
 * @return int|false How many rows were updated.
 */
function add_ping( $post, $uri ) {
	global $wpdb;

	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$pung = trim( $post->pinged );
	$pung = preg_split( '/\s/', $pung );

	if ( is_array( $uri ) ) {
		$pung = array_merge( $pung, $uri );
	} else {
		$pung[] = $uri;
	}
	$new = implode( "\n", $pung );

	/**
	 * Filters the new ping URL to add for the given post.
	 *
	 * @since WP 2.0.0
	 *
	 * @param string $new New ping URL to add.
	 */
	$new = apply_filters( 'add_ping', $new );

	$return = $wpdb->update( $wpdb->posts, array( 'pinged' => $new ), array( 'ID' => $post->ID ) );
	clean_post_cache( $post->ID );
	return $return;
}

/**
 * Retrieves URLs already pinged for a post.
 *
 * @since WP 1.5.0
 *
 * @since WP 4.7.0 `$post` can be a WP_Post object.
 *
 * @param int|WP_Post $post Post ID or object.
 * @return string[]|false Array of URLs already pinged for the given post, false if the post is not found.
 */
function get_pung( $post ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	$pung = trim( $post->pinged );
	$pung = preg_split( '/\s/', $pung );

	/**
	 * Filters the list of already-pinged URLs for the given post.
	 *
	 * @since WP 2.0.0
	 *
	 * @param string[] $pung Array of URLs already pinged for the given post.
	 */
	return apply_filters( 'get_pung', $pung );
}

/**
 * Hook to schedule pings and enclosures when a post is published.
 *
 * Uses WP_IMPORTING constants.
 *
 * @since WP 2.3.0
 * @access private
 *
 * @param int $post_id The ID of the post being published.
 */
function _publish_post_hook( $post_id ) {
	if ( defined( 'WP_IMPORTING' ) ) {
		return;
	}

	if ( get_option( 'default_pingback_flag' ) ) {
		add_post_meta( $post_id, '_pingme', '1', true );
	}
	add_post_meta( $post_id, '_encloseme', '1', true );

	$to_ping = get_to_ping( $post_id );
	if ( ! empty( $to_ping ) ) {
		add_post_meta( $post_id, '_trackbackme', '1' );
	}

	if ( ! wp_next_scheduled( 'do_pings' ) ) {
		wp_schedule_single_event( time(), 'do_pings' );
	}
}
