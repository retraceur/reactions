<?php

add_action( 'sanitize_comment_cookies', 'sanitize_comment_cookies' );
add_action( 'set_comment_cookies', 'wp_set_comment_cookies', 10, 3 );
