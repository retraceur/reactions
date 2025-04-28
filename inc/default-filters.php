<?php

if (
	// Comment reply link.
	isset( $_GET['replytocom'] )
	||
	// Unapproved comment preview.
	( isset( $_GET['unapproved'] ) && isset( $_GET['moderation-hash'] ) )
) {
	add_filter( 'wp_robots', 'wp_robots_no_robots' );
}

add_action( 'sanitize_comment_cookies', 'sanitize_comment_cookies' );
add_action( 'set_comment_cookies', 'wp_set_comment_cookies', 10, 3 );

add_filter( 'wp_privacy_personal_data_exporters', 'wp_register_comment_personal_data_exporter' );
add_filter( 'wp_privacy_personal_data_erasers', 'wp_register_comment_personal_data_eraser' );

add_action( 'embed_content_meta', 'print_embed_comments_button' );

add_filter( 'retraceur_unapproved_reaction_headers', 'retraceur_reaction_unapproved_headers' );

foreach (
    array(
        'comment_max_links',
        'close_comments_days_old',
        'comments_per_page',
        'thread_comments_depth',
        'default_ping_status',
        'default_comment_status',
        'ping_sites',
        'limited_email_domains',
        'banned_email_domains',
        'moderation_keys',
        'disallowed_keys',
    ) as $option ) {
    add_filter( "sanitize_option_{$option}", 'retraceur_reaction_sanitize_option', 10, 3 );
}

add_action( 'wp_head', 'retraceur_reaction_feed_links', 2 );
add_action( 'wp_head', 'retraceur_reaction_feed_links_extra', 3 );

add_action( 'publish_post', '_publish_post_hook', 5, 1 );

add_action( 'do_pings', 'do_all_pings', 10, 0 );
add_action( 'do_all_pings', 'do_all_pingbacks', 10, 0 );
add_action( 'do_all_pings', 'do_all_enclosures', 10, 0 );
add_action( 'do_all_pings', 'do_all_trackbacks', 10, 0 );
add_action( 'do_all_pings', 'generic_ping', 10, 0 );
add_filter( 'option_ping_sites', 'privacy_ping_filter' );
