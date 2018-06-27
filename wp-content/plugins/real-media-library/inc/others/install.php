<?php
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function rml_install($errorlevel = false, $installThisCallable = null) {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$charset_collate = $wpdb->get_charset_collate();
	$table_name = general\Core::getInstance()->getTableName();
	
	// Avoid errors
	if ($errorlevel === false) {
		$show_errors = $wpdb->show_errors(false);
		$suppress_errors = $wpdb->suppress_errors(false);
		$errorLevel = error_reporting();
		error_reporting(0);
	}
	
	// Table realmedialibrary
	if ($installThisCallable === null) {
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			parent mediumint(9) DEFAULT '-1' NOT NULL,
			name tinytext NOT NULL,
			slug text DEFAULT '' NOT NULL,
			absolute text DEFAULT '' NOT NULL,
			owner bigint(20) NOT NULL,
			ord mediumint(10) DEFAULT 0 NOT NULL,
			contentCustomOrder tinyint(1) DEFAULT 0 NOT NULL,
			type varchar(10) DEFAULT '0' NOT NULL,
			restrictions varchar(255) DEFAULT '' NOT NULL,
			cnt mediumint(10) DEFAULT NULL,
			importId bigint(20) DEFAULT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		dbDelta( $sql );
		
		if ($errorlevel) {
			$wpdb->print_error();
		}
		
		// Table realmedialibrary_posts
		$table_name = general\Core::getInstance()->getTableName("posts");
		$sql = "CREATE TABLE $table_name (
			attachment bigint(20) NOT NULL,
			fid mediumint(9) NOT NULL DEFAULT '-1',
			isShortcut bigint(20) NOT NULL DEFAULT 0,
			nr bigint(20),
			oldCustomNr bigint(20) DEFAULT NULL,
			importData text,
			KEY rmljoin (attachment,fid),
			UNIQUE KEY rmlorder (attachment,isShortcut)
		) $charset_collate;";
		dbDelta( $sql );
		
		if ($errorlevel) {
			$wpdb->print_error();
		}
		
		// Table realmedialibrary_meta
		$table_name = general\Core::getInstance()->getTableName("meta");
		$sql = "CREATE TABLE $table_name (
		  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `realmedialibrary_id` bigint(20) unsigned NOT NULL DEFAULT '0',
		  `meta_key` varchar(255) DEFAULT NULL,
		  `meta_value` longtext,
		  PRIMARY KEY  (meta_id),
		  KEY realmedialibrary_id (realmedialibrary_id),
		  KEY meta_key (meta_key)
		) $charset_collate;";
		dbDelta( $sql );
		
		if ($errorlevel) {
			$wpdb->print_error();
		}
		
		// Table realmedialibrary_meta
		$table_name = general\Core::getInstance()->getTableName("debug");
		$sql = "CREATE TABLE $table_name (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `text` text,
		  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql );
		
		if ($errorlevel) {
			$wpdb->print_error();
		}
	}else{
		call_user_func($installThisCallable);
	}
	
	if ($errorlevel === false) {
		$wpdb->show_errors($show_errors);
		$wpdb->suppress_errors($suppress_errors);
		error_reporting($errorLevel);
	}
	
	if ($installThisCallable === null) {
		update_option( 'rml_db_version', RML_VERSION );
	}
}