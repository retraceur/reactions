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

/**
 * Retrieves the permalink for the search results comments feed.
 *
 * @since WP 2.5.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param string $search_query Optional. Search query. Default empty.
 * @param string $feed         Optional. Feed type. Possible values include 'rss2', 'atom'.
 *                             Default is the value of get_default_feed().
 * @return string The comments feed search results permalink.
 */
function get_search_comments_feed_link( $search_query = '', $feed = '' ) {
	global $wp_rewrite;

	if ( empty( $feed ) ) {
		$feed = get_default_feed();
	}

	$link = get_search_feed_link( $search_query, $feed );

	$permastruct = $wp_rewrite->get_search_permastruct();

	if ( empty( $permastruct ) ) {
		$link = add_query_arg( 'feed', 'comments-' . $feed, $link );
	} else {
		$link = add_query_arg( 'withcomments', 1, $link );
	}

	/** This filter is documented in wp-includes/link-template.php */
	return apply_filters( 'search_feed_link', $link, $feed, 'comments' );
}

/**
 * Retrieves the comments page number link.
 *
 * @since WP 2.7.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param int $pagenum  Optional. Page number. Default 1.
 * @param int $max_page Optional. The maximum number of comment pages. Default 0.
 * @return string The comments page number link URL.
 */
function get_comments_pagenum_link( $pagenum = 1, $max_page = 0 ) {
	global $wp_rewrite;

	$pagenum  = (int) $pagenum;
	$max_page = (int) $max_page;

	$result = get_permalink();

	if ( 'newest' === get_option( 'default_comments_page' ) ) {
		if ( $pagenum !== $max_page ) {
			if ( $wp_rewrite->using_permalinks() ) {
				$result = user_trailingslashit( trailingslashit( $result ) . $wp_rewrite->comments_pagination_base . '-' . $pagenum, 'commentpaged' );
			} else {
				$result = add_query_arg( 'cpage', $pagenum, $result );
			}
		}
	} elseif ( $pagenum > 1 ) {
		if ( $wp_rewrite->using_permalinks() ) {
			$result = user_trailingslashit( trailingslashit( $result ) . $wp_rewrite->comments_pagination_base . '-' . $pagenum, 'commentpaged' );
		} else {
			$result = add_query_arg( 'cpage', $pagenum, $result );
		}
	}

	$result .= '#comments';

	/**
	 * Filters the comments page number link for the current request.
	 *
	 * @since WP 2.7.0
	 *
	 * @param string $result The comments page number link.
	 */
	return apply_filters( 'get_comments_pagenum_link', $result );
}

/**
 * Retrieves the link to the next comments page.
 *
 * @since WP 2.7.1
 * @since WP 6.7.0 Added the `page` parameter.
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string   $label    Optional. Label for link text. Default empty.
 * @param int      $max_page Optional. Max page. Default 0.
 * @param int|null $page     Optional. Page number. Default null.
 * @return string|void HTML-formatted link for the next page of comments.
 */
function get_next_comments_link( $label = '', $max_page = 0, $page = null ) {
	global $wp_query;

	if ( ! is_singular() ) {
		return;
	}

	if ( is_null( $page ) ) {
		$page = get_query_var( 'cpage' );
	}

	if ( ! $page ) {
		$page = 1;
	}

	$next_page = (int) $page + 1;

	if ( empty( $max_page ) ) {
		$max_page = $wp_query->max_num_comment_pages;
	}

	if ( empty( $max_page ) ) {
		$max_page = get_comment_pages_count();
	}

	if ( $next_page > $max_page ) {
		return;
	}

	if ( empty( $label ) ) {
		$label = __( 'Newer Comments &raquo;' );
	}

	/**
	 * Filters the anchor tag attributes for the next comments page link.
	 *
	 * @since WP 2.7.0
	 *
	 * @param string $attributes Attributes for the anchor tag.
	 */
	$attr = apply_filters( 'next_comments_link_attributes', '' );

	return sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( get_comments_pagenum_link( $next_page, $max_page ) ),
		$attr,
		preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
	);
}

/**
 * Displays the link to the next comments page.
 *
 * @since WP 2.7.0
 *
 * @param string $label    Optional. Label for link text. Default empty.
 * @param int    $max_page Optional. Max page. Default 0.
 */
function next_comments_link( $label = '', $max_page = 0 ) {
	echo get_next_comments_link( $label, $max_page );
}

