<?php
/**
 * WP-CLI commands for StageGuard
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
 * CLI class.
 *
 * Handles WP-CLI commands for StageGuard.
 *
 * @since 1.0.0
 */
class CLI {
	/**
	 * Toggle debug mode on or off.
	 *
	 * ## OPTIONS
	 *
	 * <on|off>
	 * : Whether to turn debug mode on or off.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stageguard debug_mode on
	 *     wp stageguard debug_mode off
	 *
	 * @param array<string> $args       Command arguments.
	 * @param array<string> $assoc_args Associative arguments.
	 * @return void
	 *
	 * @when after_wp_load
	 */
	public function debug_mode( array $args, array $assoc_args ): void {
		if ( ! isset( $args[0] ) ) {
			\WP_CLI::error( 'Please specify either "on" or "off".' );
			return;
		}

		$value = 'on' === $args[0];
		update_option( 'stageguard_debug_mode', $value );

		\WP_CLI::success( 'Debug mode has been turned ' . ( $value ? 'on' : 'off' ) . '.' );
		\WP_CLI::warning( 'Note: WP_DEBUG must be manually configured in wp-config.php for full debug mode functionality.' );
	}

	/**
	 * Display the StageGuard log.
	 *
	 * ## OPTIONS
	 *
	 * [--lines=<number>]
	 * : Number of lines to display from the log. Default is 50.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stageguard show_log
	 *     wp stageguard show_log --lines=100
	 *
	 * @param array<string> $args       Command arguments.
	 * @param array<string> $assoc_args Associative arguments.
	 * @return void
	 *
	 * @when after_wp_load
	 */
	public function show_log( array $args, array $assoc_args ): void {
		$lines = isset( $assoc_args['lines'] ) ? intval( $assoc_args['lines'] ) : 50;

		$logger = new Logger();
		$logs   = $logger->get_logs_with_limit( $lines );

		if ( empty( $logs ) ) {
			\WP_CLI::warning( 'No logs found.' );
			return;
		}

		\WP_CLI::line( sprintf( 'Showing last %d log entries:', count( $logs ) ) );
		\WP_CLI::line( '' );

		foreach ( $logs as $log ) {
			\WP_CLI::line(
				sprintf(
					'[%s] %s',
					$log['timestamp'],
					$log['message']
				)
			);
		}
	}

	/**
	 * Clear all StageGuard logs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stageguard clear_log
	 *
	 * @param array<string> $args       Command arguments.
	 * @param array<string> $assoc_args Associative arguments.
	 * @return void
	 *
	 * @when after_wp_load
	 */
	public function clear_log( array $args, array $assoc_args ): void {
		$logger = new Logger();
		$logger->clear_logs();

		\WP_CLI::success( 'All logs have been cleared.' );
	}
}
