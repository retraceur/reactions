<?php
/**
 * List Table API: WP_Post_Comments_List_Table class.
 *
 * @since WP 4.4.0
 * @since 1.0.0 Retraceur fork.
 *
 * @package Retraceur
 * @subpackage Administration
 */

/**
 * Core class used to implement displaying post comments in a list table.
 *
 * @since WP 3.1.0
 *
 * @see WP_Comments_List_Table
 */
class WP_Post_List_Table extends WP_List_Table {

	/**
	 * @param bool $comment_status
	 * @return int
	 */
	public function inline_edit() {
		if ( post_type_supports( $screen->post_type, 'comments' ) || post_type_supports( $screen->post_type, 'trackbacks' ) ) : ?>

			<?php if ( $bulk ) : ?>

				<div class="inline-edit-group wp-clearfix">

				<?php if ( post_type_supports( $screen->post_type, 'comments' ) ) : ?>

					<label class="alignleft">
						<span class="title"><?php _e( 'Comments' ); ?></span>
						<select name="comment_status">
							<option value=""><?php _e( '&mdash; No Change &mdash;' ); ?></option>
							<option value="open"><?php _e( 'Allow' ); ?></option>
							<option value="closed"><?php _e( 'Do not allow' ); ?></option>
						</select>
					</label>

				<?php endif; ?>

				<?php if ( post_type_supports( $screen->post_type, 'trackbacks' ) ) : ?>

					<label class="alignright">
						<span class="title"><?php _e( 'Pings' ); ?></span>
						<select name="ping_status">
							<option value=""><?php _e( '&mdash; No Change &mdash;' ); ?></option>
							<option value="open"><?php _e( 'Allow' ); ?></option>
							<option value="closed"><?php _e( 'Do not allow' ); ?></option>
						</select>
					</label>

				<?php endif; ?>

				</div>

			<?php else : // $bulk ?>

				<div class="inline-edit-group wp-clearfix">

				<?php if ( post_type_supports( $screen->post_type, 'comments' ) ) : ?>

					<label class="alignleft">
						<input type="checkbox" name="comment_status" value="open" />
						<span class="checkbox-title"><?php _e( 'Allow Comments' ); ?></span>
					</label>

				<?php endif; ?>

				<?php if ( post_type_supports( $screen->post_type, 'trackbacks' ) ) : ?>

					<label class="alignleft">
						<input type="checkbox" name="ping_status" value="open" />
						<span class="checkbox-title"><?php _e( 'Allow Pings' ); ?></span>
					</label>

				<?php endif; ?>

				</div>

			<?php endif; // $bulk ?>

		<?php endif;
	}
}
