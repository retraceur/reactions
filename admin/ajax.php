<?php

/**
 * Handles replying to a comment via AJAX.
 *
 * @since WP 3.1.0
 *
 * @param string $action Action to perform.
 */
function wp_ajax_replyto_comment( $action ) {
	if ( empty( $action ) ) {
		$action = 'replyto-comment';
	}

	check_ajax_referer( $action, '_ajax_nonce-replyto-comment' );

	$comment_post_id = (int) $_POST['comment_post_ID'];
	$post            = get_post( $comment_post_id );

	if ( ! $post ) {
		wp_die( -1 );
	}

	if ( ! current_user_can( 'edit_post', $comment_post_id ) ) {
		wp_die( -1 );
	}

	if ( empty( $post->post_status ) ) {
		wp_die( 1 );
	} elseif ( in_array( $post->post_status, array( 'draft', 'pending', 'trash' ), true ) ) {
		wp_die( __( 'You cannot reply to a comment on a draft post.' ) );
	}

	$user = wp_get_current_user();

	if ( $user->exists() ) {
		$comment_author       = wp_slash( $user->display_name );
		$comment_author_email = wp_slash( $user->user_email );
		$comment_author_url   = wp_slash( $user->user_url );
		$user_id              = $user->ID;

		if ( current_user_can( 'unfiltered_html' ) ) {
			if ( ! isset( $_POST['_wp_unfiltered_html_comment'] ) ) {
				$_POST['_wp_unfiltered_html_comment'] = '';
			}

			if ( wp_create_nonce( 'unfiltered-html-comment' ) !== $_POST['_wp_unfiltered_html_comment'] ) {
				kses_remove_filters(); // Start with a clean slate.
				kses_init_filters();   // Set up the filters.
				remove_filter( 'pre_comment_content', 'wp_filter_post_kses' );
				add_filter( 'pre_comment_content', 'wp_filter_kses' );
			}
		}
	} else {
		wp_die( __( 'Sorry, you must be logged in to reply to a comment.' ) );
	}

	$comment_content = trim( $_POST['content'] );

	if ( '' === $comment_content ) {
		wp_die( __( 'Please type your comment text.' ) );
	}

	$comment_type = isset( $_POST['comment_type'] ) ? trim( $_POST['comment_type'] ) : 'comment';

	$comment_parent = 0;

	if ( isset( $_POST['comment_ID'] ) ) {
		$comment_parent = absint( $_POST['comment_ID'] );
	}

	$comment_auto_approved = false;

	$commentdata = array(
		'comment_post_ID' => $comment_post_id,
	);

	$commentdata += compact(
		'comment_author',
		'comment_author_email',
		'comment_author_url',
		'comment_content',
		'comment_type',
		'comment_parent',
		'user_id'
	);

	// Automatically approve parent comment.
	if ( ! empty( $_POST['approve_parent'] ) ) {
		$parent = get_comment( $comment_parent );

		if ( $parent && '0' === $parent->comment_approved && (int) $parent->comment_post_ID === $comment_post_id ) {
			if ( ! current_user_can( 'edit_comment', $parent->comment_ID ) ) {
				wp_die( -1 );
			}

			if ( wp_set_comment_status( $parent, 'approve' ) ) {
				$comment_auto_approved = true;
			}
		}
	}

	$comment_id = wp_new_comment( $commentdata );

	if ( is_wp_error( $comment_id ) ) {
		wp_die( $comment_id->get_error_message() );
	}

	$comment = get_comment( $comment_id );

	if ( ! $comment ) {
		wp_die( 1 );
	}

	$position = ( isset( $_POST['position'] ) && (int) $_POST['position'] ) ? (int) $_POST['position'] : '-1';

	ob_start();
	if ( isset( $_REQUEST['mode'] ) && 'dashboard' === $_REQUEST['mode'] ) {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		_wp_dashboard_recent_comments_row( $comment );
	} else {
		if ( isset( $_REQUEST['mode'] ) && 'single' === $_REQUEST['mode'] ) {
			$wp_list_table = _get_list_table( 'WP_Post_Comments_List_Table', array( 'screen' => 'edit-comments' ) );
		} else {
			$wp_list_table = _get_list_table( 'WP_Comments_List_Table', array( 'screen' => 'edit-comments' ) );
		}
		$wp_list_table->single_row( $comment );
	}
	$comment_list_item = ob_get_clean();

	$response = array(
		'what'     => 'comment',
		'id'       => $comment->comment_ID,
		'data'     => $comment_list_item,
		'position' => $position,
	);

	$counts                   = wp_count_comments();
	$response['supplemental'] = array(
		'in_moderation'        => $counts->moderated,
		'i18n_comments_text'   => sprintf(
			/* translators: %s: Number of comments. */
			_n( '%s Comment', '%s Comments', $counts->approved ),
			number_format_i18n( $counts->approved )
		),
		'i18n_moderation_text' => sprintf(
			/* translators: %s: Number of comments. */
			_n( '%s Comment in moderation', '%s Comments in moderation', $counts->moderated ),
			number_format_i18n( $counts->moderated )
		),
	);

	if ( $comment_auto_approved ) {
		$response['supplemental']['parent_approved'] = $parent->comment_ID;
		$response['supplemental']['parent_post_id']  = $parent->comment_post_ID;
	}

	$x = new WP_Ajax_Response();
	$x->add( $response );
	$x->send();
}

