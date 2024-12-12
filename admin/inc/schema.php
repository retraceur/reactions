<?php

$tables = "CREATE TABLE $wpdb->commentmeta (
	meta_id bigint(20) unsigned NOT NULL auto_increment,
	comment_id bigint(20) unsigned NOT NULL default '0',
	meta_key varchar(255) default NULL,
	meta_value longtext,
	PRIMARY KEY  (meta_id),
	KEY comment_id (comment_id),
	KEY meta_key (meta_key($max_index_length))
) $charset_collate;
CREATE TABLE $wpdb->comments (
	comment_ID bigint(20) unsigned NOT NULL auto_increment,
	comment_post_ID bigint(20) unsigned NOT NULL default '0',
	comment_author tinytext NOT NULL,
	comment_author_email varchar(100) NOT NULL default '',
	comment_author_url varchar(200) NOT NULL default '',
	comment_author_IP varchar(100) NOT NULL default '',
	comment_date datetime NOT NULL default '0000-00-00 00:00:00',
	comment_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
	comment_content text NOT NULL,
	comment_karma int(11) NOT NULL default '0',
	comment_approved varchar(20) NOT NULL default '1',
	comment_agent varchar(255) NOT NULL default '',
	comment_type varchar(20) NOT NULL default 'comment',
	comment_parent bigint(20) unsigned NOT NULL default '0',
	user_id bigint(20) unsigned NOT NULL default '0',
	PRIMARY KEY  (comment_ID),
	KEY comment_post_ID (comment_post_ID),
	KEY comment_approved_date_gmt (comment_approved,comment_date_gmt),
	KEY comment_date_gmt (comment_date_gmt),
	KEY comment_parent (comment_parent),
	KEY comment_author_email (comment_author_email(10))
) $charset_collate;";
