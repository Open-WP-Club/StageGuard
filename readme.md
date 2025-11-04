# StageGuard

## Description

StageGuard is a WordPress plugin designed to clearly indicate and manage a staging environment. It provides various features to protect your staging site, prevent accidental emails, and manage plugin activations.

## Features

- Displays a prominent message in the admin panel and on the frontend indicating a staging environment - only if WooCommerce is not installed
- Automatically deactivates specific plugins on staging environments
- Prevents activation of certain plugins and provides a custom error message
- Activates Coming Soon mode for WooCommerce (if installed)
- Modifies search engine visibility settings
- Provides password protection for the staging site (redirects to WordPress login)
- **Advanced IP restriction with multiple formats:**
  - Individual IP addresses (e.g., `192.168.1.1`)
  - CIDR notation for IP ranges (e.g., `192.168.1.0/24`)
  - IP address ranges (e.g., `192.168.1.1-192.168.1.10`)
  - Supports both IPv4 and IPv6
  - Automatic localhost whitelisting for safety
  - Smart proxy header detection (X-Forwarded-For, X-Real-IP)
- Filters robots.txt to discourage search engine indexing (no physical file modification)
- Catches and logs emails sent from the staging environment
- Includes WP-CLI commands for managing the plugin
- Clean, modular architecture with separated concerns

## Deactivated Plugins

StageGuard will deactivate the following plugins:

