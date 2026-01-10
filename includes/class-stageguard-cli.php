<?php

/**
 * WP-CLI commands for StageGuard
 *
 * @package StageGuard
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * StageGuard CLI commands
 */
final class StageGuard_CLI
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
     *
     * @param array<int, string> $args Positional arguments
     */
    public function debug_mode(array $args): void
    {
        if (!isset($args[0])) {
            WP_CLI::error('Please specify either "on" or "off".');
            return;
        }

        $value = $args[0] === 'on';
        $stageguard = StageGuard::get_instance();

        // Use the public method instead of reflection
        $result = $stageguard->get_admin()->set_debug_mode($value);

        if ($result) {
            WP_CLI::success('Debug mode has been turned ' . ($value ? 'on' : 'off') . '.');
        } else {
            WP_CLI::warning('Debug mode option updated, but wp-config.php could not be modified.');
        }
    }

    /**
     * Displays the StageGuard log.
     *
     * ## OPTIONS
     *
     * [--lines=<number>]
     * : Number of lines to display from the end of the log. Default is 50.
     *
     * [--clear]
     * : Clear the log file after displaying.
     *
     * ## EXAMPLES
     *
     *     wp stageguard show_log
     *     wp stageguard show_log --lines=100
     *     wp stageguard show_log --clear
     *
     * @when after_wp_load
     *
     * @param array<int, string> $args Positional arguments
     * @param array<string, string> $assoc_args Associative arguments
     */
    public function show_log(array $args, array $assoc_args): void
    {
        $lines = isset($assoc_args['lines']) ? (int) $assoc_args['lines'] : 50;
        $log_file = WP_CONTENT_DIR . '/stageguard-log.txt';

        if (!file_exists($log_file)) {
            WP_CLI::error('Log file does not exist.');
            return;
        }

        $log_content = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($log_content === false) {
            WP_CLI::error('Could not read log file.');
            return;
        }

        $log_content = array_slice($log_content, -$lines);

        if (empty($log_content)) {
            WP_CLI::log('Log file is empty.');
        } else {
            foreach ($log_content as $line) {
                WP_CLI::line($line);
            }
        }

        if (isset($assoc_args['clear'])) {
            if (file_put_contents($log_file, '') !== false) {
                WP_CLI::success('Log file cleared.');
            } else {
                WP_CLI::warning('Could not clear log file.');
            }
        }
    }

    /**
     * Shows current StageGuard status.
     *
     * ## EXAMPLES
     *
     *     wp stageguard status
     *
     * @when after_wp_load
     */
    public function status(): void
    {
        $debug_mode = get_option('stageguard_debug_mode', false);
        $password_protection = get_option('stageguard_password_protection', false);
        $ip_restriction = get_option('stageguard_ip_restriction', false);
        $allowed_ips = get_option('stageguard_allowed_ips', '');

        WP_CLI::line('');
        WP_CLI::line('StageGuard Status:');
        WP_CLI::line('──────────────────────────────');
        WP_CLI::line(sprintf('Debug Mode:          %s', $debug_mode ? '✓ Enabled' : '✗ Disabled'));
        WP_CLI::line(sprintf('Password Protection: %s', $password_protection ? '✓ Enabled' : '✗ Disabled'));
        WP_CLI::line(sprintf('IP Restriction:      %s', $ip_restriction ? '✓ Enabled' : '✗ Disabled'));

        if ($ip_restriction && !empty($allowed_ips)) {
            $ips = array_filter(array_map('trim', explode("\n", $allowed_ips)));
            WP_CLI::line(sprintf('Allowed IPs:         %d configured', count($ips)));
        }

        WP_CLI::line('──────────────────────────────');
        WP_CLI::line('');
    }

    /**
     * Toggles password protection on or off.
     *
     * ## OPTIONS
     *
     * <on|off>
     * : Whether to turn password protection on or off.
     *
     * ## EXAMPLES
     *
     *     wp stageguard password_protection on
     *     wp stageguard password_protection off
     *
     * @when after_wp_load
     *
     * @param array<int, string> $args Positional arguments
     */
    public function password_protection(array $args): void
    {
        if (!isset($args[0])) {
            WP_CLI::error('Please specify either "on" or "off".');
            return;
        }

        $value = $args[0] === 'on';
        update_option('stageguard_password_protection', $value);

        StageGuard::get_instance()->log_action(
            sprintf('Password protection %s via CLI', $value ? 'enabled' : 'disabled')
        );

        WP_CLI::success('Password protection has been turned ' . ($value ? 'on' : 'off') . '.');
    }

    /**
     * Toggles IP restriction on or off.
     *
     * ## OPTIONS
     *
     * <on|off>
     * : Whether to turn IP restriction on or off.
     *
     * ## EXAMPLES
     *
     *     wp stageguard ip_restriction on
     *     wp stageguard ip_restriction off
     *
     * @when after_wp_load
     *
     * @param array<int, string> $args Positional arguments
     */
    public function ip_restriction(array $args): void
    {
        if (!isset($args[0])) {
            WP_CLI::error('Please specify either "on" or "off".');
            return;
        }

        $value = $args[0] === 'on';
        update_option('stageguard_ip_restriction', $value);

        StageGuard::get_instance()->log_action(
            sprintf('IP restriction %s via CLI', $value ? 'enabled' : 'disabled')
        );

        WP_CLI::success('IP restriction has been turned ' . ($value ? 'on' : 'off') . '.');
    }
}
