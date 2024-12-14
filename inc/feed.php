<?php

/**
 * Outputs the link to the comments for the current post in an XML safe way.
 *
 * @since WP 3.0.0
 */
function comments_link_feed() {
	/**
	 * Filters the comments permalink for the current post.
	 *
	 * @since WP 3.6.0
	 *
	 * @param string $comment_permalink The current comment permalink with
	 *                                  '#comments' appended.
	 */
	echo esc_url( apply_filters( 'comments_link_feed', get_comments_link() ) );
}

/**
 * Displays the feed GUID for the current comment.
 *
 * @since WP 2.5.0
 *
 * @param int|WP_Comment $comment_id Optional comment object or ID. Defaults to global comment object.
 */
function comment_guid( $comment_id = null ) {
	echo esc_url( get_comment_guid( $comment_id ) );
}

/**
 * Retrieves the feed GUID for the current comment.
 *
 * @since WP 2.5.0
 *
 * @param int|WP_Comment $comment_id Optional comment object or ID. Defaults to global comment object.
 * @return string|false GUID for comment on success, false on failure.
 */
function get_comment_guid( $comment_id = null ) {
	$comment = get_comment( $comment_id );

	if ( ! is_object( $comment ) ) {
		return false;
	}

	return get_the_guid( $comment->comment_post_ID ) . '#comment-' . $comment->comment_ID;
}

/**
 * Displays the link to the comments.
 *
 * @since WP 1.5.0
 * @since WP 4.4.0 Introduced the `$comment` argument.
 *
 * @param int|WP_Comment $comment Optional. Comment object or ID. Defaults to global comment object.
 */
function comment_link( $comment = null ) {
	/**
	 * Filters the current comment's permalink.
	 *
	 * @since WP 3.6.0
	 *
	 * @see get_comment_link()
	 *
	 * @param string $comment_permalink The current comment permalink.
	 */
	echo esc_url( apply_filters( 'comment_link', get_comment_link( $comment ) ) );
}

/**
 * Retrieves the current comment author for use in the feeds.
 *
 * @since WP 2.0.0
 *
 * @return string Comment Author.
 */
function get_comment_author_rss() {
	/**
	 * Filters the current comment author for use in a feed.
	 *
	 * @since WP 1.5.0
	 *
	 * @see get_comment_author()
	 *
	 * @param string $comment_author The current comment author.
	 */
	return apply_filters( 'comment_author_rss', get_comment_author() );
}

/**
 * Displays the current comment author in the feed.
 *
 * @since WP 1.0.0
 */
function comment_author_rss() {
	echo get_comment_author_rss();
}

/**
 * Displays the current comment content for use in the feeds.
 *
 * @since WP 1.0.0
 */
function comment_text_rss() {
	$comment_text = get_comment_text();
	/**
	 * Filters the current comment content for use in a feed.
	 *
	 * @since WP 1.5.0
	 *
	 * @param string $comment_text The content of the current comment.
	 */
	$comment_text = apply_filters( 'comment_text_rss', $comment_text );
	echo $comment_text;
}