1. [BunnyCDN](https://wordpress.org/plugins/bunnycdn/)
2. [Redis Cache](https://wordpress.org/plugins/redis-cache/)
3. [Google Listings and Ads](https://wordpress.org/plugins/google-listings-and-ads/)
4. [Metorik Helper](https://wordpress.org/plugins/metorik-helper/)
5. [Order Sync with Zendesk for WooCommerce](https://wordpress.org/plugins/order-sync-with-zendesk-for-woocommerce/)
6. [Redis Object Cache](https://wordpress.org/plugins/redis-object-cache/)
7. [RunCloud Hub](https://wordpress.org/plugins/runcloud-hub/)
8. [Site Kit by Google](https://wordpress.org/plugins/google-site-kit/)
9. [Super Page Cache for Cloudflare](https://wordpress.org/plugins/wp-cloudflare-page-cache/)
10. [WooCommerce - ShipStation Integration](https://wordpress.org/plugins/woocommerce-shipstation-integration/)
11. [WP OPcache](https://wordpress.org/plugins/wp-opcache/)
12. [Headers Security Advanced & HSTS WP](https://wordpress.org/plugins/headers-security-advanced-hsts-wp/)
13. [WP-Rocket](https://wp-rocket.me/)
14. [Tidio Chat](https://wordpress.org/plugins/tidio-live-chat/)
15. [LiteSpeed Cache](https://wordpress.org/plugins/litespeed-cache/)
16. [WP Fastest Cache](https://wordpress.org/plugins/wp-fastest-cache/)
17. [PhastPress](https://wordpress.org/plugins/phastpress/)
18. [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/)
19. [WP Optimize](https://wordpress.org/plugins/wp-optimize/)
20. [Autoptimize](https://wordpress.org/plugins/autoptimize/)
21. [NitroPack](https://wordpress.org/plugins/nitropack/)
22. [WP Sync DB](https://github.com/wp-sync-db/wp-sync-db)
23. [WP Sync DB Media Files](https://github.com/wp-sync-db/wp-sync-db-media-files)
24. [UpdraftPlus](https://wordpress.org/plugins/updraftplus/)
25. [Mailchimp for WooCommerce](https://wordpress.org/plugins/mailchimp-for-woocommerce/)

## Installation

1. Upload the `stageguard` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > StageGuard to configure the plugin.

## Configuration

Navigate to **Settings > StageGuard** in your WordPress admin panel to configure:

### 1. Debug Mode
Toggle WordPress debug mode on or off. When enabled, WP_DEBUG constant will be set to true in wp-config.php.

### 2. Password Protection
Enable to redirect non-logged-in users to the WordPress login page. This ensures only authenticated users can access the staging site.

### 3. IP Restriction
Enable IP-based access control to restrict who can view the staging site.

### 4. Allowed IPs
Specify IP addresses that should have access to the staging site. Supports multiple formats:

- **Individual IPs**: `192.168.1.1` (one per line)
- **CIDR Notation**: `192.168.1.0/24` (allows entire subnet)
- **IP Ranges**: `192.168.1.1-192.168.1.10` (allows range of IPs)
- **IPv6 Support**: `2001:db8::1` or `2001:db8::/32`

The system automatically whitelists `127.0.0.1` and `::1` (localhost) for safety. Your current IP address is displayed in the settings page for convenience.

## Viewing Logs

You can view the StageGuard logs in two ways:

1. **Admin Interface**: Go to Settings > StageGuard Logs in the WordPress admin area.
2. **WP-CLI**: Use the command `wp stageguard show_log` to view logs in the terminal.

## WP-CLI Commands

StageGuard supports the following WP-CLI commands:

- `wp stageguard debug_mode <on|off>`: Toggle debug mode on or off.
- `wp stageguard show_log [--lines=<number>]`: Display the StageGuard log. Use the `--lines` option to specify the number of lines to show (default is 50).

## Architecture

StageGuard features a clean, modular architecture:

```
StageGuard/
├── stageguard.php                    # Main plugin file
└── includes/
    ├── class-stageguard-admin.php    # Admin UI and settings
    ├── class-stageguard-security.php # Password & IP protection
    └── class-stageguard-cli.php      # WP-CLI commands
```

This separation of concerns makes the code:
- Easier to maintain and test
- More secure with focused responsibilities
- Simpler to extend with new features

## Troubleshooting

If you're having issues with StageGuard, check the following:

1. **Logging Issues**: Ensure that the web server has write permissions to the `wp-content` directory for logging.
2. **Staging Indicator Not Showing**: Check if your theme is properly loading the `wp_head` action.
3. **Password Protection Not Working**: Make sure you're not already logged in to WordPress. The protection only affects non-authenticated users.
4. **IP Restriction Issues**:
   - Verify your IP format is correct (use the formats shown in Configuration section)
   - Check if you're behind a proxy - the plugin detects `X-Forwarded-For` headers
   - Remember that `127.0.0.1` and `::1` are always whitelisted
5. **robots.txt Not Updating**: The plugin uses WordPress's virtual robots.txt. If you have a physical `robots.txt` file, it will take precedence. Delete the physical file to use the plugin's filter.

## Requirements

- **WordPress**: 6.4 or higher
- **PHP**: 8.0 or higher
- **License**: GPL-2.0-or-later

## Changelog

### Version 1.0.0
- Refactored plugin architecture with separated concerns
- Added advanced IP restriction with CIDR notation support
- Added IP range support (e.g., 192.168.1.1-192.168.1.10)
- Added IPv6 support for IP restrictions
- Improved robots.txt handling (filter-only, no file modification)
- Enhanced security with better input sanitization
- Smart proxy header detection for accurate IP identification
- Automatic localhost whitelisting
- Updated to Open-WP-Club ownership
- Improved code documentation and PHPDoc blocks
- Better error messages for access denied scenarios

### Version 0.2.x
- Initial release with basic staging protection features
- Plugin deactivation on staging
- Password protection and basic IP restriction
- Email catching and logging
- WP-CLI support

## Support

For support, please open an issue on the [GitHub repository](https://github.com/Open-WP-Club/StageGuard/).

## Contributing

We welcome contributions! Please feel free to submit pull requests or open issues for bugs and feature requests.

## Author

**Open-WP-Club**
- Website: <https://openwpclub.com>
- GitHub: <https://github.com/Open-WP-Club>
