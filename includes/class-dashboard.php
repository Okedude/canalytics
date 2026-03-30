<?php
/**
 * Comby_Dashboard Class v2.0
 * Ultimate Analytics Hub. Real-time, eCommerce, UTMs, Journeys, and Geolocation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Comby_Dashboard {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	public static function add_admin_menu() {
		add_menu_page(
			'Comby Analytics',
			'Comby Analytics',
			'view_comby_analytics',
			'comby-analytics',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-chart-area',
			30
		);
	}

	public static function enqueue_styles( $hook ) {
		if ( $hook !== 'toplevel_page_comby-analytics' ) {
			return;
		}

		wp_enqueue_style( 'comby-dashboard-css', COMBY_ANALYTICS_URL . 'assets/css/dashboard.css', array(), COMBY_ANALYTICS_VERSION );
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true );
		wp_enqueue_script( 'comby-dashboard-js', COMBY_ANALYTICS_URL . 'assets/js/dashboard.js', array( 'chart-js' ), COMBY_ANALYTICS_VERSION, true );

		wp_localize_script( 'comby-dashboard-js', 'comby_dashboard_data', self::get_dashboard_data() );
	}

	public static function render_dashboard() {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
		?>
		<div class="comby-dashboard-wrapper">
			<header class="comby-header">
				<div class="comby-logo">
					<span class="dashicons dashicons-chart-area"></span>
					<h1>Comby <span>Analytics</span> <small>v2.0 PRO</small></h1>
				</div>
				<nav class="comby-tabs">
					<a href="?page=comby-analytics&tab=overview" class="<?php echo $tab === 'overview' ? 'active' : ''; ?>">Overview</a>
					<a href="?page=comby-analytics&tab=realtime" class="<?php echo $tab === 'realtime' ? 'active' : ''; ?>">Real-time</a>
					<a href="?page=comby-analytics&tab=ecommerce" class="<?php echo $tab === 'ecommerce' ? 'active' : ''; ?>">eCommerce</a>
					<a href="?page=comby-analytics&tab=campaigns" class="<?php echo $tab === 'campaigns' ? 'active' : ''; ?>">Campaigns</a>
					<a href="?page=comby-analytics&tab=journeys" class="<?php echo $tab === 'journeys' ? 'active' : ''; ?>">Journeys</a>
					<a href="?page=comby-analytics&tab=settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">Settings</a>
				</nav>
			</header>

			<main class="comby-content">
				<?php 
				switch ( $tab ) {
					case 'overview': self::render_overview(); break;
					case 'realtime': self::render_realtime(); break;
					case 'ecommerce': self::render_ecommerce(); break;
					case 'campaigns': self::render_campaigns(); break;
					case 'journeys': self::render_journeys(); break;
					case 'settings': self::render_settings(); break;
				}
				?>
			</main>
		</div>
		<?php
	}

	private static function render_overview() {
		$data = self::get_dashboard_data();
		?>
		<div class="comby-view overview-view">
			<section class="comby-grid cards-grid">
				<div class="comby-card stats-card">
					<h3>Total Visitors</h3>
					<p class="stat-value"><?php echo $data['total_visitors']; ?></p>
					<span class="stat-label">Cookie-less unique hashes</span>
				</div>
				<div class="comby-card stats-card">
					<h3>Revenue</h3>
					<p class="stat-value">$<?php echo number_format($data['total_revenue'], 2); ?></p>
					<span class="stat-label">Total from eCommerce hooks</span>
				</div>
				<div class="comby-card stats-card">
					<h3>Conv. Rate</h3>
					<p class="stat-value"><?php echo round($data['conversion_rate'], 2); ?>%</p>
					<span class="stat-label">Orders / Visitors</span>
				</div>
			</section>

			<section class="comby-grid charts-grid">
				<div class="comby-card chart-card">
					<h3>Visitor Trends</h3>
					<canvas id="trendsChart"></canvas>
				</div>
			</section>
		</div>
		<?php
	}

	private static function render_realtime() {
		global $wpdb;
		$table_sessions = $wpdb->prefix . 'comby_sessions';
		$active_users = $wpdb->get_results( "SELECT * FROM $table_sessions WHERE last_ping > DATE_SUB(NOW(), INTERVAL 30 SECOND)" );
		?>
		<div class="comby-view realtime-view">
			<div class="comby-card table-card">
				<h3>Live Activity (<span class="status-pulse"></span> <?php echo count($active_users); ?> Active)</h3>
				<table class="comby-table">
					<thead><tr><th>Hash</th><th>IP</th><th>Source</th><th>Active Page</th></tr></thead>
					<tbody>
						<?php foreach ( $active_users as $user ) : 
							$last_pv = $wpdb->get_row( $wpdb->prepare( "SELECT page_title, page_url FROM {$wpdb->prefix}comby_pageviews WHERE session_hash = %s ORDER BY id DESC LIMIT 1", $user->session_hash ) );
						?>
						<tr>
							<td><small><?php echo substr($user->session_hash, 0, 8); ?>...</small></td>
							<td><?php echo $user->ip_address; ?></td>
							<td><?php echo $user->utm_source ?: ($user->referrer ?: 'Direct'); ?></td>
							<td><?php echo $last_pv ? esc_html($last_pv->page_title) : 'Unknown'; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	private static function render_ecommerce() {
		global $wpdb;
		$orders = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}comby_pageviews WHERE event_type = 'ecommerce' ORDER BY timestamp DESC LIMIT 20" );
		?>
		<div class="comby-view ecommerce-view">
			<div class="comby-card table-card">
				<h3>Recent Orders (WooCommerce / EDD / SureCart)</h3>
				<table class="comby-table">
					<thead><tr><th>Order ID</th><th>Revenue</th><th>Platform</th><th>Timestamp</th></tr></thead>
					<tbody>
						<?php foreach ( $orders as $o ) : ?>
						<tr>
							<td>#<?php echo $o->order_id; ?></td>
							<td class="stat-success">$<?php echo number_format($o->revenue, 2); ?></td>
							<td><?php echo $o->event_label; ?></td>
							<td><?php echo $o->timestamp; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	private static function render_campaigns() {
		global $wpdb;
		$campaigns = $wpdb->get_results( "SELECT utm_campaign, utm_source, COUNT(*) as visitors FROM {$wpdb->prefix}comby_sessions WHERE utm_campaign != '' GROUP BY utm_campaign ORDER BY visitors DESC" );
		?>
		<div class="comby-view campaigns-view">
			<div class="comby-card table-card">
				<h3>UTM Campaign Performance</h3>
				<table class="comby-table">
					<thead><tr><th>Campaign</th><th>Source</th><th>Visitors</th></tr></thead>
					<tbody>
						<?php foreach ( $campaigns as $c ) : ?>
						<tr>
							<td><strong><?php echo esc_html($c->utm_campaign); ?></strong></td>
							<td><?php echo esc_html($c->utm_source); ?></td>
							<td><?php echo $c->visitors; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	private static function render_journeys() {
		global $wpdb;
		$sessions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}comby_sessions ORDER BY last_ping DESC LIMIT 10" );
		?>
		<div class="comby-view journeys-view">
			<h3>Individual User Journeys</h3>
			<?php foreach ( $sessions as $s ) : 
				$path = $wpdb->get_results( $wpdb->prepare( "SELECT page_title, page_url, timestamp FROM {$wpdb->prefix}comby_pageviews WHERE session_hash = %s ORDER BY timestamp ASC", $s->session_hash ) );
			?>
			<div class="comby-card journey-card" style="margin-bottom: 20px;">
				<h4>Visitor Journey: <?php echo substr($s->session_hash, 0, 8); ?> <small>(<?php echo $s->ip_address; ?>)</small></h4>
				<ul class="journey-path">
					<?php foreach ( $path as $step ) : ?>
						<li>
							<span class="journey-time"><?php echo date('H:i:s', strtotime($step->timestamp)); ?></span>
							<span class="journey-page"><?php echo esc_html($step->page_title); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_settings() {
		?>
		<div class="comby-view settings-view">
			<div class="comby-card settings-card">
				<h3>Privacy & Management</h3>
				<form method="post" action="">
					<p><label><input type="checkbox" checked disabled> <strong>Cookie-less Tracking Enabled</strong></label></p>
					<p><label><input type="checkbox" checked disabled> Rotating Daily Salts (Privacy Enhancement)</label></p>
					<hr>
					<p>Auto-Prune Data After: 
						<select name="prune_days" class="comby-select">
							<option value="30">30 Days</option>
							<option value="60">60 Days</option>
							<option value="90" selected>90 Days</option>
						</select>
					</p>
					<button class="comby-btn" disabled>Save Settings</button>
				</form>
			</div>
		</div>
		<?php
	}

	private static function get_dashboard_data() {
		global $wpdb;
		$total_visitors = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}comby_sessions" );
		$total_revenue = $wpdb->get_var( "SELECT SUM(revenue) FROM {$wpdb->prefix}comby_pageviews" );
		$total_orders = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}comby_pageviews WHERE event_type = 'ecommerce'" );
		$conversion_rate = ($total_visitors > 0) ? ($total_orders / $total_visitors) * 100 : 0;

		$trends_labels = array();
		$trends_data = array();
		for ( $i = 6; $i >= 0; $i-- ) {
			$d = date('Y-m-d', strtotime("-$i days"));
			$trends_labels[] = date('D', strtotime($d));
			$trends_data[] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}comby_pageviews WHERE DATE(timestamp) = %s", $d ) );
		}

		return array(
			'total_visitors' => $total_visitors ?: 0,
			'total_revenue' => $total_revenue ?: 0,
			'total_orders' => $total_orders ?: 0,
			'conversion_rate' => $conversion_rate,
			'trends' => array(
				'labels' => $trends_labels,
				'data' => $trends_data
			)
		);
	}
}
