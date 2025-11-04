<?php
/**
 * Security features for StageGuard
 *
 * @package StageGuard
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * StageGuard Security class
 */
class StageGuard_Security
{
    /**
     * Initialize security features
     */
    public function __construct()
    {
        add_action('init', [$this, 'password_protect_staging'], 1);
        add_action('init', [$this, 'ip_restrict_staging'], 1);
    }

    /**
     * Password protect staging site by redirecting to login
     */
    public function password_protect_staging()
    {
        if (get_option('stageguard_password_protection', false)) {
            if (!is_user_logged_in() && !defined('DOING_CRON')) {
                $protocol = is_ssl() ? 'https' : 'http';
                $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
                $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                $current_url = $protocol . '://' . $host . $request_uri;
                $login_url = wp_login_url();

                // Check if we're not already on the login page to avoid redirect loops
                if (strpos($current_url, $login_url) === false) {
                    wp_safe_redirect(add_query_arg('redirect_to', urlencode($current_url), $login_url));
                    exit;
                }
            }
        }
    }

    /**
     * Restrict access by IP address
     */
    public function ip_restrict_staging()
    {
        if (!get_option('stageguard_ip_restriction', false)) {
            return;
        }

        // Skip if user is logged in
        if (is_user_logged_in()) {
            return;
        }

        $current_ip = $this->get_client_ip();
        $allowed_ips = $this->get_allowed_ips();

        if (!$this->is_ip_allowed($current_ip, $allowed_ips)) {
            $message = sprintf(
                /* translators: %s: Current IP address */
                esc_html__('Access denied. Your IP address (%s) is not allowed to view this staging site.', 'stageguard'),
                esc_html($current_ip)
            );
            wp_die($message, esc_html__('Access Denied', 'stageguard'), ['response' => 403]);
        }
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip()
    {
        // Try to get real IP if behind proxy (with validation)
        $ip_keys = ['REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));

                // For X-Forwarded-For, take the first IP (client IP)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validate IP format
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    // Only trust proxy headers from private network IPs
                    if ($key !== 'REMOTE_ADDR' && !$this->is_private_ip($ip)) {
                        continue;
                    }
                    return $ip;
                }
            }
        }

        return '';
    }

    /**
     * Check if IP is a private network IP
     *
     * @param string $ip IP address
     * @return bool
     */
    private function is_private_ip($ip)
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Get allowed IPs from settings
     *
     * @return array
     */
    private function get_allowed_ips()
    {
        $allowed_ips_raw = get_option('stageguard_allowed_ips', '');
        $allowed_ips = explode("\n", $allowed_ips_raw);
        $allowed_ips = array_map('trim', $allowed_ips);
        $allowed_ips = array_filter($allowed_ips); // Remove empty lines

        // Always whitelist localhost and private networks for safety
        $default_allowed = ['127.0.0.1', '::1'];

        return array_merge($default_allowed, $allowed_ips);
    }

    /**
     * Check if an IP address is allowed
     *
     * @param string $ip          The IP to check
     * @param array  $allowed_ips Array of allowed IPs/ranges
     * @return bool
     */
    private function is_ip_allowed($ip, $allowed_ips)
    {
        if (empty($ip)) {
            return false;
        }

        foreach ($allowed_ips as $allowed) {
            // Exact match
            if ($ip === $allowed) {
                return true;
            }

            // CIDR notation (e.g., 192.168.1.0/24)
            if (strpos($allowed, '/') !== false) {
                if ($this->ip_in_cidr($ip, $allowed)) {
                    return true;
                }
            }

            // IP range (e.g., 192.168.1.1-192.168.1.10)
            if (strpos($allowed, '-') !== false) {
                if ($this->ip_in_range($ip, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip   IP address to check
     * @param string $cidr CIDR notation (e.g., 192.168.1.0/24)
     * @return bool
     */
    private function ip_in_cidr($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);

        // Validate CIDR format
        if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        $mask = intval($mask);

        // Determine if IPv4 or IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($mask < 0 || $mask > 32) {
                return false;
            }

            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);

            if ($ip_long === false || $subnet_long === false) {
                return false;
            }

            $mask_long = -1 << (32 - $mask);

            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($mask < 0 || $mask > 128) {
                return false;
            }

            return $this->ipv6_in_cidr($ip, $subnet, $mask);
        }

        return false;
    }

    /**
     * Check if IPv6 is in CIDR range
     *
     * @param string $ip     IPv6 address to check
     * @param string $subnet IPv6 subnet
     * @param int    $mask   CIDR mask
     * @return bool
     */
    private function ipv6_in_cidr($ip, $subnet, $mask)
    {
        $ip_bin = inet_pton($ip);
        $subnet_bin = inet_pton($subnet);

        if ($ip_bin === false || $subnet_bin === false) {
            return false;
        }

        $ip_bits = '';
        $subnet_bits = '';

        for ($i = 0; $i < strlen($ip_bin); $i++) {
            $ip_bits .= str_pad(decbin(ord($ip_bin[$i])), 8, '0', STR_PAD_LEFT);
            $subnet_bits .= str_pad(decbin(ord($subnet_bin[$i])), 8, '0', STR_PAD_LEFT);
        }

        return substr($ip_bits, 0, $mask) === substr($subnet_bits, 0, $mask);
    }

    /**
     * Check if IP is in range
     *
     * @param string $ip    IP address to check
     * @param string $range IP range (e.g., 192.168.1.1-192.168.1.10)
     * @return bool
     */
    private function ip_in_range($ip, $range)
    {
        list($start_ip, $end_ip) = array_map('trim', explode('-', $range));

        // Validate IPs
        if (!filter_var($start_ip, FILTER_VALIDATE_IP) || !filter_var($end_ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Only support IPv4 ranges for simplicity
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_long = ip2long($ip);
            $start_long = ip2long($start_ip);
            $end_long = ip2long($end_ip);

            if ($ip_long === false || $start_long === false || $end_long === false) {
                return false;
            }

            return ($ip_long >= $start_long && $ip_long <= $end_long);
        }

        return false;
    }
}
