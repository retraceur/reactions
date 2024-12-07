<?php

if ( ! function_exists( 'wp_notify_postauthor' ) ) :
	/**
	 * Notifies an author (and/or others) of a comment/trackback/pingback on a post.
	 *
	 * @since WP 1.0.0
	 *
	 * @param int|WP_Comment $comment_id Comment ID or WP_Comment object.
	 * @param string         $deprecated Not used.
	 * @return bool True on completion. False if no email addresses were specified.
	 */
	function wp_notify_postauthor( $comment_id, $deprecated = null ) {
		if ( null !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '3.8.0' );
		}

		$comment = get_comment( $comment_id );
		if ( empty( $comment ) || empty( $comment->comment_post_ID ) ) {
			return false;
		}

		$post   = get_post( $comment->comment_post_ID );
		$author = get_userdata( $post->post_author );

		// Who to notify? By default, just the post author, but others can be added.
		$emails = array();
		if ( $author ) {
			$emails[] = $author->user_email;
		}

		/**
		 * Filters the list of email addresses to receive a comment notification.
		 *
		 * By default, only post authors are notified of comments. This filter allows
		 * others to be added.
		 *
		 * @since WP 3.7.0
		 *
		 * @param string[] $emails     An array of email addresses to receive a comment notification.
		 * @param string   $comment_id The comment ID as a numeric string.
		 */
		$emails = apply_filters( 'comment_notification_recipients', $emails, $comment->comment_ID );
		$emails = array_filter( $emails );

		// If there are no addresses to send the comment to, bail.
		if ( ! count( $emails ) ) {
			return false;
		}

		// Facilitate unsetting below without knowing the keys.
		$emails = array_flip( $emails );

		/**
		 * Filters whether to notify comment authors of their comments on their own posts.
		 *
		 * By default, comment authors aren't notified of their comments on their own
		 * posts. This filter allows you to override that.
		 *
		 * @since WP 3.8.0
		 *
		 * @param bool   $notify     Whether to notify the post author of their own comment.
		 *                           Default false.
		 * @param string $comment_id The comment ID as a numeric string.
		 */
		$notify_author = apply_filters( 'comment_notification_notify_author', false, $comment->comment_ID );

		// The comment was left by the author.
		if ( $author && ! $notify_author && (int) $comment->user_id === (int) $post->post_author ) {
			unset( $emails[ $author->user_email ] );
		}

		// The author moderated a comment on their own post.
		if ( $author && ! $notify_author && get_current_user_id() === (int) $post->post_author ) {
			unset( $emails[ $author->user_email ] );
		}

		// The post author is no longer a member of the blog.
		if ( $author && ! $notify_author && ! user_can( $post->post_author, 'read_post', $post->ID ) ) {
			unset( $emails[ $author->user_email ] );
		}

		// If there's no email to send the comment to, bail, otherwise flip array back around for use below.
		if ( ! count( $emails ) ) {
			return false;
		} else {
			$emails = array_flip( $emails );
		}

		$comment_author_domain = '';
		if ( WP_Http::is_ip_address( $comment->comment_author_IP ) ) {
			$comment_author_domain = gethostbyaddr( $comment->comment_author_IP );
		}

		/*
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 */
		$blogname        = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$comment_content = wp_specialchars_decode( $comment->comment_content );

		$wp_email = 'wordpress@' . preg_replace( '#^www\.#', '', wp_parse_url( network_home_url(), PHP_URL_HOST ) );

		if ( '' === $comment->comment_author ) {
			$from = "From: \"$blogname\" <$wp_email>";
			if ( '' !== $comment->comment_author_email ) {
				$reply_to = "Reply-To: $comment->comment_author_email";
			}
		} else {
			$from = "From: \"$comment->comment_author\" <$wp_email>";
			if ( '' !== $comment->comment_author_email ) {
				$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
			}
		}

		$message_headers = "$from\n"
		. 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";

		if ( isset( $reply_to ) ) {
			$message_headers .= $reply_to . "\n";
		}

		/**
		 * Filters the comment notification email headers.
		 *
		 * @since WP 1.5.2
		 *
		 * @param string $message_headers Headers for the comment notification email.
		 * @param string $comment_id      Comment ID as a numeric string.
		 */
		$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment->comment_ID );

		foreach ( $emails as $email ) {
			$user = get_user_by( 'email', $email );

			if ( $user ) {
				$switched_locale = switch_to_user_locale( $user->ID );
			} else {
				$switched_locale = switch_to_locale( get_locale() );
			}

			switch ( $comment->comment_type ) {
				case 'trackback':
					/* translators: %s: Post title. */
					$notify_message = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
					/* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
					$notify_message .= sprintf( __( 'Website: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					/* translators: %s: Trackback/pingback/comment author URL. */
					$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
					/* translators: %s: Comment text. */
					$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
					$notify_message .= __( 'You can see all trackbacks on this post here:' ) . "\r\n";
					/* translators: Trackback notification email subject. 1: Site title, 2: Post title. */
					$subject = sprintf( __( '[%1$s] Trackback: "%2$s"' ), $blogname, $post->post_title );
					break;

				case 'pingback':
					/* translators: %s: Post title. */
					$notify_message = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
					/* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
					$notify_message .= sprintf( __( 'Website: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					/* translators: %s: Trackback/pingback/comment author URL. */
					$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
					/* translators: %s: Comment text. */
					$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
					$notify_message .= __( 'You can see all pingbacks on this post here:' ) . "\r\n";
					/* translators: Pingback notification email subject. 1: Site title, 2: Post title. */
					$subject = sprintf( __( '[%1$s] Pingback: "%2$s"' ), $blogname, $post->post_title );
					break;

				default: // Comments.
					/* translators: %s: Post title. */
					$notify_message = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
					/* translators: 1: Comment author's name, 2: Comment author's IP address, 3: Comment author's hostname. */
					$notify_message .= sprintf( __( 'Author: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					/* translators: %s: Comment author email. */
					$notify_message .= sprintf( __( 'Email: %s' ), $comment->comment_author_email ) . "\r\n";
					/* translators: %s: Trackback/pingback/comment author URL. */
					$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";

					if ( $comment->comment_parent && user_can( $post->post_author, 'edit_comment', $comment->comment_parent ) ) {
						/* translators: Comment moderation. %s: Parent comment edit URL. */
						$notify_message .= sprintf( __( 'In reply to: %s' ), admin_url( "comment.php?action=editcomment&c={$comment->comment_parent}#wpbody-content" ) ) . "\r\n";
					}

					/* translators: %s: Comment text. */
					$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
					$notify_message .= __( 'You can see all comments on this post here:' ) . "\r\n";
					/* translators: Comment notification email subject. 1: Site title, 2: Post title. */
					$subject = sprintf( __( '[%1$s] Comment: "%2$s"' ), $blogname, $post->post_title );
					break;
			}

			$notify_message .= get_permalink( $comment->comment_post_ID ) . "#comments\r\n\r\n";
			/* translators: %s: Comment URL. */
			$notify_message .= sprintf( __( 'Permalink: %s' ), get_comment_link( $comment ) ) . "\r\n";

			if ( user_can( $post->post_author, 'edit_comment', $comment->comment_ID ) ) {
				if ( EMPTY_TRASH_DAYS ) {
					/* translators: Comment moderation. %s: Comment action URL. */
					$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c={$comment->comment_ID}#wpbody-content" ) ) . "\r\n";
				} else {
					/* translators: Comment moderation. %s: Comment action URL. */
					$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c={$comment->comment_ID}#wpbody-content" ) ) . "\r\n";
				}
				/* translators: Comment moderation. %s: Comment action URL. */
				$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c={$comment->comment_ID}#wpbody-content" ) ) . "\r\n";
			}

			/**
			 * Filters the comment notification email text.
			 *
			 * @since WP 1.5.2
			 *
			 * @param string $notify_message The comment notification email text.
			 * @param string $comment_id     Comment ID as a numeric string.
			 */
			$notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment->comment_ID );

			/**
			 * Filters the comment notification email subject.
			 *
			 * @since WP 1.5.2
			 *
			 * @param string $subject    The comment notification email subject.
			 * @param string $comment_id Comment ID as a numeric string.
			 */
			$subject = apply_filters( 'comment_notification_subject', $subject, $comment->comment_ID );

			wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}

		return true;
	}
