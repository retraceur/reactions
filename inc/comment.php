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
