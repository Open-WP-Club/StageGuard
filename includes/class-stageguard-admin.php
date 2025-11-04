<?php
/**
 * Admin settings and interface for StageGuard
 *
 * @package StageGuard
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * StageGuard Admin class
 */
class StageGuard_Admin
{
    /**
     * Initialize the admin functionality
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_stageguard_menu']);
        add_action('admin_notices', [$this, 'staging_env_notice']);
        add_action('admin_notices', [$this, 'stageguard_activation_notice']);
    }

    /**
     * Display staging environment notice
     */
    public function staging_env_notice()
    {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('This website is a staging environment.', 'stageguard') . '</p></div>';
        }
    }

    /**
     * Display plugin activation error notice
     */
    public function stageguard_activation_notice()
    {
        if (isset($_GET['stageguard_activation_error']) && sanitize_text_field(wp_unslash($_GET['stageguard_activation_error'])) === 'true') {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('This plugin cannot be activated in the staging environment. Please deactivate StageGuard to enable this plugin.', 'stageguard') . '</p></div>';
        }
    }

    /**
     * Add settings page to admin menu
     */
    public function add_stageguard_menu()
    {
        add_options_page(
            __('StageGuard Settings', 'stageguard'),
            __('StageGuard', 'stageguard'),
            'manage_options',
            'stageguard-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'stageguard'));
        }

        if (isset($_POST['stageguard_settings']) && check_admin_referer('stageguard_settings')) {
            $this->save_settings();
        }

        $this->display_settings_form();
    }

    /**
     * Save settings from form submission
     */
    private function save_settings()
    {
        $debug_mode = isset($_POST['debug_mode']);
        $password_protection = isset($_POST['password_protection']);
        $ip_restriction = isset($_POST['ip_restriction']);
        $allowed_ips = isset($_POST['allowed_ips']) ? sanitize_textarea_field(wp_unslash($_POST['allowed_ips'])) : '';

        update_option('stageguard_debug_mode', $debug_mode);
        update_option('stageguard_password_protection', $password_protection);
        update_option('stageguard_ip_restriction', $ip_restriction);
        update_option('stageguard_allowed_ips', $allowed_ips);

        // Update wp-config.php
        $this->update_wp_config('WP_DEBUG', $debug_mode);

        // Log the action
        $stageguard = StageGuard::get_instance();
        $stageguard->log_action('Settings updated');

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'stageguard') . '</p></div>';
    }

    /**
     * Display the settings form
     */
    private function display_settings_form()
    {
        $current_debug_mode = get_option('stageguard_debug_mode', true);
        $current_password_protection = get_option('stageguard_password_protection', false);
        $current_ip_restriction = get_option('stageguard_ip_restriction', false);
        $current_allowed_ips = get_option('stageguard_allowed_ips', '');
        $current_user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('StageGuard Settings', 'stageguard'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('stageguard_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Debug Mode', 'stageguard'); ?></th>
                        <td>
                            <label for="debug_mode">
                                <input type="checkbox" id="debug_mode" name="debug_mode" <?php checked($current_debug_mode); ?>>
                                <?php esc_html_e('Enable Debug Mode', 'stageguard'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Password Protection', 'stageguard'); ?></th>
                        <td>
                            <label for="password_protection">
                                <input type="checkbox" id="password_protection" name="password_protection" <?php checked($current_password_protection); ?>>
                                <?php esc_html_e('Enable Password Protection (Redirects to WordPress login)', 'stageguard'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('IP Restriction', 'stageguard'); ?></th>
                        <td>
                            <label for="ip_restriction">
                                <input type="checkbox" id="ip_restriction" name="ip_restriction" <?php checked($current_ip_restriction); ?>>
                                <?php esc_html_e('Enable IP Restriction', 'stageguard'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed IPs', 'stageguard'); ?></th>
                        <td>
                            <textarea id="allowed_ips" name="allowed_ips" rows="5" cols="50" class="large-text"><?php echo esc_textarea($current_allowed_ips); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Enter one IP address per line. Supports individual IPs (192.168.1.1), CIDR notation (192.168.1.0/24), and IP ranges (192.168.1.1-192.168.1.10).', 'stageguard'); ?>
                                <br>
                                <?php esc_html_e('Your current IP address is:', 'stageguard'); ?> <strong><?php echo esc_html($current_user_ip); ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'stageguard'), 'primary', 'stageguard_settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Update wp-config.php with a constant value
     *
     * @param string $constant The constant name
     * @param bool   $value    The value to set
     */
    private function update_wp_config($constant, $value)
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_config_file = ABSPATH . 'wp-config.php';

        if (!is_writable($wp_config_file)) {
            add_settings_error('stageguard', 'file_not_writable', __('wp-config.php is not writable. Please check file permissions.', 'stageguard'));
            return;
        }

        $config_content = file_get_contents($wp_config_file);
        $value_to_put = $value ? 'true' : 'false';

        if (preg_match("/define\s*\(\s*(['\"])$constant\\1\s*,\s*(.+?)\s*\);/", $config_content)) {
            $config_content = preg_replace(
                "/define\s*\(\s*(['\"])$constant\\1\s*,\s*(.+?)\s*\);/",
                "define('$constant', $value_to_put);",
                $config_content
            );
        } else {
            $config_content .= PHP_EOL . "define('$constant', $value_to_put);";
        }

        if (file_put_contents($wp_config_file, $config_content) === false) {
            add_settings_error('stageguard', 'file_not_updated', __('Failed to update wp-config.php. Please check file permissions.', 'stageguard'));
        }
    }
}
