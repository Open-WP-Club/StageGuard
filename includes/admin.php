<?php
/**
 * Admin settings and interface for StageGuard
 *
 * @package StageGuard
 */

declare(strict_types=1);

namespace StageGuard;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * Handles admin settings and interface.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param Logger $logger Logger instance.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;

		add_action( 'admin_menu', array( $this, 'add_stageguard_menu' ) );
		add_action( 'admin_notices', array( $this, 'staging_env_notice' ) );
		add_action( 'admin_notices', array( $this, 'stageguard_activation_notice' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Display staging environment notice.
	 *
	 * @return void
	 */
	public function staging_env_notice(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'This website is a staging environment.', 'stageguard' )
		);
	}

	/**
	 * Display plugin activation error notice.
	 *
	 * @return void
	 */
	public function stageguard_activation_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['stageguard_activation_error'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'true' !== sanitize_text_field( wp_unslash( $_GET['stageguard_activation_error'] ) ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__(
				'This plugin cannot be activated in the staging environment. Please deactivate StageGuard to enable this plugin.',
				'stageguard'
			)
		);
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @return void
	 */
	public function add_stageguard_menu(): void {
		// Main settings page.
		add_options_page(
			__( 'StageGuard Settings', 'stageguard' ),
			__( 'StageGuard', 'stageguard' ),
			'manage_options',
			'stageguard-settings',
			array( $this, 'render_settings_page' )
		);

		// Logs submenu page.
		add_submenu_page(
			'options-general.php',
			__( 'StageGuard Logs', 'stageguard' ),
			__( 'StageGuard Logs', 'stageguard' ),
			'manage_options',
			'stageguard-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// Register settings.
		register_setting(
			'stageguard_settings',
			'stageguard_debug_mode',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		register_setting(
			'stageguard_settings',
			'stageguard_password_protection',
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		register_setting(
			'stageguard_settings',
			'stageguard_ip_restriction',
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		register_setting(
			'stageguard_settings',
			'stageguard_allowed_ips',
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		);

		// Add settings section.
		add_settings_section(
			'stageguard_main_section',
			__( 'StageGuard Settings', 'stageguard' ),
			array( $this, 'settings_section_callback' ),
			'stageguard-settings'
		);

		// Add settings fields.
		add_settings_field(
			'stageguard_debug_mode',
			__( 'Debug Mode', 'stageguard' ),
			array( $this, 'debug_mode_field_callback' ),
			'stageguard-settings',
			'stageguard_main_section'
		);

		add_settings_field(
			'stageguard_password_protection',
			__( 'Password Protection', 'stageguard' ),
			array( $this, 'password_protection_field_callback' ),
			'stageguard-settings',
			'stageguard_main_section'
		);

		add_settings_field(
			'stageguard_ip_restriction',
			__( 'IP Restriction', 'stageguard' ),
			array( $this, 'ip_restriction_field_callback' ),
			'stageguard-settings',
			'stageguard_main_section'
		);

		add_settings_field(
			'stageguard_allowed_ips',
			__( 'Allowed IPs', 'stageguard' ),
			array( $this, 'allowed_ips_field_callback' ),
			'stageguard-settings',
			'stageguard_main_section'
		);
	}

	/**
	 * Settings section callback.
	 *
	 * @return void
	 */
	public function settings_section_callback(): void {
		echo '<p>' . esc_html__(
			'Configure StageGuard settings below. Note: Debug mode is controlled via wp-config.php and requires direct file access.',
			'stageguard'
		) . '</p>';
	}

	/**
	 * Debug mode field callback.
	 *
	 * @return void
	 */
	public function debug_mode_field_callback(): void {
		$value   = get_option( 'stageguard_debug_mode', true );
		$checked = $value ? 'checked' : '';
		?>
		<label for="stageguard_debug_mode">
			<input type="checkbox" id="stageguard_debug_mode" name="stageguard_debug_mode" value="1" <?php echo esc_attr( $checked ); ?>>
			<?php esc_html_e( 'Enable Debug Mode (Recommended for staging)', 'stageguard' ); ?>
		</label>
		<p class="description">
			<?php
			esc_html_e(
				'Note: Debug mode requires WP_DEBUG to be defined in wp-config.php. This setting stores your preference but manual wp-config.php updates may be needed.',
				'stageguard'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Password protection field callback.
	 *
	 * @return void
	 */
	public function password_protection_field_callback(): void {
		$value   = get_option( 'stageguard_password_protection', false );
		$checked = $value ? 'checked' : '';
		?>
		<label for="stageguard_password_protection">
			<input type="checkbox" id="stageguard_password_protection" name="stageguard_password_protection" value="1" <?php echo esc_attr( $checked ); ?>>
			<?php esc_html_e( 'Enable Password Protection (Redirects to WordPress login)', 'stageguard' ); ?>
		</label>
		<?php
	}

	/**
	 * IP restriction field callback.
	 *
	 * @return void
	 */
	public function ip_restriction_field_callback(): void {
		$value   = get_option( 'stageguard_ip_restriction', false );
		$checked = $value ? 'checked' : '';
		?>
		<label for="stageguard_ip_restriction">
			<input type="checkbox" id="stageguard_ip_restriction" name="stageguard_ip_restriction" value="1" <?php echo esc_attr( $checked ); ?>>
			<?php esc_html_e( 'Enable IP Restriction', 'stageguard' ); ?>
		</label>
		<?php
	}

	/**
	 * Allowed IPs field callback.
	 *
	 * @return void
	 */
	public function allowed_ips_field_callback(): void {
		$value      = get_option( 'stageguard_allowed_ips', '' );
		$current_ip = $this->get_current_user_ip();
		?>
		<textarea id="stageguard_allowed_ips" name="stageguard_allowed_ips" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php
			esc_html_e(
				'Enter one IP address per line. Supports individual IPs (192.168.1.1), CIDR notation (192.168.1.0/24), and IP ranges (192.168.1.1-192.168.1.10).',
				'stageguard'
			);
			?>
			<br>
			<?php
			printf(
				/* translators: %s: current user IP address */
				esc_html__( 'Your current IP address is: %s', 'stageguard' ),
				'<strong>' . esc_html( $current_ip ) . '</strong>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Get current user IP address.
	 *
	 * @return string
	 */
	private function get_current_user_ip(): string {
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stageguard' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'StageGuard Settings', 'stageguard' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'stageguard_settings' );
				do_settings_sections( 'stageguard-settings' );
				submit_button( __( 'Save Settings', 'stageguard' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Quick Links', 'stageguard' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=stageguard-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'View Activity Logs', 'stageguard' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the logs page.
	 *
	 * @return void
	 */
	public function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stageguard' ) );
		}

		// Handle clear logs action.
		if ( isset( $_POST['stageguard_clear_logs'] ) && check_admin_referer( 'stageguard_clear_logs' ) ) {
			$this->logger->clear_logs();
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'All logs have been cleared.', 'stageguard' ) .
				'</p></div>';
		}

		$logs       = $this->logger->get_logs();
		$logs_count = count( $logs );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'StageGuard Activity Logs', 'stageguard' ); ?></h1>

			<p>
				<?php
				printf(
					/* translators: %d: number of log entries */
					esc_html__( 'Showing %d log entries (maximum 1000 stored)', 'stageguard' ),
					esc_html( $logs_count )
				);
				?>
			</p>

			<?php if ( ! empty( $logs ) ) : ?>
				<form method="post" style="margin-bottom: 20px;">
					<?php wp_nonce_field( 'stageguard_clear_logs' ); ?>
					<input type="submit" name="stageguard_clear_logs" class="button button-secondary"
						value="<?php esc_attr_e( 'Clear All Logs', 'stageguard' ); ?>"
						onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs?', 'stageguard' ); ?>');">
				</form>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 180px;"><?php esc_html_e( 'Timestamp', 'stageguard' ); ?></th>
							<th><?php esc_html_e( 'Message', 'stageguard' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['timestamp'] ); ?></td>
								<td><?php echo esc_html( $log['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No logs found.', 'stageguard' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
