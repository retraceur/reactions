<?php

/**
 * Sets the cookies used to store an unauthenticated commentator's identity. Typically used
 * to recall previous comments by this commentator that are still held in moderation.
 *
 * @since WP 3.4.0
 * @since WP 4.9.6 The `$cookies_consent` parameter was added.
 *
 * @param WP_Comment $comment         Comment object.
 * @param WP_User    $user            Comment author's user object. The user may not exist.
 * @param bool       $cookies_consent Optional. Comment author's consent to store cookies. Default true.
 */
function wp_set_comment_cookies( $comment, $user, $cookies_consent = true ) {
	// If the user already exists, or the user opted out of cookies, don't set cookies.
	if ( $user->exists() ) {
		return;
	}

	if ( false === $cookies_consent ) {
		// Remove any existing cookies.
		$past = time() - YEAR_IN_SECONDS;
		setcookie( 'comment_author_' . COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'comment_author_email_' . COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'comment_author_url_' . COOKIEHASH, ' ', $past, COOKIEPATH, COOKIE_DOMAIN );

		return;
	}

	/**
	 * Filters the lifetime of the comment cookie in seconds.
	 *
	 * @since WP 2.8.0
	 * @since WP 6.6.0 The default $seconds value changed from 30000000 to YEAR_IN_SECONDS.
	 *
	 * @param int $seconds Comment cookie lifetime. Default YEAR_IN_SECONDS.
	 */
	$comment_cookie_lifetime = time() + apply_filters( 'comment_cookie_lifetime', YEAR_IN_SECONDS );

	$secure = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );

	setcookie( 'comment_author_' . COOKIEHASH, $comment->comment_author, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	setcookie( 'comment_author_email_' . COOKIEHASH, $comment->comment_author_email, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	setcookie( 'comment_author_url_' . COOKIEHASH, esc_url( $comment->comment_author_url ), $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
}

/**
 * Sanitizes the cookies sent to the user already.
 *
 * Will only do anything if the cookies have already been created for the user.
 * Mostly used after cookies had been sent to use elsewhere.
 *
 * @since WP 2.0.4
 */
function sanitize_comment_cookies() {
	if ( isset( $_COOKIE[ 'comment_author_' . COOKIEHASH ] ) ) {
		/**
		 * Filters the comment author's name cookie before it is set.
		 *
		 * When this filter hook is evaluated in wp_filter_comment(),
		 * the comment author's name string is passed.
		 *
		 * @since WP 1.5.0
		 *
		 * @param string $author_cookie The comment author name cookie.
		 */
		$comment_author = apply_filters( 'pre_comment_author_name', $_COOKIE[ 'comment_author_' . COOKIEHASH ] );
		$comment_author = wp_unslash( $comment_author );
		$comment_author = esc_attr( $comment_author );

		$_COOKIE[ 'comment_author_' . COOKIEHASH ] = $comment_author;
	}

	if ( isset( $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] ) ) {
		/**
		 * Filters the comment author's email cookie before it is set.
		 *
		 * When this filter hook is evaluated in wp_filter_comment(),
		 * the comment author's email string is passed.
		 *
		 * @since WP 1.5.0
		 *
		 * @param string $author_email_cookie The comment author email cookie.
		 */
		$comment_author_email = apply_filters( 'pre_comment_author_email', $_COOKIE[ 'comment_author_email_' . COOKIEHASH ] );
		$comment_author_email = wp_unslash( $comment_author_email );
		$comment_author_email = esc_attr( $comment_author_email );

		$_COOKIE[ 'comment_author_email_' . COOKIEHASH ] = $comment_author_email;
	}

	if ( isset( $_COOKIE[ 'comment_author_url_' . COOKIEHASH ] ) ) {
		/**
		 * Filters the comment author's URL cookie before it is set.
		 *
		 * When this filter hook is evaluated in wp_filter_comment(),
		 * the comment author's URL string is passed.
		 *
		 * @since WP 1.5.0
		 *
		 * @param string $author_url_cookie The comment author URL cookie.
		 */
		$comment_author_url = apply_filters( 'pre_comment_author_url', $_COOKIE[ 'comment_author_url_' . COOKIEHASH ] );
		$comment_author_url = wp_unslash( $comment_author_url );

		$_COOKIE[ 'comment_author_url_' . COOKIEHASH ] = $comment_author_url;
	}
}

/**
 * Prints the necessary markup for the embed comments button.
 *
 * @since WP 4.4.0
 */
function print_embed_comments_button() {
	if ( is_404() || ! ( get_comments_number() || comments_open() ) ) {
		return;
	}
	?>
	<div class="wp-embed-comments">
		<a href="<?php comments_link(); ?>" target="_top">
			<span class="dashicons dashicons-admin-comments"></span>
			<?php
			printf(
				/* translators: %s: Number of comments. */
				_n(
					'%s <span class="screen-reader-text">Comment</span>',
					'%s <span class="screen-reader-text">Comments</span>',
					get_comments_number()
				),
				number_format_i18n( get_comments_number() )
			);
			?>
		</a>
	</div>
	<?php
}

/**
 * Registers the personal data exporter for comments.
 *
 * @since WP 4.9.6
 *
 * @param array[] $exporters An array of personal data exporters.
 * @return array[] An array of personal data exporters.
 */
function wp_register_comment_personal_data_exporter( $exporters ) {
	$exporters['wordpress-comments'] = array(
		'exporter_friendly_name' => __( 'WordPress Comments' ),
		'callback'               => 'wp_comments_personal_data_exporter',
	);

	return $exporters;
}

/**
 * Registers the personal data eraser for comments.
 *
 * @since WP 4.9.6
 *
 * @param array $erasers An array of personal data erasers.
 * @return array An array of personal data erasers.
 */
function wp_register_comment_personal_data_eraser( $erasers ) {
	$erasers['wordpress-comments'] = array(
		'eraser_friendly_name' => __( 'WordPress Comments' ),
		'callback'             => 'wp_comments_personal_data_eraser',
	);

	return $erasers;
}

/**
 * Calculates the total number of comment pages.
 *
 * @since WP 2.7.0
 *
 * @uses Walker_Comment
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param WP_Comment[] $comments Optional. Array of WP_Comment objects. Defaults to `$wp_query->comments`.
 * @param int          $per_page Optional. Comments per page. Defaults to the value of `comments_per_page`
 *                               query var, option of the same name, or 1 (in that order).
 * @param bool         $threaded Optional. Control over flat or threaded comments. Defaults to the value
 *                               of `thread_comments` option.
 * @return int Number of comment pages.
 */
function get_comment_pages_count( $comments = null, $per_page = null, $threaded = null ) {
	global $wp_query;

	if ( null === $comments && null === $per_page && null === $threaded && ! empty( $wp_query->max_num_comment_pages ) ) {
		return $wp_query->max_num_comment_pages;
	}

	if ( ( ! $comments || ! is_array( $comments ) ) && ! empty( $wp_query->comments ) ) {
		$comments = $wp_query->comments;
	}

	if ( empty( $comments ) ) {
		return 0;
	}

	if ( ! get_option( 'page_comments' ) ) {
		return 1;
	}

	if ( ! isset( $per_page ) ) {
		$per_page = (int) get_query_var( 'comments_per_page' );
	}
	if ( 0 === $per_page ) {
		$per_page = (int) get_option( 'comments_per_page' );
	}
	if ( 0 === $per_page ) {
		return 1;
	}

	if ( ! isset( $threaded ) ) {
		$threaded = get_option( 'thread_comments' );
	}

	if ( $threaded ) {
		$walker = new Walker_Comment();
		$count  = ceil( $walker->get_number_of_root_elements( $comments ) / $per_page );
	} else {
		$count = ceil( count( $comments ) / $per_page );
	}

	return (int) $count;
}

/**
 * Retrieves comment data given a comment ID or comment object.
 *
 * If an object is passed then the comment data will be cached and then returned
 * after being passed through a filter. If the comment is empty, then the global
 * comment variable will be used, if it is set.
 *
 * @since WP 2.0.0
 *
 * @global WP_Comment $comment Global comment object.
 *
 * @param WP_Comment|string|int $comment Comment to retrieve.
 * @param string                $output  Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                                       correspond to a WP_Comment object, an associative array, or a numeric array,
 *                                       respectively. Default OBJECT.
 * @return WP_Comment|array|null Depends on $output value.
 */
function get_comment( $comment = null, $output = OBJECT ) {
	if ( empty( $comment ) && isset( $GLOBALS['comment'] ) ) {
		$comment = $GLOBALS['comment'];
	}

	if ( $comment instanceof WP_Comment ) {
		$_comment = $comment;
	} elseif ( is_object( $comment ) ) {
		$_comment = new WP_Comment( $comment );
	} else {
		$_comment = WP_Comment::get_instance( $comment );
	}

	if ( ! $_comment ) {
		return null;
	}

	/**
	 * Fires after a comment is retrieved.
	 *
	 * @since WP 2.3.0
	 *
	 * @param WP_Comment $_comment Comment data.
	 */
	$_comment = apply_filters( 'get_comment', $_comment );

	if ( OBJECT === $output ) {
		return $_comment;
	} elseif ( ARRAY_A === $output ) {
		return $_comment->to_array();
	} elseif ( ARRAY_N === $output ) {
		return array_values( $_comment->to_array() );
	}
	return $_comment;
}

/**
 * Retrieves the approved comments for a post.
 *
 * @since WP 2.0.0
 * @since WP 4.1.0 Refactored to leverage WP_Comment_Query over a direct query.
 *
 * @param int   $post_id The ID of the post.
 * @param array $args    {
 *     Optional. See WP_Comment_Query::__construct() for information on accepted arguments.
 *
 *     @type int    $status  Comment status to limit results by. Defaults to approved comments.
 *     @type int    $post_id Limit results to those affiliated with a given post ID.
 *     @type string $order   How to order retrieved comments. Default 'ASC'.
 * }
 * @return WP_Comment[]|int[]|int The approved comments, or number of comments if `$count`
 *                                argument is true.
 */
function get_approved_comments( $post_id, $args = array() ) {
	if ( ! $post_id ) {
		return array();
	}

	$defaults    = array(
		'status'  => 1,
		'post_id' => $post_id,
		'order'   => 'ASC',
	);
	$parsed_args = wp_parse_args( $args, $defaults );

	$query = new WP_Comment_Query();
	return $query->query( $parsed_args );
}

/**
 * Retrieves a list of comments.
 *
 * The comment list can be for the blog as a whole or for an individual post.
 *
 * @since WP 2.7.0
 *
 * @param string|array $args Optional. Array or string of arguments. See WP_Comment_Query::__construct()
 *                           for information on accepted arguments. Default empty string.
 * @return WP_Comment[]|int[]|int List of comments or number of found comments if `$count` argument is true.
 */
function get_comments( $args = '' ) {
	$query = new WP_Comment_Query();
	return $query->query( $args );
}

/**
 * Calculates what page number a comment will appear on for comment paging.
 *
 * @since WP 2.7.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int   $comment_id Comment ID.
 * @param array $args {
 *     Array of optional arguments.
 *
 *     @type string     $type      Limit paginated comments to those matching a given type.
 *                                 Accepts 'comment', 'trackback', 'pingback', 'pings'
 *                                 (trackbacks and pingbacks), or 'all'. Default 'all'.
 *     @type int        $per_page  Per-page count to use when calculating pagination.
 *                                 Defaults to the value of the 'comments_per_page' option.
 *     @type int|string $max_depth If greater than 1, comment page will be determined
 *                                 for the top-level parent `$comment_id`.
 *                                 Defaults to the value of the 'thread_comments_depth' option.
 * }
 * @return int|null Comment page number or null on error.
 */
function get_page_of_comment( $comment_id, $args = array() ) {
	global $wpdb;

	$page = null;

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}

	$defaults      = array(
		'type'      => 'all',
		'page'      => '',
		'per_page'  => '',
		'max_depth' => '',
	);
	$args          = wp_parse_args( $args, $defaults );
	$original_args = $args;

	// Order of precedence: 1. `$args['per_page']`, 2. 'comments_per_page' query_var, 3. 'comments_per_page' option.
	if ( get_option( 'page_comments' ) ) {
		if ( '' === $args['per_page'] ) {
			$args['per_page'] = get_query_var( 'comments_per_page' );
		}

		if ( '' === $args['per_page'] ) {
			$args['per_page'] = get_option( 'comments_per_page' );
		}
	}

	if ( empty( $args['per_page'] ) ) {
		$args['per_page'] = 0;
		$args['page']     = 0;
	}

	if ( $args['per_page'] < 1 ) {
		$page = 1;
	}

	if ( null === $page ) {
		if ( '' === $args['max_depth'] ) {
			if ( get_option( 'thread_comments' ) ) {
				$args['max_depth'] = get_option( 'thread_comments_depth' );
			} else {
				$args['max_depth'] = -1;
			}
		}

		// Find this comment's top-level parent if threading is enabled.
		if ( $args['max_depth'] > 1 && 0 != $comment->comment_parent ) {
			return get_page_of_comment( $comment->comment_parent, $args );
		}

		$comment_args = array(
			'type'       => $args['type'],
			'post_id'    => $comment->comment_post_ID,
			'fields'     => 'ids',
			'count'      => true,
			'status'     => 'approve',
			'orderby'    => 'none',
			'parent'     => 0,
			'date_query' => array(
				array(
					'column' => "$wpdb->comments.comment_date_gmt",
					'before' => $comment->comment_date_gmt,
				),
			),
		);

		if ( is_user_logged_in() ) {
			$comment_args['include_unapproved'] = array( get_current_user_id() );
		} else {
			$unapproved_email = wp_get_unapproved_comment_author_email();

			if ( $unapproved_email ) {
				$comment_args['include_unapproved'] = array( $unapproved_email );
			}
		}

		/**
		 * Filters the arguments used to query comments in get_page_of_comment().
		 *
		 * @since WP 5.5.0
		 *
		 * @see WP_Comment_Query::__construct()
		 *
		 * @param array $comment_args {
		 *     Array of WP_Comment_Query arguments.
		 *
		 *     @type string $type               Limit paginated comments to those matching a given type.
		 *                                      Accepts 'comment', 'trackback', 'pingback', 'pings'
		 *                                      (trackbacks and pingbacks), or 'all'. Default 'all'.
		 *     @type int    $post_id            ID of the post.
		 *     @type string $fields             Comment fields to return.
		 *     @type bool   $count              Whether to return a comment count (true) or array
		 *                                      of comment objects (false).
		 *     @type string $status             Comment status.
		 *     @type int    $parent             Parent ID of comment to retrieve children of.
		 *     @type array  $date_query         Date query clauses to limit comments by. See WP_Date_Query.
		 *     @type array  $include_unapproved Array of IDs or email addresses whose unapproved comments
		 *                                      will be included in paginated comments.
		 * }
		 */
		$comment_args = apply_filters( 'get_page_of_comment_query_args', $comment_args );

		$comment_query       = new WP_Comment_Query();
		$older_comment_count = $comment_query->query( $comment_args );

		// No older comments? Then it's page #1.
		if ( 0 == $older_comment_count ) {
			$page = 1;

			// Divide comments older than this one by comments per page to get this comment's page number.
		} else {
			$page = (int) ceil( ( $older_comment_count + 1 ) / $args['per_page'] );
		}
	}

	/**
	 * Filters the calculated page on which a comment appears.
	 *
	 * @since WP 4.4.0
	 * @since WP 4.7.0 Introduced the `$comment_id` parameter.
	 *
	 * @param int   $page          Comment page.
	 * @param array $args {
	 *     Arguments used to calculate pagination. These include arguments auto-detected by the function,
	 *     based on query vars, system settings, etc. For pristine arguments passed to the function,
	 *     see `$original_args`.
	 *
	 *     @type string $type      Type of comments to count.
	 *     @type int    $page      Calculated current page.
	 *     @type int    $per_page  Calculated number of comments per page.
	 *     @type int    $max_depth Maximum comment threading depth allowed.
	 * }
	 * @param array $original_args {
	 *     Array of arguments passed to the function. Some or all of these may not be set.
	 *
	 *     @type string $type      Type of comments to count.
	 *     @type int    $page      Current comment page.
	 *     @type int    $per_page  Number of comments per page.
	 *     @type int    $max_depth Maximum comment threading depth allowed.
	 * }
	 * @param int $comment_id ID of the comment.
	 */
	return apply_filters( 'get_page_of_comment', (int) $page, $args, $original_args, $comment_id );
}

function retraceur_reaction_unapproved_headers( $headers ) {
	// Unmoderated comments are only visible for 10 minutes via the moderation hash.
	$expires = 10 * MINUTE_IN_SECONDS;

	$headers['Expires']       = gmdate( $date_format, time() + $expires );
	$headers['Cache-Control'] = sprintf(
		'max-age=%d, must-revalidate',
		$expires
	);

	return $headers;
}
