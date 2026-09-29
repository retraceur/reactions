<?php

/**
 * Allows the note mention chip markup in comment content.
 *
 * The notes `@` mention completer stores a mention as a chip carrying the
 * mentioned user's ID in a class token:
 * `<span class="wp-note-mention user-N">@Name</span>`. The default comment
 * allowlist does not allow `span` at all, so for users without
 * `unfiltered_html` the mention would be stripped on save.
 *
 * The allowance is deliberately narrow and always on: `span` is a
 * semantics-free element and _wp_kses_sanitize_note_mention_classes()
 * reduces its `class` to the two mention tokens right after kses runs, so
 * regular (including anonymous) commenters gain nothing beyond the inert
 * mention markup itself.
 *
 * @since WP 7.1.0
 * @access private
 *
 * @param array<string, array<string, bool>> $allowed The allowed tags structure for the context.
 * @param string                             $context The kses context.
 * @return array<string, array<string, bool>> Modified allowed tags structure.
 */
function _wp_kses_allow_note_mention_span( $allowed, $context ): array {
	if ( ! is_array( $allowed ) ) {
		$allowed = array();
	}
	if ( 'pre_comment_content' !== $context ) {
		return $allowed;
	}

	if ( ! isset( $allowed['span'] ) || ! is_array( $allowed['span'] ) ) {
		$allowed['span'] = array();
	}

	$allowed['span']['class'] = true;

	return $allowed;
}

/**
 * Reduces `span` classes in comment content to the note mention tokens.
 *
 * _wp_kses_allow_note_mention_span() lets `class` through kses on `span` so
 * the mention chip survives, but `class` is an open-ended styling and
 * scripting hook, so this companion pass - running right after
 * `wp_filter_kses` at priority 10 - strips every class token except the two
 * the mention markup uses: `wp-note-mention` and `user-N`. `span` is the only
 * comment tag allowed to carry `class` at all, so walking `span` tags covers
 * the entire allowance.
 *
 * The pass only applies while the restrictive comment allowlist is active:
 * users with `unfiltered_html` are filtered through `wp_filter_post_kses`
 * (or not at all), where arbitrary classes are already permitted, and
 * narrowing their markup here would restrict what core allows them to post.
 *
 * @since WP 7.1.0
 * @access private
 *
 * @param string $content Slashed comment content, already filtered by kses.
 * @return string Slashed comment content with span classes reduced.
 */
function _wp_kses_sanitize_note_mention_classes( $content ): string {
	if ( ! is_string( $content ) ) {
		$content = '';
	}
	if ( false === has_filter( 'pre_comment_content', 'wp_filter_kses' ) ) {
		return $content;
	}

	$processor = new WP_HTML_Tag_Processor( wp_unslash( $content ) );

	while ( $processor->next_tag( 'SPAN' ) ) {
		foreach ( $processor->class_list() as $token ) {
			if ( 'wp-note-mention' !== $token && ! preg_match( '/^user-[1-9][0-9]*$/', $token ) ) {
				// Removing the last class also removes the attribute itself.
				$processor->remove_class( $token );
			}
		}
	}

	return wp_slash( $processor->get_updated_html() );
}
