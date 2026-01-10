<?php

/**
 * Plugin Name: StageGuard
 * Plugin URI: https://github.com/Open-WP-Club/StageGuard/
 * Description: Manages staging environment, including Coming Soon mode, search engine visibility, staging indicator, debug mode toggle, and robots.txt modification.
 * Version: 1.1.1
 * Author: OpenWPClub.com
 * Author URI: https://openwpclub.com
 * License: GPL-2.0-or-later
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Text Domain: stageguard
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once plugin_dir_path(__FILE__) . 'includes/class-stageguard-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-stageguard-security.php';

/**
 * Main StageGuard class
 */
final class StageGuard
{
    private static ?self $instance = null;

    /** @var array<int, string> */
    private readonly array $plugins_to_handle;

    private readonly string $log_file;

    private readonly StageGuard_Admin $admin;

    private readonly StageGuard_Security $security;

    private function __construct()
    {
        $this->plugins_to_handle = $this->get_plugins_list();
        $this->log_file = WP_CONTENT_DIR . '/stageguard-log.txt';

        // Initialize components
        $this->admin = new StageGuard_Admin();
        $this->security = new StageGuard_Security();

        add_action('plugins_loaded', $this->load_textdomain(...));
        add_action('admin_init', $this->deactivate_staging_plugins(...));
        add_action('activate_plugin', $this->prevent_plugin_activation(...), 10, 1);
        add_action('admin_init', $this->maybe_activate_staging_settings(...));
        add_action('wp_head', $this->add_staging_indicator(...));
        add_filter('robots_txt', $this->custom_robots_txt(...), 10, 2);
        add_action('wp_login', $this->log_user_login(...), 10, 2);

        // Email handling
        add_filter('wp_mail', $this->catch_staging_emails(...));

        register_activation_hook(__FILE__, $this->activate(...));
        register_deactivation_hook(__FILE__, $this->deactivate(...));
    }

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the admin instance
     */
    public function get_admin(): StageGuard_Admin
    {
        return $this->admin;
    }

