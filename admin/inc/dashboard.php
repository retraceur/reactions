<?php

/**
 * Show Comments section.
 *
 * @since WP 3.8.0
 *
 * @param int $total_items Optional. Number of comments to query. Default 5.
 * @return bool False if no comments were found. True otherwise.
 */
function wp_dashboard_recent_comments( $total_items = 5 ) {
	// Select all comment types and filter out spam later for better query performance.
	$comments = array();

	$comments_query = array(
		'number' => $total_items * 5,
		'offset' => 0,
	);

	if ( ! current_user_can( 'edit_posts' ) ) {
		$comments_query['status'] = 'approve';
	}

	do {
		$possible = get_comments( $comments_query );

		if ( empty( $possible ) || ! is_array( $possible ) ) {
			break;
		}

		foreach ( $possible as $comment ) {
			if ( ! current_user_can( 'edit_post', $comment->comment_post_ID )
				&& ( post_password_required( $comment->comment_post_ID )
					|| ! current_user_can( 'read_post', $comment->comment_post_ID ) )
			) {
				// The user has no access to the post and thus cannot see the comments.
				continue;
			}

			$comments[]     = $comment;
			$comments_count = count( $comments );

			if ( $comments_count === $total_items ) {
				break 2;
			}
		}

		$comments_query['offset'] += $comments_query['number'];
		$comments_query['number']  = $total_items * 10;
	} while ( $comments_count < $total_items );

	if ( $comments ) {
		echo '<div id="latest-comments" class="activity-block table-view-list">';
		echo '<h3>' . __( 'Recent Comments' ) . '</h3>';

		echo '<ul id="the-comment-list" data-wp-lists="list:comment">';
		foreach ( $comments as $comment ) {
			_wp_dashboard_recent_comments_row( $comment );
		}
		echo '</ul>';

		if ( current_user_can( 'edit_posts' ) ) {
			echo '<h3 class="screen-reader-text">' .
				/* translators: Hidden accessibility text. */
				__( 'View more comments' ) .
			'</h3>';
			_get_list_table( 'WP_Comments_List_Table' )->views();
		}

		wp_comment_reply( -1, false, 'dashboard', false );
		wp_comment_trashnotice();

		echo '</div>';
	} else {
		return false;
	}
	return true;
}

/**
 * Outputs a row for the Recent Comments widget.
 *
 * @access private
 * @since WP 2.7.0
 *
 * @global WP_Comment $comment Global comment object.
 *
 * @param WP_Comment $comment   The current comment.
 * @param bool       $show_date Optional. Whether to display the date.
 */
