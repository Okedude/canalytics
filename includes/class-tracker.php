<?php
/**
 * Comby_Tracker Class v2.0
 * Cookie-less, Privacy-First, eCommerce Ready.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Comby_Tracker {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracker' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		// eCommerce Hooks
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'track_woocommerce' ), 10, 1 );
		add_action( 'edd_complete_purchase', array( __CLASS__, 'track_edd' ), 10, 1 );
	}

	public static function enqueue_tracker() {
		$queried_object = get_queried_object();
		$author_id = 0;
		$categories = '';

		if ( is_singular() ) {
			$author_id = get_the_author_meta( 'ID' );
			$cats = get_the_category();
			$categories = ! empty( $cats ) ? implode( ',', wp_list_pluck( $cats, 'name' ) ) : '';
		}

		wp_enqueue_script( 'comby-tracker', COMBY_ANALYTICS_URL . 'assets/js/tracker.js', array(), COMBY_ANALYTICS_VERSION, true );
		wp_localize_script( 'comby-tracker', 'comby_config', array(
			'root'    => esc_url_raw( rest_url() ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'url'     => home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ),
			'title'   => get_the_title(),
			'user_id' => get_current_user_id(),
			'author_id'  => $author_id,
			'categories' => $categories
		) );
	}

	public static function register_rest_routes() {
		register_rest_route( 'comby-analytics/v1', '/ping', array(
			'methods'  => 'POST',
			'callback' => array( __CLASS__, 'handle_ping' ),
			'permission_callback' => '__return_true'
		) );
	}

	public static function handle_ping( $request ) {
		global $wpdb;
		$params = $request->get_params();
		$session_hash = self::get_privacy_hash();
		
		$page_url     = isset( $params['url'] ) ? esc_url_raw( $params['url'] ) : '';
		$page_title   = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
		$is_heartbeat = isset( $params['heartbeat'] ) && $params['heartbeat'] === 'true';
		$event_type   = isset( $params['event'] ) ? sanitize_text_field( $params['event'] ) : 'pageview';
		$event_label  = isset( $params['label'] ) ? sanitize_text_field( $params['label'] ) : '';
		
		$table_sessions  = $wpdb->prefix . 'comby_sessions';
		$table_pageviews = $wpdb->prefix . 'comby_pageviews';

		// 1. Session Setup (with UTMs and Referrer)
		$session = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_sessions WHERE session_hash = %s", $session_hash ) );

		if ( ! $session ) {
			$geo = self::get_local_geo( $_SERVER['REMOTE_ADDR'] );
			$wpdb->insert( $table_sessions, array(
				'session_hash' => $session_hash,
				'user_id'      => isset( $params['user_id'] ) ? intval( $params['user_id'] ) : 0,
				'ip_address'   => $_SERVER['REMOTE_ADDR'],
				'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
				'country'      => $geo['country'],
				'city'         => $geo['city'],
				'referrer'     => isset( $params['referrer'] ) ? esc_url_raw( $params['referrer'] ) : '',
				'utm_source'   => isset( $params['utm_source'] ) ? sanitize_text_field( $params['utm_source'] ) : '',
				'utm_medium'   => isset( $params['utm_medium'] ) ? sanitize_text_field( $params['utm_medium'] ) : '',
				'utm_campaign' => isset( $params['utm_campaign'] ) ? sanitize_text_field( $params['utm_campaign'] ) : '',
				'start_time'   => current_time( 'mysql' ),
				'last_ping'    => current_time( 'mysql' )
			) );
		} else {
			$wpdb->update( $table_sessions, array( 'last_ping' => current_time( 'mysql' ) ), array( 'id' => $session->id ) );
		}

		// 2. Activity / Pageview Logging
		if ( $is_heartbeat ) {
			$last_pv = $wpdb->get_row( $wpdb->prepare( 
				"SELECT id, duration_secs FROM $table_pageviews WHERE session_hash = %s AND page_url = %s ORDER BY id DESC LIMIT 1", 
				$session_hash, $page_url 
			) );
			if ( $last_pv ) {
				$wpdb->update( $table_pageviews, array( 'duration_secs' => $last_pv->duration_secs + 5 ), array( 'id' => $last_pv->id ) );
			}
		} else {
			$wpdb->insert( $table_pageviews, array(
				'session_hash' => $session_hash,
				'page_url'     => $page_url,
				'page_title'   => $page_title,
				'author_id'    => isset( $params['author_id'] ) ? intval( $params['author_id'] ) : 0,
				'categories'   => isset( $params['categories'] ) ? sanitize_text_field( $params['categories'] ) : '',
				'timestamp'    => current_time( 'mysql' ),
				'event_type'   => $event_type,
				'event_label'  => $event_label
			) );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Track WooCommerce Conversions
	 */
	public static function track_woocommerce( $order_id ) {
		global $wpdb;
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;

		$wpdb->insert( $wpdb->prefix . 'comby_pageviews', array(
			'session_hash' => self::get_privacy_hash(),
			'page_url'     => home_url( '/checkout/order-received/' ),
			'page_title'   => 'Order Complete',
			'timestamp'    => current_time( 'mysql' ),
			'event_type'   => 'ecommerce',
			'event_label'  => 'WooCommerce Order',
			'revenue'      => $order->get_total(),
			'order_id'     => $order_id
		) );
	}

	/**
	 * Cookie-less Privacy Hash (Identity-safe)
	 */
	private static function get_privacy_hash() {
		$ip = $_SERVER['REMOTE_ADDR'];
		$ua = $_SERVER['HTTP_USER_AGENT'];
		$salt = get_option( 'comby_analytics_salt', 'default_salt' );
		return hash( 'sha256', $ip . $ua . $salt );
	}

	/**
	 * Fast Local GeoIP (Simulated for this version)
	 */
	private static function get_local_geo( $ip ) {
		// In production, this would use a local geo database bin file.
		return array( 'country' => 'United States', 'city' => 'Local Network' );
	}
}
