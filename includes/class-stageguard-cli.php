<?php
/**
 * WP-CLI commands for StageGuard
 *
 * @package StageGuard
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * StageGuard CLI commands
 */
class StageGuard_CLI
{
    /**
     * Toggles debug mode on or off.
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
     * @when after_wp_load
     */
    public function debug_mode($args)
    {
        if (!isset($args[0])) {
            WP_CLI::error('Please specify either "on" or "off".');
        }

        $value = $args[0] === 'on';
        update_option('stageguard_debug_mode', $value);

        $stageguard = StageGuard::get_instance();
        $reflection = new ReflectionClass($stageguard);
        $method = $reflection->getMethod('update_wp_config');
        $method->setAccessible(true);
        $method->invoke($stageguard, 'WP_DEBUG', $value);

        WP_CLI::success('Debug mode has been turned ' . ($value ? 'on' : 'off') . '.');
    }

    /**
     * Displays the StageGuard log.
     *
     * ## OPTIONS
     *
     * [--lines=<number>]
     * : Number of lines to display from the end of the log. Default is 50.
     *
     * ## EXAMPLES
     *
     *     wp stageguard show_log
     *     wp stageguard show_log --lines=100
     *
     * @when after_wp_load
     */
    public function show_log($args, $assoc_args)
    {
        $lines = isset($assoc_args['lines']) ? intval($assoc_args['lines']) : 50;
        $log_file = WP_CONTENT_DIR . '/stageguard-log.txt';

        if (!file_exists($log_file)) {
            WP_CLI::error('Log file does not exist.');
        }

        $log_content = file($log_file);
        $log_content = array_slice($log_content, -$lines);

        foreach ($log_content as $line) {
            WP_CLI::line(trim($line));
        }
    }
}