function _wp_dashboard_recent_comments_row( &$comment, $show_date = true ) {
	$GLOBALS['comment'] = clone $comment;

	if ( $comment->comment_post_ID > 0 ) {
		$comment_post_title = _draft_or_post_title( $comment->comment_post_ID );
		$comment_post_url   = get_the_permalink( $comment->comment_post_ID );
		$comment_post_link  = '<a href="' . esc_url( $comment_post_url ) . '">' . $comment_post_title . '</a>';
	} else {
		$comment_post_link = '';
	}

	$actions_string = '';
	if ( current_user_can( 'edit_comment', $comment->comment_ID ) ) {
		// Pre-order it: Approve | Reply | Edit | Spam | Trash.
		$actions = array(
			'approve'   => '',
			'unapprove' => '',
			'reply'     => '',
			'edit'      => '',
			'spam'      => '',
			'trash'     => '',
			'delete'    => '',
			'view'      => '',
		);

		$approve_nonce = esc_html( '_wpnonce=' . wp_create_nonce( 'approve-comment_' . $comment->comment_ID ) );
		$del_nonce     = esc_html( '_wpnonce=' . wp_create_nonce( 'delete-comment_' . $comment->comment_ID ) );

		$action_string = 'comment.php?action=%s&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID . '&%s';

		$approve_url   = sprintf( $action_string, 'approvecomment', $approve_nonce );
		$unapprove_url = sprintf( $action_string, 'unapprovecomment', $approve_nonce );
		$spam_url      = sprintf( $action_string, 'spamcomment', $del_nonce );
		$trash_url     = sprintf( $action_string, 'trashcomment', $del_nonce );
		$delete_url    = sprintf( $action_string, 'deletecomment', $del_nonce );

		$actions['approve'] = sprintf(
			'<a href="%s" data-wp-lists="%s" class="vim-a aria-button-if-js" aria-label="%s">%s</a>',
			esc_url( $approve_url ),
			"dim:the-comment-list:comment-{$comment->comment_ID}:unapproved:e7e7d3:e7e7d3:new=approved",
			esc_attr__( 'Approve this comment' ),
			__( 'Approve' )
		);

		$actions['unapprove'] = sprintf(
			'<a href="%s" data-wp-lists="%s" class="vim-u aria-button-if-js" aria-label="%s">%s</a>',
			esc_url( $unapprove_url ),
			"dim:the-comment-list:comment-{$comment->comment_ID}:unapproved:e7e7d3:e7e7d3:new=unapproved",
			esc_attr__( 'Unapprove this comment' ),
			__( 'Unapprove' )
		);

		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			"comment.php?action=editcomment&amp;c={$comment->comment_ID}",
			esc_attr__( 'Edit this comment' ),
			__( 'Edit' )
		);

		$actions['reply'] = sprintf(
			'<button type="button" onclick="window.commentReply && commentReply.open(\'%s\',\'%s\');" class="vim-r button-link hide-if-no-js" aria-label="%s">%s</button>',
			$comment->comment_ID,
			$comment->comment_post_ID,
			esc_attr__( 'Reply to this comment' ),
			__( 'Reply' )
		);

		$actions['spam'] = sprintf(
			'<a href="%s" data-wp-lists="%s" class="vim-s vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
			esc_url( $spam_url ),
			"delete:the-comment-list:comment-{$comment->comment_ID}::spam=1",
			esc_attr__( 'Mark this comment as spam' ),
			/* translators: "Mark as spam" link. */
			_x( 'Spam', 'verb' )
		);

		if ( ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="delete vim-d vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $delete_url ),
				"delete:the-comment-list:comment-{$comment->comment_ID}::trash=1",
				esc_attr__( 'Delete this comment permanently' ),
				__( 'Delete Permanently' )
			);
		} else {
			$actions['trash'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="delete vim-d vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $trash_url ),
				"delete:the-comment-list:comment-{$comment->comment_ID}::trash=1",
				esc_attr__( 'Move this comment to the Trash' ),
				_x( 'Trash', 'verb' )
			);
		}

		$actions['view'] = sprintf(
			'<a class="comment-link" href="%s" aria-label="%s">%s</a>',
			esc_url( get_comment_link( $comment ) ),
			esc_attr__( 'View this comment' ),
			__( 'View' )
		);

		/** This filter is documented in wp-admin/includes/class-wp-comments-list-table.php */
		$actions = apply_filters( 'comment_row_actions', array_filter( $actions ), $comment );

		$i = 0;

		foreach ( $actions as $action => $link ) {
			++$i;

			if ( ( ( 'approve' === $action || 'unapprove' === $action ) && 2 === $i )
				|| 1 === $i
			) {
				$separator = '';
			} else {
				$separator = ' | ';
			}

			// Reply and quickedit need a hide-if-no-js span.
			if ( 'reply' === $action || 'quickedit' === $action ) {
				$action .= ' hide-if-no-js';
			}

			if ( 'view' === $action && '1' !== $comment->comment_approved ) {
				$action .= ' hidden';
			}

			$actions_string .= "<span class='$action'>{$separator}{$link}</span>";
		}
	}
	?>

		<li id="comment-<?php echo $comment->comment_ID; ?>" <?php comment_class( array( 'comment-item', wp_get_comment_status( $comment ) ), $comment ); ?>>

			<?php
			$comment_row_class = '';

			if ( get_option( 'show_avatars' ) ) {
				echo get_avatar( $comment, 50, 'mystery' );
				$comment_row_class .= ' has-avatar';
			}
			?>

			<?php if ( ! $comment->comment_type || 'comment' === $comment->comment_type ) : ?>

			<div class="dashboard-comment-wrap has-row-actions <?php echo $comment_row_class; ?>">
			<p class="comment-meta">
				<?php
				// Comments might not have a post they relate to, e.g. programmatically created ones.
				if ( $comment_post_link ) {
					printf(
						/* translators: 1: Comment author, 2: Post link, 3: Notification if the comment is pending. */
						__( 'From %1$s on %2$s %3$s' ),
						'<cite class="comment-author">' . get_comment_author_link( $comment ) . '</cite>',
						$comment_post_link,
						'<span class="approve">' . __( '[Pending]' ) . '</span>'
					);
				} else {
					printf(
						/* translators: 1: Comment author, 2: Notification if the comment is pending. */
						__( 'From %1$s %2$s' ),
						'<cite class="comment-author">' . get_comment_author_link( $comment ) . '</cite>',
						'<span class="approve">' . __( '[Pending]' ) . '</span>'
					);
				}
				?>
			</p>

				<?php
			else :
				switch ( $comment->comment_type ) {
					case 'pingback':
						$type = __( 'Pingback' );
						break;
					case 'trackback':
						$type = __( 'Trackback' );
						break;
					default:
						$type = ucwords( $comment->comment_type );
				}
				$type = esc_html( $type );
				?>
			<div class="dashboard-comment-wrap has-row-actions">
			<p class="comment-meta">
				<?php
				// Pingbacks, Trackbacks or custom comment types might not have a post they relate to, e.g. programmatically created ones.
				if ( $comment_post_link ) {
					printf(
						/* translators: 1: Type of comment, 2: Post link, 3: Notification if the comment is pending. */
						_x( '%1$s on %2$s %3$s', 'dashboard' ),
						"<strong>$type</strong>",
						$comment_post_link,
						'<span class="approve">' . __( '[Pending]' ) . '</span>'
					);
				} else {
					printf(
						/* translators: 1: Type of comment, 2: Notification if the comment is pending. */
						_x( '%1$s %2$s', 'dashboard' ),
						"<strong>$type</strong>",
						'<span class="approve">' . __( '[Pending]' ) . '</span>'
					);
				}
				?>
			</p>
			<p class="comment-author"><?php comment_author_link( $comment ); ?></p>

			<?php endif; // comment_type ?>
			<blockquote><p><?php comment_excerpt( $comment ); ?></p></blockquote>
			<?php if ( $actions_string ) : ?>
			<p class="row-actions"><?php echo $actions_string; ?></p>
			<?php endif; ?>
			</div>
		</li>
	<?php
	$GLOBALS['comment'] = null;
}
