<?php

function retraceur_reaction_feed_links( $args = array() ) {
    if ( apply_filters( 'feed_links_show_comments_feed', true ) ) {
        printf(
			'<link rel="alternate" type="%s" title="%s" href="%s" />' . "\n",
			feed_content_type(),
			esc_attr( sprintf( __( '%1$s %2$s Comments Feed' ), get_bloginfo( 'name' ), $args['separator'] ) ),
			esc_url( get_feed_link( 'comments_' . get_default_feed() ) )
		);
	}
}

function retraceur_reaction_feed_links_extra( $args = array() ) {
    $defaults = array(
		/* translators: 1: Site name, 2: Separator (raquo), 3: Post title. */
		'singletitle'   => __( '%1$s %2$s %3$s Comments Feed' ),
	);

	$args = wp_parse_args( $args, $defaults );

    if ( is_singular() ) {
		$id   = 0;
		$post = get_post( $id );

		/** This filter is documented in wp-includes/general-template.php */
		$show_comments_feed = apply_filters( 'feed_links_show_comments_feed', true );

		/**
		 * Filters whether to display the post comments feed link.
		 *
		 * This filter allows to enable or disable the feed link for a singular post
		 * in a way that is independent of {@see 'feed_links_show_comments_feed'}
		 * (which controls the global comments feed). The result of that filter
		 * is accepted as a parameter.
		 *
		 * @since WP 6.1.0
		 *
		 * @param bool $show_comments_feed Whether to display the post comments feed link. Defaults to
		 *                                 the {@see 'feed_links_show_comments_feed'} filter result.
		 */
		$show_post_comments_feed = apply_filters( 'feed_links_extra_show_post_comments_feed', $show_comments_feed );

		if ( $show_post_comments_feed && ( comments_open() || pings_open() || $post->comment_count > 0 ) ) {
			$title = sprintf(
				$args['singletitle'],
				get_bloginfo( 'name' ),
				$args['separator'],
				the_title_attribute( array( 'echo' => false ) )
			);

			$feed_link = get_post_comments_feed_link( $post->ID );

			if ( $feed_link ) {
				$href = $feed_link;
			}
		}
	}

    if ( isset( $title ) && isset( $href ) ) {
		printf(
			'<link rel="alternate" type="%s" title="%s" href="%s" />' . "\n",
			feed_content_type(),
			esc_attr( $title ),
			esc_url( $href )
		);
	}
}
