<?php

// edit posts screen
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