/**
 * Handles editing a comment via AJAX.
 *
 * @since WP 3.1.0
 */
function wp_ajax_edit_comment() {
	check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment' );

	$comment_id = (int) $_POST['comment_ID'];

	if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
		wp_die( -1 );
	}

	if ( '' === $_POST['content'] ) {
		wp_die( __( 'Please type your comment text.' ) );
	}

	if ( isset( $_POST['status'] ) ) {
		$_POST['comment_status'] = $_POST['status'];
	}

	$updated = edit_comment();
	if ( is_wp_error( $updated ) ) {
		wp_die( $updated->get_error_message() );
	}

	$position = ( isset( $_POST['position'] ) && (int) $_POST['position'] ) ? (int) $_POST['position'] : '-1';
	/*
	 * Checkbox is used to differentiate between the Edit Comments screen (1)
	 * and the Comments section on the Edit Post screen (0).
	 */
	$checkbox      = ( isset( $_POST['checkbox'] ) && '1' === $_POST['checkbox'] ) ? 1 : 0;
	$wp_list_table = _get_list_table( $checkbox ? 'WP_Comments_List_Table' : 'WP_Post_Comments_List_Table', array( 'screen' => 'edit-comments' ) );

	$comment = get_comment( $comment_id );

	if ( empty( $comment->comment_ID ) ) {
		wp_die( -1 );
	}

	ob_start();
	$wp_list_table->single_row( $comment );
	$comment_list_item = ob_get_clean();

	$x = new WP_Ajax_Response();

	$x->add(
		array(
			'what'     => 'edit_comment',
			'id'       => $comment->comment_ID,
			'data'     => $comment_list_item,
			'position' => $position,
		)
	);

	$x->send();
}

/**
 * Handles getting comments via AJAX.
 *
 * @since WP 3.1.0
 *
 * @global int $post_id
 *
 * @param string $action Action to perform.
 */
function wp_ajax_get_comments( $action ) {
	global $post_id;

	if ( empty( $action ) ) {
		$action = 'get-comments';
	}

	check_ajax_referer( $action );

	if ( empty( $post_id ) && ! empty( $_REQUEST['p'] ) ) {
		$id = absint( $_REQUEST['p'] );
		if ( ! empty( $id ) ) {
			$post_id = $id;
		}
	}

	if ( empty( $post_id ) ) {
		wp_die( -1 );
	}

	$wp_list_table = _get_list_table( 'WP_Post_Comments_List_Table', array( 'screen' => 'edit-comments' ) );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( -1 );
	}

	$wp_list_table->prepare_items();

	if ( ! $wp_list_table->has_items() ) {
		wp_die( 1 );
	}

	$x = new WP_Ajax_Response();

	ob_start();
	foreach ( $wp_list_table->items as $comment ) {
		if ( ! current_user_can( 'edit_comment', $comment->comment_ID ) && 0 === $comment->comment_approved ) {
			continue;
		}
		get_comment( $comment );
		$wp_list_table->single_row( $comment );
	}
	$comment_list_item = ob_get_clean();

	$x->add(
		array(
			'what' => 'comments',
			'data' => $comment_list_item,
		)
	);

	$x->send();
}
