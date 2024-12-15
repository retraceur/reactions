<?php

function retraceur_reaction_make_clickable_rel( $rel ) {
	// Apply "ugc" when in comment context.
	if ( 'comment_text' === current_filter() ) {
		$rel .= ' ugc';
	}

	return $rel;
}
add_filter( 'make_clickable_rel', 'retraceur_reaction_make_clickable_rel' );

function retraceur_reaction_sanitize_option( $value, $option, $original_value ) {
	global $wpdb;

	switch ( $option ) {
		case 'comment_max_links':
		case 'close_comments_days_old':
		case 'comments_per_page':
		case 'thread_comments_depth':
			$value = absint( $original_value );
			break;

		case 'default_ping_status':
		case 'default_comment_status':
			// Options that if not there have 0 value but need to be something like "closed".
			if ( '0' === (string) $original_value || '' === $original_value ) {
				$value = 'closed';
			}
			break;
		
		case 'ping_sites':
			$value = explode( "\n", $original_value );
			$value = array_filter( array_map( 'trim', $value ) );
			$value = array_filter( array_map( 'sanitize_url', $value ) );
			$value = implode( "\n", $value );
			break;

		case 'limited_email_domains':
		case 'banned_email_domains':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $original_value );
			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				if ( ! is_array( $value ) ) {
					$value = explode( "\n", $value );
				}

				$domains = array_values( array_filter( array_map( 'trim', $value ) ) );
				$value   = array();

				foreach ( $domains as $domain ) {
					if ( ! preg_match( '/(--|\.\.)/', $domain ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $domain ) ) {
						$value[] = $domain;
					}
				}
				if ( ! $value ) {
					$value = '';
				}
			}
			break;

		case 'moderation_keys':
		case 'disallowed_keys':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				$value = explode( "\n", $value );
				$value = array_filter( array_map( 'trim', $value ) );
				$value = array_unique( $value );
				$value = implode( "\n", $value );
			}
			break;
	}

	return $value;
}
