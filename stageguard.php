<?php
/**
 * Plugin Name: StageGuard
 * Plugin URI: https://github.com/Open-WP-Club/StageGuard/
 * Description: Manages staging environment, including Coming Soon mode, search engine visibility, staging indicator, debug mode toggle, and robots.txt modification.
 * Version: 1.0.1
 * Author: OpenWPClub.com
 * Author URI: https://openwpclub.com
 * License: GPL-2.0-or-later
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: stageguard
 * Domain Path: /languages
 *
 * @package StageGuard
 */

declare(strict_types=1);

namespace StageGuard;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'STAGEGUARD_VERSION', '1.0.1' );
define( 'STAGEGUARD_PLUGIN_FILE', __FILE__ );
define( 'STAGEGUARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STAGEGUARD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STAGEGUARD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader if available.
if ( file_exists( STAGEGUARD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once STAGEGUARD_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load required classes.
require_once STAGEGUARD_PLUGIN_DIR . 'includes/class-admin.php';
require_once STAGEGUARD_PLUGIN_DIR . 'includes/class-security.php';
require_once STAGEGUARD_PLUGIN_DIR . 'includes/class-logger.php';

/**
 * Main StageGuard class.
 *
 * @since 1.0.0
 */
final class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * List of plugins to handle.
	 *
	 * @var array<string>
	 */
	private array $plugins_to_handle;

	/**
	 * Admin instance.
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * Security instance.
	 *
	 * @var Security
	 */
	private Security $security;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_plugins_to_handle();

		// Initialize components.
		$this->logger   = new Logger();
		$this->admin    = new Admin( $this->logger );
		$this->security = new Security( $this->logger );

		$this->init_hooks();
	}

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( $this, 'deactivate_staging_plugins' ) );
		add_action( 'activate_plugin', array( $this, 'prevent_plugin_activation' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'maybe_activate_staging_settings' ) );
		add_action( 'wp_head', array( $this, 'add_staging_indicator' ) );
		add_action( 'admin_head', array( $this, 'add_staging_indicator' ) );
		add_filter( 'robots_txt', array( $this, 'custom_robots_txt' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'log_user_login' ), 10, 2 );

		// Email handling - prevent emails from being sent.
		add_filter( 'pre_wp_mail', array( $this, 'catch_staging_emails' ), 10, 2 );

		register_activation_hook( STAGEGUARD_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( STAGEGUARD_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'stageguard',
			false,
			dirname( STAGEGUARD_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Load plugins to handle/disable in staging.
	 *
	 * @return void
	 */
	private function load_plugins_to_handle(): void {
		/**
		 * Filter the list of plugins to handle in staging environment.
		 *
		 * @since 1.0.1
		 *
		 * @param array<string> $plugins List of plugin paths.
		 */
		$this->plugins_to_handle = apply_filters(
			'stageguard_plugins_to_handle',
			array(
                    'bunnycdn/bunnycdn.php', // BunnyCDN
                    'redis-cache/redis-cache.php', // Redis Cache
                    'google-listings-and-ads/google-listings-and-ads.php', // Google Listings and Ads
                    'metorik-helper/metorik-helper.php', // Metorik Helper
                    'mwb-zendesk-woo-order-sync/mwb-zendesk-woo-order-sync.php', // Order Sync with Zendesk for WooCommerce
                    'redis-object-cache/redis-object-cache.php', // Redis Object Cache
                    'runcloud-hub/runcloud-hub.php', // RunCloud Hub
                    'google-site-kit/google-site-kit.php', // Site Kit by Google
                    'google-listings-and-ads/google-listings-and-ads.php', // Google Listings and Ads
                    'super-page-cache-for-cloudflare/super-page-cache-for-cloudflare.php', // WP Cloudflare Page Cache older version
                    'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php', // WP Cloudflare Page Cache newer version
                    'woocommerce-shipstation-integration/woocommerce-shipstation.php', // ShipStation
                    'wp-opcache/wp-opcache.php', // OPcache
                    'headers-security-advanced-hsts-wp/headers-security-advanced-hsts-wp.php', // HSTS
                    'wp-rocket/wp-rocket.php', // WP Rocket
                    'tidio-live-chat/tidio-live-chat.php', // Tidio Chat
                    'litespeed-cache/litespeed-cache.php', // LiteSpeed Cache
                    'wp-fastest-cache/wpFastestCache.php', // WP Fastest Cache
                    'phastpress/phastpress.php', // PhastPress
                    'w3-total-cache/w3-total-cache.php', // W3 Total Cache
                    'wp-optimize/wp-optimize.php', // WP Optimize
                    'autoptimize/autoptimize.php', // Autoptimize
                    'nitropack/nitropack.php', // Nitropack
                    'wp-sync-db/wp-sync-db.php', // WP Sync DB
                    'wp-sync-db-media-files/wp-sync-db-media-files.php', // WP Sync DB Media Files
                    'updraftplus/updraftplus.php', // UpdraftPlus - Backup/Restore
                    'mailchimp-for-woocommerce/mailchimp-woocommerce.php', // Mailchimp for WooCommerce
            )
		);
		$this->plugins_to_handle = array_map( 'trim', $this->plugins_to_handle );
		$this->plugins_to_handle = array_unique( $this->plugins_to_handle );
	}

	/**
	 * Deactivate staging plugins.
	 *
	 * @return void
	 */
	public function deactivate_staging_plugins(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		foreach ( $this->plugins_to_handle as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				$this->logger->log(
					sprintf(
						/* translators: %s: plugin path */
						__( 'Deactivated plugin: %s', 'stageguard' ),
						$plugin
					)
				);
			}
		}
	}

	/**
	 * Prevent plugin activation.
	 *
	 * @param string $plugin Plugin path.
	 * @return void
	 */
	public function prevent_plugin_activation( string $plugin ): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( in_array( $plugin, $this->plugins_to_handle, true ) ) {
			deactivate_plugins( $plugin );
			$this->logger->log(
				sprintf(
					/* translators: %s: plugin path */
					__( 'Prevented activation of plugin: %s', 'stageguard' ),
					$plugin
				)
			);
			wp_safe_redirect(
				add_query_arg(
					'stageguard_activation_error',
					'true',
					admin_url( 'plugins.php' )
				)
			);
			exit;
		}
	}

	/**
	 * Maybe activate staging settings on first load.
	 *
	 * @return void
	 */
	public function maybe_activate_staging_settings(): void {
		$woocommerce_activated          = get_option( 'stageguard_woocommerce_activated', false );
		$search_engine_visibility_activated = get_option( 'stageguard_search_engine_visibility_activated', false );

		if ( ! $woocommerce_activated ) {
			$this->activate_woocommerce_coming_soon_mode();
			update_option( 'stageguard_woocommerce_activated', true );
		}

		if ( ! $search_engine_visibility_activated ) {
			$this->activate_wordpress_search_engine_visibility();
			update_option( 'stageguard_search_engine_visibility_activated', true );
		}
	}

	/**
	 * Activate WooCommerce Coming Soon mode.
	 *
	 * @return void
	 */
	private function activate_woocommerce_coming_soon_mode(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		if ( version_compare( WC()->version, '9.1', '>=' ) ) {
			update_option( 'woocommerce_coming_soon', 'yes' );
			$this->logger->log( __( 'Activated WooCommerce Coming Soon mode', 'stageguard' ) );
		}
	}

	/**
	 * Activate WordPress search engine visibility (discourage search engines).
	 *
	 * @return void
	 */
	private function activate_wordpress_search_engine_visibility(): void {
		update_option( 'blog_public', 0 );
		$this->logger->log( __( 'Activated WordPress Search Engine Visibility', 'stageguard' ) );
	}

	/**
	 * Add staging indicator banner.
	 *
	 * @return void
	 */
	public function add_staging_indicator(): void {
		// Only show if WooCommerce is not active (WooCommerce has its own coming soon mode).
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Enqueue the indicator styles.
		wp_enqueue_style(
			'stageguard-indicator',
			STAGEGUARD_PLUGIN_URL . 'assets/css/indicator.css',
			array(),
			STAGEGUARD_VERSION
		);
		?>
		<div id="stageguard-indicator">
			<?php esc_html_e( 'STAGING ENVIRONMENT', 'stageguard' ); ?>
		</div>
		<?php
	}

	/**
	 * Filter robots.txt content to disallow all crawlers.
	 * This uses filter only - no physical file modification.
	 *
	 * @param string $output Current robots.txt output.
	 * @param string $public Whether site is public.
	 * @return string Modified robots.txt content.
	 */
	public function custom_robots_txt( string $output, string $public ): string {
		return "User-agent: *\nDisallow: /\n";
	}

	/**
	 * Log user login.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public function log_user_login( string $user_login, \WP_User $user ): void {
		$this->logger->log(
			sprintf(
				/* translators: 1: username, 2: user ID */
				__( 'User logged in: %1$s (ID: %2$d)', 'stageguard' ),
				$user_login,
				$user->ID
			)
		);
	}

	/**
	 * Prevent emails from being sent in staging environment.
	 * Uses pre_wp_mail filter to completely block email sending.
	 *
	 * @param null|bool $return Short-circuit return value.
	 * @param array     $atts   Email attributes.
	 * @return bool Always returns false to prevent email sending.
	 */
	public function catch_staging_emails( $return, array $atts ): bool {
		$to      = $atts['to'] ?? '';
		$subject = $atts['subject'] ?? '';

		$this->logger->log(
			sprintf(
				/* translators: 1: email recipient, 2: email subject */
				__( 'Email blocked: To: %1$s, Subject: %2$s', 'stageguard' ),
				is_array( $to ) ? implode( ', ', $to ) : $to,
				$subject
			)
		);

		// Return false to prevent the email from being sent.
		return false;
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate(): void {
		add_option( 'stageguard_debug_mode', true );
		add_option( 'stageguard_password_protection', false );
		add_option( 'stageguard_ip_restriction', false );
		add_option( 'stageguard_allowed_ips', '' );

		$this->logger->log( __( 'StageGuard activated', 'stageguard' ) );

		// Reset the activation flags when the plugin is activated.
		delete_option( 'stageguard_woocommerce_activated' );
		delete_option( 'stageguard_search_engine_visibility_activated' );

		// Flush rewrite rules for robots.txt.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->logger->log( __( 'StageGuard deactivated', 'stageguard' ) );

		// Clean up the activation flags when the plugin is deactivated.
		delete_option( 'stageguard_woocommerce_activated' );
		delete_option( 'stageguard_search_engine_visibility_activated' );

		// Note: We don't delete other options here, that's done in uninstall.php.
		flush_rewrite_rules();
	}
}

/**
 * Initialize StageGuard plugin.
 *
 * @return void
 */
function init(): void {
	Plugin::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

// WP-CLI Support.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once STAGEGUARD_PLUGIN_DIR . 'includes/class-cli.php';
	\WP_CLI::add_command( 'stageguard', __NAMESPACE__ . '\CLI' );
}