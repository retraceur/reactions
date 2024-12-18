<?php

/**
 * Helper function that constructs a comment query vars array from the passed
 * block properties.
 *
 * It's used with the Comment Query Loop inner blocks.
 *
 * @since WP 6.0.0
 *
 * @param WP_Block $block Block instance.
 * @return array Returns the comment query parameters to use with the
 *               WP_Comment_Query constructor.
 */
function build_comment_query_vars_from_block( $block ) {

	$comment_args = array(
		'orderby'       => 'comment_date_gmt',
		'order'         => 'ASC',
		'status'        => 'approve',
		'no_found_rows' => false,
	);

	if ( is_user_logged_in() ) {
		$comment_args['include_unapproved'] = array( get_current_user_id() );
	} else {
		$unapproved_email = wp_get_unapproved_comment_author_email();

		if ( $unapproved_email ) {
			$comment_args['include_unapproved'] = array( $unapproved_email );
		}
	}

	if ( ! empty( $block->context['postId'] ) ) {
		$comment_args['post_id'] = (int) $block->context['postId'];
	}

	if ( get_option( 'thread_comments' ) ) {
		$comment_args['hierarchical'] = 'threaded';
	} else {
		$comment_args['hierarchical'] = false;
	}

	if ( get_option( 'page_comments' ) === '1' || get_option( 'page_comments' ) === true ) {
		$per_page     = get_option( 'comments_per_page' );
		$default_page = get_option( 'default_comments_page' );
		if ( $per_page > 0 ) {
			$comment_args['number'] = $per_page;

			$page = (int) get_query_var( 'cpage' );
			if ( $page ) {
				$comment_args['paged'] = $page;
			} elseif ( 'oldest' === $default_page ) {
				$comment_args['paged'] = 1;
			} elseif ( 'newest' === $default_page ) {
				$max_num_pages = (int) ( new WP_Comment_Query( $comment_args ) )->max_num_pages;
				if ( 0 !== $max_num_pages ) {
					$comment_args['paged'] = $max_num_pages;
				}
			}
		}
	}

	return $comment_args;
}

/**
 * Helper function that returns the proper pagination arrow HTML for
 * `CommentsPaginationNext` and `CommentsPaginationPrevious` blocks based on the
 * provided `paginationArrow` from `CommentsPagination` context.
 *
 * It's used in CommentsPaginationNext and CommentsPaginationPrevious blocks.
 *
 * @since WP 6.0.0
 *
 * @param WP_Block $block           Block instance.
 * @param string   $pagination_type Optional. Type of the arrow we will be rendering.
 *                                  Accepts 'next' or 'previous'. Default 'next'.
 * @return string|null The pagination arrow HTML or null if there is none.
 */
function get_comments_pagination_arrow( $block, $pagination_type = 'next' ) {
	$arrow_map = array(
		'none'    => '',
		'arrow'   => array(
			'next'     => '→',
			'previous' => '←',
		),
		'chevron' => array(
			'next'     => '»',
			'previous' => '«',
		),
	);
	if ( ! empty( $block->context['comments/paginationArrow'] ) && ! empty( $arrow_map[ $block->context['comments/paginationArrow'] ][ $pagination_type ] ) ) {
		$arrow_attribute = $block->context['comments/paginationArrow'];
		$arrow           = $arrow_map[ $block->context['comments/paginationArrow'] ][ $pagination_type ];
		$arrow_classes   = "wp-block-comments-pagination-$pagination_type-arrow is-arrow-$arrow_attribute";
		return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>";
	}
	return null;
}
