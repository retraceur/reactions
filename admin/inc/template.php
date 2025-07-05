<?php

// edit posts screen

/**
 * Outputs the in-line comment reply-to form in the Comments list table.
 *
 * @since WP 2.7.0
 *
 * @global WP_List_Table $wp_list_table
 *
 * @param int    $position  Optional. The value of the 'position' input field. Default 1.
 * @param bool   $checkbox  Optional. The value of the 'checkbox' input field. Default false.
 * @param string $mode      Optional. If set to 'single', will use WP_Post_Comments_List_Table,
 *                          otherwise WP_Comments_List_Table. Default 'single'.
 * @param bool   $table_row Optional. Whether to use a table instead of a div element. Default true.
 */
function wp_comment_reply( $position = 1, $checkbox = false, $mode = 'single', $table_row = true ) {
	global $wp_list_table;
	/**
	 * Filters the in-line comment reply-to form output in the Comments
	 * list table.
	 *
	 * Returning a non-empty value here will short-circuit display
	 * of the in-line comment-reply form in the Comments list table,
	 * echoing the returned value instead.
	 *
	 * @since 2.7.0
	 *
	 * @see wp_comment_reply()
	 *
	 * @param string $content The reply-to form content.
	 * @param array  $args    An array of default args.
	 */
	$content = apply_filters(
		'wp_comment_reply',
		'',
		array(
			'position' => $position,
			'checkbox' => $checkbox,
			'mode'     => $mode,
		)
	);

	if ( ! empty( $content ) ) {
		echo $content;
		return;
	}

	if ( ! $wp_list_table ) {
		if ( 'single' === $mode ) {
			$wp_list_table = _get_list_table( 'WP_Post_Comments_List_Table' );
		} else {
			$wp_list_table = _get_list_table( 'WP_Comments_List_Table' );
		}
	}

	?>
<form method="get">
	<?php if ( $table_row ) : ?>
<table style="display:none;"><tbody id="com-reply"><tr id="replyrow" class="inline-edit-row" style="display:none;"><td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="colspanchange">
<?php else : ?>
<div id="com-reply" style="display:none;"><div id="replyrow" style="display:none;">
<?php endif; ?>
	<fieldset class="comment-reply">
	<legend>
		<span class="hidden" id="editlegend"><?php _e( 'Edit Comment' ); ?></span>
		<span class="hidden" id="replyhead"><?php _e( 'Reply to Comment' ); ?></span>
		<span class="hidden" id="addhead"><?php _e( 'Add Comment' ); ?></span>
	</legend>

	<div id="replycontainer">
	<label for="replycontent" class="screen-reader-text">
		<?php
		/* translators: Hidden accessibility text. */
		_e( 'Comment' );
		?>
	</label>
	<?php
	$quicktags_settings = array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' );
	wp_editor(
		'',
		'replycontent',
		array(
			'media_buttons' => false,
			'tinymce'       => false,
			'quicktags'     => $quicktags_settings,
		)
	);
	?>
	</div>

	<div id="edithead" style="display:none;">
		<div class="inside">
		<label for="author-name"><?php _e( 'Name' ); ?></label>
		<input type="text" name="newcomment_author" size="50" value="" id="author-name" />
		</div>

		<div class="inside">
		<label for="author-email"><?php _e( 'Email' ); ?></label>
		<input type="text" name="newcomment_author_email" size="50" value="" id="author-email" />
		</div>

		<div class="inside">
		<label for="author-url"><?php _e( 'URL' ); ?></label>
		<input type="text" id="author-url" name="newcomment_author_url" class="code" size="103" value="" />
		</div>
	</div>

	<div id="replysubmit" class="submit">
		<p class="reply-submit-buttons">
			<button type="button" class="save button button-primary">
				<span id="addbtn" style="display: none;"><?php _e( 'Add Comment' ); ?></span>
				<span id="savebtn" style="display: none;"><?php _e( 'Update Comment' ); ?></span>
				<span id="replybtn" style="display: none;"><?php _e( 'Submit Reply' ); ?></span>
			</button>
			<button type="button" class="cancel button"><?php _e( 'Cancel' ); ?></button>
			<span class="waiting spinner"></span>
		</p>
		<?php
		wp_admin_notice(
			'<p class="error"></p>',
			array(
				'type'               => 'error',
				'additional_classes' => array( 'notice-alt', 'inline', 'hidden' ),
				'paragraph_wrap'     => false,
			)
		);
		?>
	</div>

	<input type="hidden" name="action" id="action" value="" />
	<input type="hidden" name="comment_ID" id="comment_ID" value="" />
	<input type="hidden" name="comment_post_ID" id="comment_post_ID" value="" />
	<input type="hidden" name="status" id="status" value="" />
	<input type="hidden" name="position" id="position" value="<?php echo $position; ?>" />
	<input type="hidden" name="checkbox" id="checkbox" value="<?php echo $checkbox ? 1 : 0; ?>" />
	<input type="hidden" name="mode" id="mode" value="<?php echo esc_attr( $mode ); ?>" />
	<?php
		wp_nonce_field( 'replyto-comment', '_ajax_nonce-replyto-comment', false );
	if ( current_user_can( 'unfiltered_html' ) ) {
		wp_nonce_field( 'unfiltered-html-comment', '_wp_unfiltered_html_comment', false );
	}
	?>
	</fieldset>
	<?php if ( $table_row ) : ?>
</td></tr></tbody></table>
	<?php else : ?>
</div></div>
	<?php endif; ?>
</form>
	<?php
}


/**
 * Outputs 'undo move to Trash' text for comments.
 *
 * @since WP 2.9.0
 */
function wp_comment_trashnotice() {
	?>
<div class="hidden" id="trash-undo-holder">
	<div class="trash-undo-inside">
		<?php
		/* translators: %s: Comment author, filled by Ajax. */
		printf( __( 'Comment by %s moved to the Trash.' ), '<strong></strong>' );
		?>
		<span class="undo untrash"><a href="#"><?php _e( 'Undo' ); ?></a></span>
	</div>
</div>
<div class="hidden" id="spam-undo-holder">
	<div class="spam-undo-inside">
		<?php
		/* translators: %s: Comment author, filled by Ajax. */
		printf( __( 'Comment by %s marked as spam.' ), '<strong></strong>' );
		?>
		<span class="undo unspam"><a href="#"><?php _e( 'Undo' ); ?></a></span>
	</div>
</div>
	<?php
}