    /**
     * Get the security instance
     */
    public function get_security(): StageGuard_Security
    {
        return $this->security;
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('stageguard', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Get list of plugins to handle in staging
     *
     * @return array<int, string>
     */
    private function get_plugins_list(): array
    {
        return array_map('trim', [
            'bunnycdn/bunnycdn.php',
            'redis-cache/redis-cache.php',
            'google-listings-and-ads/google-listings-and-ads.php',
            'metorik-helper/metorik-helper.php',
            'mwb-zendesk-woo-order-sync/mwb-zendesk-woo-order-sync.php',
            'redis-object-cache/redis-object-cache.php',
            'runcloud-hub/runcloud-hub.php',
            'google-site-kit/google-site-kit.php',
            'super-page-cache-for-cloudflare/super-page-cache-for-cloudflare.php',
            'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php',
            'woocommerce-shipstation-integration/woocommerce-shipstation.php',
            'wp-opcache/wp-opcache.php',
            'headers-security-advanced-hsts-wp/headers-security-advanced-hsts-wp.php',
            'wp-rocket/wp-rocket.php',
            'tidio-live-chat/tidio-live-chat.php',
            'litespeed-cache/litespeed-cache.php',
            'wp-fastest-cache/wpFastestCache.php',
            'phastpress/phastpress.php',
            'w3-total-cache/w3-total-cache.php',
            'wp-optimize/wp-optimize.php',
            'autoptimize/autoptimize.php',
            'nitropack/nitropack.php',
            'wp-sync-db/wp-sync-db.php',
            'wp-sync-db-media-files/wp-sync-db-media-files.php',
            'updraftplus/updraftplus.php',
            'mailchimp-for-woocommerce/mailchimp-woocommerce.php',
        ]);
    }

    public function deactivate_staging_plugins(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        foreach ($this->plugins_to_handle as $plugin) {
            if (is_plugin_active($plugin)) {
                deactivate_plugins($plugin);
                $this->log_action(sprintf('Deactivated plugin: %s', $plugin));
            }
        }
    }

    public function prevent_plugin_activation(string $plugin): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        if (in_array($plugin, $this->plugins_to_handle, true)) {
            deactivate_plugins($plugin);
            $this->log_action(sprintf('Prevented activation of plugin: %s', $plugin));
            wp_safe_redirect(add_query_arg('stageguard_activation_error', 'true', admin_url('plugins.php')));
            exit;
        }
    }

    public function maybe_activate_staging_settings(): void
    {
        $woocommerce_activated = get_option('stageguard_woocommerce_activated', false);
        $search_engine_visibility_activated = get_option('stageguard_search_engine_visibility_activated', false);

        if (!$woocommerce_activated) {
            $this->activate_woocommerce_coming_soon_mode();
            update_option('stageguard_woocommerce_activated', true);
        }

        if (!$search_engine_visibility_activated) {
            $this->activate_wordpress_search_engine_visibility();
            update_option('stageguard_search_engine_visibility_activated', true);
        }
    }

    public function activate_woocommerce_coming_soon_mode(): void
    {
        if (class_exists('WooCommerce')) {
            if (version_compare(WC()->version, '9.1', '>=')) {
                update_option('woocommerce_coming_soon', 'yes');
                $this->log_action('Activated WooCommerce Coming Soon mode');
            }
        }
    }

    public function activate_wordpress_search_engine_visibility(): void
    {
        update_option('blog_public', 0);
        $this->log_action('Activated WordPress Search Engine Visibility');
    }

    public function add_staging_indicator(): void
    {
        if (class_exists('WooCommerce')) {
            return;
        }

        ?>
        <style>
            body {
                margin-top: 35px !important;
            }
            #stageguard-indicator {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #ff0000;
                color: white;
                text-align: center;
                padding: 10px;
                font-size: 16px;
                font-weight: bold;
                z-index: 999999;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            }
            body.admin-bar #stageguard-indicator {
                top: 32px;
            }
            @media screen and (max-width: 782px) {
                body.admin-bar #stageguard-indicator {
                    top: 46px;
                }
                body {
                    margin-top: 46px !important;
                }
            }
        </style>
        <div id="stageguard-indicator">
            <?php esc_html_e('STAGING ENVIRONMENT', 'stageguard'); ?>
        </div>
        <?php
    }

    /**
     * Filter robots.txt content to disallow all crawlers
     */
    public function custom_robots_txt(string $output, string $public): string
    {
        return "User-agent: *\nDisallow: /\n";
    }

    /**
     * Log an action to the log file
     */
    public function log_action(string $message): void
    {
        $timestamp = current_time('mysql');
        $log_message = sprintf("[%s] %s\n", $timestamp, $message);
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }

    public function log_user_login(string $user_login, \WP_User $user): void
    {
        $this->log_action(sprintf('User logged in: %s (ID: %d)', $user_login, $user->ID));
    }

    /**
     * Catch and redirect staging emails
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function catch_staging_emails(array $args): array
    {
        $to = is_array($args['to']) ? implode(', ', $args['to']) : (string) $args['to'];
        $subject = (string) ($args['subject'] ?? '');

        $this->log_action(sprintf('Email caught: To: %s, Subject: %s', $to, $subject));

        // Prevent the email from being sent
        $args['to'] = 'no-reply@example.com';

        return $args;
    }

    public function activate(): void
    {
        add_option('stageguard_debug_mode', true);
        add_option('stageguard_password_protection', false);
        add_option('stageguard_ip_restriction', false);
        add_option('stageguard_allowed_ips', '');
        $this->log_action('StageGuard activated');

        // Reset the activation flags when the plugin is activated
        delete_option('stageguard_woocommerce_activated');
        delete_option('stageguard_search_engine_visibility_activated');
    }

    public function deactivate(): void
    {
        delete_option('stageguard_debug_mode');
        delete_option('stageguard_password_protection');
        delete_option('stageguard_ip_restriction');
        delete_option('stageguard_allowed_ips');
        $this->log_action('StageGuard deactivated');

        // Clean up the activation flags when the plugin is deactivated
        delete_option('stageguard_woocommerce_activated');
        delete_option('stageguard_search_engine_visibility_activated');
    }
}

/**
 * Initialize StageGuard
 */
function stageguard_init(): void
{
    StageGuard::get_instance();
}
add_action('plugins_loaded', 'stageguard_init');

// WP-CLI Support
if (defined('WP_CLI') && WP_CLI) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-stageguard-cli.php';
    WP_CLI::add_command('stageguard', 'StageGuard_CLI');
}
