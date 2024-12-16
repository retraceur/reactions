<?php

/**
 * Retrieves the permalink for the post comments feed.
 *
 * @since WP 2.2.0
 *
 * @param int    $post_id Optional. Post ID. Default is the ID of the global `$post`.
 * @param string $feed    Optional. Feed type. Possible values include 'rss2', 'atom'.
 *                        Default is the value of get_default_feed().
 * @return string The permalink for the comments feed for the given post on success, empty string on failure.
 */
function get_post_comments_feed_link( $post_id = 0, $feed = '' ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	$post = get_post( $post_id );

	// Bail out if the post does not exist.
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$unattached = 'attachment' === $post->post_type && 0 === (int) $post->post_parent;

	if ( get_option( 'permalink_structure' ) ) {
		if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post_id ) {
			$url = _get_page_link( $post_id );
		} else {
			$url = get_permalink( $post_id );
		}

		if ( $unattached ) {
			$url = home_url( '/feed/' );
			if ( get_default_feed() !== $feed ) {
				$url .= "$feed/";
			}
			$url = add_query_arg( 'attachment_id', $post_id, $url );
		} else {
			$url = trailingslashit( $url ) . 'feed';
			if ( get_default_feed() !== $feed ) {
				$url .= "/$feed";
			}
			$url = user_trailingslashit( $url, 'single_feed' );
		}
	} else {
		if ( $unattached ) {
			$url = add_query_arg(
				array(
					'feed'          => $feed,
					'attachment_id' => $post_id,
				),
				home_url( '/' )
			);
		} elseif ( 'page' === $post->post_type ) {
			$url = add_query_arg(
				array(
					'feed'    => $feed,
					'page_id' => $post_id,
				),
				home_url( '/' )
			);
		} else {
			$url = add_query_arg(
				array(
					'feed' => $feed,
					'p'    => $post_id,
				),
				home_url( '/' )
			);
		}
	}

	/**
	 * Filters the post comments feed permalink.
	 *
	 * @since WP 1.5.1
	 *
	 * @param string $url Post comments feed permalink.
	 */
	return apply_filters( 'post_comments_feed_link', $url );
}

/**
 * Displays the comment feed link for a post.
 *
 * Prints out the comment feed link for a post. Link text is placed in the
 * anchor. If no link text is specified, default text is used. If no post ID is
 * specified, the current post is used.
 *
 * @since WP 2.5.0
 *
 * @param string $link_text Optional. Descriptive link text. Default 'Comments Feed'.
 * @param int    $post_id   Optional. Post ID. Default is the ID of the global `$post`.
 * @param string $feed      Optional. Feed type. Possible values include 'rss2', 'atom'.
 *                          Default is the value of get_default_feed().
 */
function post_comments_feed_link( $link_text = '', $post_id = '', $feed = '' ) {
	$url = get_post_comments_feed_link( $post_id, $feed );
	if ( empty( $link_text ) ) {
		$link_text = __( 'Comments Feed' );
	}

	$link = '<a href="' . esc_url( $url ) . '">' . $link_text . '</a>';
	/**
	 * Filters the post comment feed link anchor tag.
	 *
	 * @since WP 2.8.0
	 *
	 * @param string $link    The complete anchor tag for the comment feed link.
	 * @param int    $post_id Post ID.
	 * @param string $feed    The feed type. Possible values include 'rss2', 'atom',
	 *                        or an empty string for the default feed type.
	 */
	echo apply_filters( 'post_comments_feed_link_html', $link, $post_id, $feed );
}