/**
 * Retrieves the link to the previous comments page.
 *
 * @since WP 2.7.1
 * @since WP 6.7.0 Added the `page` parameter.
 *
 * @param string   $label Optional. Label for comments link text. Default empty.
 * @param int|null $page  Optional. Page number. Default null.
 * @return string|void HTML-formatted link for the previous page of comments.
 */
function get_previous_comments_link( $label = '', $page = null ) {
	if ( ! is_singular() ) {
		return;
	}

	if ( is_null( $page ) ) {
		$page = get_query_var( 'cpage' );
	}

	if ( (int) $page <= 1 ) {
		return;
	}

	$previous_page = (int) $page - 1;

	if ( empty( $label ) ) {
		$label = __( '&laquo; Older Comments' );
	}

	/**
	 * Filters the anchor tag attributes for the previous comments page link.
	 *
	 * @since WP 2.7.0
	 *
	 * @param string $attributes Attributes for the anchor tag.
	 */
	$attr = apply_filters( 'previous_comments_link_attributes', '' );

	return sprintf(
		'<a href="%1$s" %2$s>%3$s</a>',
		esc_url( get_comments_pagenum_link( $previous_page ) ),
		$attr,
		preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
	);
}

/**
 * Displays the link to the previous comments page.
 *
 * @since WP 2.7.0
 *
 * @param string $label Optional. Label for comments link text. Default empty.
 */
function previous_comments_link( $label = '' ) {
	echo get_previous_comments_link( $label );
}

/**
 * Displays or retrieves pagination links for the comments on the current post.
 *
 * @see paginate_links()
 * @since WP 2.7.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param string|array $args Optional args. See paginate_links(). Default empty array.
 * @return void|string|array Void if 'echo' argument is true and 'type' is not an array,
 *                           or if the query is not for an existing single post of any post type.
 *                           Otherwise, markup for comment page links or array of comment page links,
 *                           depending on 'type' argument.
 */
function paginate_comments_links( $args = array() ) {
	global $wp_rewrite;

	if ( ! is_singular() ) {
		return;
	}

	$page = get_query_var( 'cpage' );
	if ( ! $page ) {
		$page = 1;
	}
	$max_page = get_comment_pages_count();
	$defaults = array(
		'base'         => add_query_arg( 'cpage', '%#%' ),
		'format'       => '',
		'total'        => $max_page,
		'current'      => $page,
		'echo'         => true,
		'type'         => 'plain',
		'add_fragment' => '#comments',
	);
	if ( $wp_rewrite->using_permalinks() ) {
		$defaults['base'] = user_trailingslashit( trailingslashit( get_permalink() ) . $wp_rewrite->comments_pagination_base . '-%#%', 'commentpaged' );
	}

	$args       = wp_parse_args( $args, $defaults );
	$page_links = paginate_links( $args );

	if ( $args['echo'] && 'array' !== $args['type'] ) {
		echo $page_links;
	} else {
		return $page_links;
	}
}

/**
 * Retrieves navigation to next/previous set of comments, when applicable.
 *
 * @since WP 4.4.0
 * @since WP 5.3.0 Added the `aria_label` parameter.
 * @since WP 5.5.0 Added the `class` parameter.
 *
 * @param array $args {
 *     Optional. Default comments navigation arguments.
 *
 *     @type string $prev_text          Anchor text to display in the previous comments link.
 *                                      Default 'Older comments'.
 *     @type string $next_text          Anchor text to display in the next comments link.
 *                                      Default 'Newer comments'.
 *     @type string $screen_reader_text Screen reader text for the nav element. Default 'Comments navigation'.
 *     @type string $aria_label         ARIA label text for the nav element. Default 'Comments'.
 *     @type string $class              Custom class for the nav element. Default 'comment-navigation'.
 * }
 * @return string Markup for comments links.
 */
function get_the_comments_navigation( $args = array() ) {
	$navigation = '';

	// Are there comments to navigate through?
	if ( get_comment_pages_count() > 1 ) {
		// Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
		if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
			$args['aria_label'] = $args['screen_reader_text'];
		}

		$args = wp_parse_args(
			$args,
			array(
				'prev_text'          => __( 'Older comments' ),
				'next_text'          => __( 'Newer comments' ),
				'screen_reader_text' => __( 'Comments navigation' ),
				'aria_label'         => __( 'Comments' ),
				'class'              => 'comment-navigation',
			)
		);

		$prev_link = get_previous_comments_link( $args['prev_text'] );
		$next_link = get_next_comments_link( $args['next_text'] );

		if ( $prev_link ) {
			$navigation .= '<div class="nav-previous">' . $prev_link . '</div>';
		}

		if ( $next_link ) {
			$navigation .= '<div class="nav-next">' . $next_link . '</div>';
		}

		$navigation = _navigation_markup( $navigation, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	}

	return $navigation;
}