endif;

if ( ! function_exists( 'wp_notify_moderator' ) ) :
	/**
	 * Notifies the moderator of the site about a new comment that is awaiting approval.
	 *
	 * @since WP 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * Uses the {@see 'notify_moderator'} filter to determine whether the site moderator
	 * should be notified, overriding the site setting.
	 *
	 * @param int $comment_id Comment ID.
	 * @return true Always returns true.
	 */
	function wp_notify_moderator( $comment_id ) {
		global $wpdb;

		$maybe_notify = get_option( 'moderation_notify' );

		/**
		 * Filters whether to send the site moderator email notifications, overriding the site setting.
		 *
		 * @since WP 4.4.0
		 *
		 * @param bool $maybe_notify Whether to notify blog moderator.
		 * @param int  $comment_id   The ID of the comment for the notification.
		 */
		$maybe_notify = apply_filters( 'notify_moderator', $maybe_notify, $comment_id );

		if ( ! $maybe_notify ) {
			return true;
		}

		$comment = get_comment( $comment_id );
		$post    = get_post( $comment->comment_post_ID );
		$user    = get_userdata( $post->post_author );
		// Send to the administration and to the post author if the author can modify the comment.
		$emails = array( get_option( 'admin_email' ) );
		if ( $user && user_can( $user->ID, 'edit_comment', $comment_id ) && ! empty( $user->user_email ) ) {
			if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
				$emails[] = $user->user_email;
			}
		}

		$comment_author_domain = '';
		if ( WP_Http::is_ip_address( $comment->comment_author_IP ) ) {
			$comment_author_domain = gethostbyaddr( $comment->comment_author_IP );
		}

		$comments_waiting = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'" );

		/*
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 */
		$blogname        = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$comment_content = wp_specialchars_decode( $comment->comment_content );

		$message_headers = '';

		/**
		 * Filters the list of recipients for comment moderation emails.
		 *
		 * @since WP 3.7.0
		 *
		 * @param string[] $emails     List of email addresses to notify for comment moderation.
		 * @param int      $comment_id Comment ID.
		 */
		$emails = apply_filters( 'comment_moderation_recipients', $emails, $comment_id );

		/**
		 * Filters the comment moderation email headers.
		 *
		 * @since WP 2.8.0
		 *
		 * @param string $message_headers Headers for the comment moderation email.
		 * @param int    $comment_id      Comment ID.
		 */
		$message_headers = apply_filters( 'comment_moderation_headers', $message_headers, $comment_id );

		foreach ( $emails as $email ) {
			$user = get_user_by( 'email', $email );

			if ( $user ) {
				$switched_locale = switch_to_user_locale( $user->ID );
			} else {
				$switched_locale = switch_to_locale( get_locale() );
			}

			switch ( $comment->comment_type ) {
				case 'trackback':
					/* translators: %s: Post title. */
					$notify_message  = sprintf( __( 'A new trackback on the post "%s" is waiting for your approval' ), $post->post_title ) . "\r\n";
					$notify_message .= get_permalink( $comment->comment_post_ID ) . "\r\n\r\n";
					/* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
					$notify_message .= sprintf( __( 'Website: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					/* translators: %s: Trackback/pingback/comment author URL. */
					$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
					$notify_message .= __( 'Trackback excerpt: ' ) . "\r\n" . $comment_content . "\r\n\r\n";
					break;

				case 'pingback':
					/* translators: %s: Post title. */
					$notify_message  = sprintf( __( 'A new pingback on the post "%s" is waiting for your approval' ), $post->post_title ) . "\r\n";
					$notify_message .= get_permalink( $comment->comment_post_ID ) . "\r\n\r\n";
					/* translators: 1: Trackback/pingback website name, 2: Website IP address, 3: Website hostname. */
					$notify_message .= sprintf( __( 'Website: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					/* translators: %s: Trackback/pingback/comment author URL. */
					$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
					$notify_message .= __( 'Pingback excerpt: ' ) . "\r\n" . $comment_content . "\r\n\r\n";
					break;

				default: // Comments.
					/* translators: %s: Post title. */
					$notify_message  = sprintf( __( 'A new comment on the post "%s" is waiting for your approval' ), $post->post_title ) . "\r\n";
					$notify_message .= get_permalink( $comment->comment_post_ID ) . "\r\n\r\n";
					/* translators: 1: Comment author's name, 2: Comment author's IP address, 3: Comment author's hostname. */
					$notify_message .= sprintf( __( 'Author: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					/* translators: %s: Comment author email. */
					$notify_message .= sprintf( __( 'Email: %s' ), $comment->comment_author_email ) . "\r\n";
					/* translators: %s: Trackback/pingback/comment author URL. */
					$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";

					if ( $comment->comment_parent ) {
						/* translators: Comment moderation. %s: Parent comment edit URL. */
						$notify_message .= sprintf( __( 'In reply to: %s' ), admin_url( "comment.php?action=editcomment&c={$comment->comment_parent}#wpbody-content" ) ) . "\r\n";
					}

					/* translators: %s: Comment text. */
					$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
					break;
			}

			/* translators: Comment moderation. %s: Comment action URL. */
			$notify_message .= sprintf( __( 'Approve it: %s' ), admin_url( "comment.php?action=approve&c={$comment_id}#wpbody-content" ) ) . "\r\n";

			if ( EMPTY_TRASH_DAYS ) {
				/* translators: Comment moderation. %s: Comment action URL. */
				$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c={$comment_id}#wpbody-content" ) ) . "\r\n";
			} else {
				/* translators: Comment moderation. %s: Comment action URL. */
				$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c={$comment_id}#wpbody-content" ) ) . "\r\n";
			}

			/* translators: Comment moderation. %s: Comment action URL. */
			$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c={$comment_id}#wpbody-content" ) ) . "\r\n";

			$notify_message .= sprintf(
				/* translators: Comment moderation. %s: Number of comments awaiting approval. */
				_n(
					'Currently %s comment is waiting for approval. Please visit the moderation panel:',
					'Currently %s comments are waiting for approval. Please visit the moderation panel:',
					$comments_waiting
				),
				number_format_i18n( $comments_waiting )
			) . "\r\n";
			$notify_message .= admin_url( 'edit-comments.php?comment_status=moderated#wpbody-content' ) . "\r\n";

			/* translators: Comment moderation notification email subject. 1: Site title, 2: Post title. */
			$subject = sprintf( __( '[%1$s] Please moderate: "%2$s"' ), $blogname, $post->post_title );

			/**
			 * Filters the comment moderation email text.
			 *
			 * @since WP 1.5.2
			 *
			 * @param string $notify_message Text of the comment moderation email.
			 * @param int    $comment_id     Comment ID.
			 */
			$notify_message = apply_filters( 'comment_moderation_text', $notify_message, $comment_id );

			/**
			 * Filters the comment moderation email subject.
			 *
			 * @since WP 1.5.2
			 *
			 * @param string $subject    Subject of the comment moderation email.
			 * @param int    $comment_id Comment ID.
			 */
			$subject = apply_filters( 'comment_moderation_subject', $subject, $comment_id );

			wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}

		return true;
	}
endif;
