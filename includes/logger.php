<?php
/**
 * Logger class for StageGuard
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
 * Logger class.
 *
 * Handles logging of StageGuard activities using WordPress database.
 *
 * @since 1.0.1
 */
class Logger {
	/**
	 * Maximum number of log entries to keep.
	 *
	 * @var int
	 */
	private const MAX_LOG_ENTRIES = 1000;

	/**
	 * Option name for storing logs.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'stageguard_logs';

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	public function log( string $message ): void {
		$logs = $this->get_logs();

		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'message'   => sanitize_text_field( $message ),
		);

		array_unshift( $logs, $log_entry );

		// Keep only the last MAX_LOG_ENTRIES entries.
		if ( count( $logs ) > self::MAX_LOG_ENTRIES ) {
			$logs = array_slice( $logs, 0, self::MAX_LOG_ENTRIES );
		}

		update_option( self::OPTION_NAME, $logs, false );
	}

	/**
	 * Get all logs.
	 *
	 * @return array<int, array{timestamp: string, message: string}>
	 */
	public function get_logs(): array {
		$logs = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $logs ) ) {
			return array();
		}

		return $logs;
	}

	/**
	 * Get logs with limit.
	 *
	 * @param int $limit Number of logs to retrieve.
	 * @return array<int, array{timestamp: string, message: string}>
	 */
	public function get_logs_with_limit( int $limit = 50 ): array {
		$logs = $this->get_logs();

		if ( $limit > 0 && count( $logs ) > $limit ) {
			return array_slice( $logs, 0, $limit );
		}

		return $logs;
	}

	/**
	 * Clear all logs.
	 *
	 * @return void
	 */
	public function clear_logs(): void {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Get formatted log output.
	 *
	 * @param int $limit Number of logs to retrieve.
	 * @return string Formatted log output.
	 */
	public function get_formatted_logs( int $limit = 50 ): string {
		$logs   = $this->get_logs_with_limit( $limit );
		$output = '';

		foreach ( $logs as $log ) {
			$output .= sprintf(
				"[%s] %s\n",
				$log['timestamp'],
				$log['message']
			);
		}

		return $output;
	}
}