/**
 * Displays navigation to next/previous set of comments, when applicable.
 *
 * @since WP 4.4.0
 *
 * @param array $args See get_the_comments_navigation() for available arguments. Default empty array.
 */
function the_comments_navigation( $args = array() ) {
	echo get_the_comments_navigation( $args );
}

/**
 * Retrieves a paginated navigation to next/previous set of comments, when applicable.
 *
 * @since WP 4.4.0
 * @since WP 5.3.0 Added the `aria_label` parameter.
 * @since WP 5.5.0 Added the `class` parameter.
 *
 * @see paginate_comments_links()
 *
 * @param array $args {
 *     Optional. Default pagination arguments.
 *
 *     @type string $screen_reader_text Screen reader text for the nav element. Default 'Comments pagination'.
 *     @type string $aria_label         ARIA label text for the nav element. Default 'Comments pagination'.
 *     @type string $class              Custom class for the nav element. Default 'comments-pagination'.
 * }
 * @return string Markup for pagination links.
 */
function get_the_comments_pagination( $args = array() ) {
	$navigation = '';

	// Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}

	$args         = wp_parse_args(
		$args,
		array(
			'screen_reader_text' => __( 'Comments pagination' ),
			'aria_label'         => __( 'Comments pagination' ),
			'class'              => 'comments-pagination',
		)
	);
	$args['echo'] = false;

	// Make sure we get a string back. Plain is the next best thing.
	if ( isset( $args['type'] ) && 'array' === $args['type'] ) {
		$args['type'] = 'plain';
	}

	$links = paginate_comments_links( $args );

	if ( $links ) {
		$navigation = _navigation_markup( $links, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	}

	return $navigation;
}

/**
 * Displays a paginated navigation to next/previous set of comments, when applicable.
 *
 * @since WP 4.4.0
 *
 * @param array $args See get_the_comments_pagination() for available arguments. Default empty array.
 */
function the_comments_pagination( $args = array() ) {
	echo get_the_comments_pagination( $args );
}

function retraceur_reaction_get_canonical_url( $canonical_url ) {
	$cpage = get_query_var( 'cpage', 0 );
	if ( $cpage ) {
		$canonical_url = get_comments_pagenum_link( $cpage );
	}

	return $canonical_url;
}
add_filter( 'get_canonical_url', 'retraceur_reaction_get_canonical_url' );

/**
 * Check if this comment type allows avatars to be retrieved.
 *
 * @since WP 5.1.0
 *
 * @param string $comment_type Comment type to check.
 * @return bool Whether the comment type is allowed for retrieving avatars.
 */
function is_avatar_comment_type( $comment_type ) {
	/**
	 * Filters the list of allowed comment types for retrieving avatars.
	 *
	 * @since WP 3.0.0
	 *
	 * @param array $types An array of content types. Default only contains 'comment'.
	 */
	$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );

	return in_array( $comment_type, (array) $allowed_comment_types, true );
}

function retraceur_reaction_get_avatar_data( $args, $id_or_email, $url_args ) {
	if ( ! $args['found_avatar'] && $id_or_email instanceof WP_Comment ) {
		if ( ! is_avatar_comment_type( get_comment_type( $id_or_email ) ) ) {
			$args['url'] = false;
			/** This filter is documented in wp-includes/link-template.php */
			return apply_filters( 'get_avatar_data', $args, $id_or_email );
		}

		if ( ! empty( $id_or_email->user_id ) ) {
			$user = get_user_by( 'id', (int) $id_or_email->user_id );

			if ( isset( $user->user_email ) ) {
				$email = $user->user_email;
			}
		}
		if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
			$email = $id_or_email->comment_author_email;
		}

		$email_hash  = md5( strtolower( trim( $email ) ) );
		$args['url'] = add_query_arg(
		   rawurlencode_deep( array_filter( $url_args ) ),
		   'https://seccdn.libravatar.org/avatar/' . $email_hash
	   );
	}

	return $args;
}
add_filter( 'get_avatar_data', 'retraceur_reaction_get_avatar_data', 10, 3 );
