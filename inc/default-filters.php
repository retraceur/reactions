<?php

add_action( 'sanitize_comment_cookies', 'sanitize_comment_cookies' );
add_action( 'set_comment_cookies', 'wp_set_comment_cookies', 10, 3 );

add_filter( 'wp_privacy_personal_data_exporters', 'wp_register_comment_personal_data_exporter' );
add_filter( 'wp_privacy_personal_data_erasers', 'wp_register_comment_personal_data_eraser' );

add_action( 'embed_content_meta', 'print_embed_comments_button' );

add_filter( 'retraceur_unapproved_reaction_headers', 'retraceur_reaction_unapproved_headers' )
