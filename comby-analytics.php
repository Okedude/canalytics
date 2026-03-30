<?php
/**
 * Plugin Name: Comby Analytics
 * Description: Ultimate Privacy-First Analytics. Cookie-less, eCommerce ready (WooCommerce, EDD, SureCart), UTMs, Geolocation, and User Journeys.
 * Version: 2.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COMBY_ANALYTICS_VERSION', '2.0.0' );
define( 'COMBY_ANALYTICS_PATH', plugin_dir_path( __FILE__ ) );
define( 'COMBY_ANALYTICS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation Hook: Ultimate Database Schema
 */
register_activation_hook( __FILE__, 'comby_analytics_activate' );

function comby_analytics_activate() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	// table for sessions (Cookie-less)
	$table_sessions = $wpdb->prefix . 'comby_sessions';
	$sql_sessions = "CREATE TABLE $table_sessions (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		session_hash varchar(64) NOT NULL,
		user_id bigint(20) DEFAULT 0,
		ip_address varchar(45) DEFAULT '',
		user_agent text,
		country varchar(100) DEFAULT '',
		city varchar(100) DEFAULT '',
		referrer varchar(255) DEFAULT '',
		start_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		last_ping datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		is_active tinyint(1) DEFAULT 1,
		utm_source varchar(100) DEFAULT '',
		utm_medium varchar(100) DEFAULT '',
		utm_campaign varchar(100) DEFAULT '',
		PRIMARY KEY (id),
		UNIQUE KEY session_hash (session_hash)
	) $charset_collate;";

	// table for pageviews & events
	$table_pageviews = $wpdb->prefix . 'comby_pageviews';
	$sql_pageviews = "CREATE TABLE $table_pageviews (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		session_hash varchar(64) NOT NULL,
		page_url text NOT NULL,
		page_title varchar(255),
		author_id bigint(20) DEFAULT 0,
		categories varchar(255) DEFAULT '',
		timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		duration_secs int DEFAULT 0,
		event_type varchar(50) DEFAULT 'pageview',
		event_label text,
		revenue decimal(10,2) DEFAULT 0,
		order_id varchar(50) DEFAULT '',
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_sessions );
	dbDelta( $sql_pageviews );

	// Setup Daily Salt for Privacy
	if ( ! get_option( 'comby_analytics_salt' ) ) {
		update_option( 'comby_analytics_salt', wp_generate_password( 64, true, true ) );
	}

	// Roles
	$role = get_role( 'administrator' );
	if ( $role ) {
		$role->add_cap( 'view_comby_analytics' );
	}
}

/**
 * Initialize Plugin Parts
 */
function comby_analytics_init() {
	require_once COMBY_ANALYTICS_PATH . 'includes/class-tracker.php';
	require_once COMBY_ANALYTICS_PATH . 'includes/class-dashboard.php';

	Comby_Tracker::init();
	Comby_Dashboard::init();
}
add_action( 'plugins_loaded', 'comby_analytics_init' );

/**
 * Auto-Prune Old Data (WP-Cron)
 */
if ( ! wp_next_scheduled( 'comby_analytics_prune_data' ) ) {
	wp_schedule_event( time(), 'daily', 'comby_analytics_prune_data' );
}

add_action( 'comby_analytics_prune_data', 'comby_analytics_prune_callback' );
function comby_analytics_prune_callback() {
	global $wpdb;
	$days = 90; // Default prune period
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}comby_pageviews WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}comby_sessions WHERE last_ping < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
	
	// Rotate Salt once a day for maximum privacy
	update_option( 'comby_analytics_salt', wp_generate_password( 64, true, true ) );
}
