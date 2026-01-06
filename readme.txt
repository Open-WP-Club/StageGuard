=== StageGuard ===
Contributors: openwpclub
Tags: staging, development, coming-soon, robots.txt, debug
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive staging environment management with Coming Soon mode, search engine blocking, IP restriction, and email prevention.

== Description ==

StageGuard is a comprehensive WordPress plugin designed to clearly indicate and manage staging environments. It provides essential features to protect your staging site from search engines, prevent accidental emails, and manage plugin activations.

= Key Features =

* **Staging Indicator**: Displays a prominent banner on frontend and admin indicating staging environment
* **Automatic Plugin Management**: Deactivates specific plugins that shouldn't run on staging (CDN, cache, marketing tools, etc.)
* **WooCommerce Integration**: Automatically enables Coming Soon mode for WooCommerce 9.1+
* **Search Engine Protection**: Modifies robots.txt and search engine visibility settings
* **Email Prevention**: Catches and logs all emails to prevent accidental sending
* **Password Protection**: Redirects non-logged-in users to WordPress login
* **Advanced IP Restriction**:
  - Individual IP addresses
  - CIDR notation for IP ranges (e.g., 192.168.1.0/24)
  - IP address ranges (e.g., 192.168.1.1-192.168.1.10)
  - IPv4 and IPv6 support
  - Smart proxy header detection
* **Activity Logging**: Comprehensive logging with database storage
* **WP-CLI Support**: Full command-line interface for automation

= Automatically Deactivated Plugins =

StageGuard automatically deactivates these plugin types on staging:

* **CDN & Performance**: BunnyCDN, WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Fastest Cache, Autoptimize, NitroPack, PhastPress, WP Optimize, OPcache
* **Cache**: Redis Cache, Redis Object Cache
* **Analytics & Marketing**: Google Site Kit, Google Listings and Ads, Mailchimp for WooCommerce
* **Live Chat**: Tidio Chat
* **Integrations**: ShipStation, Metorik Helper, Zendesk Order Sync
* **Hosting Tools**: RunCloud Hub, Cloudflare Page Cache
* **Backup**: UpdraftPlus
* **Security**: HSTS Headers
* **Database Sync**: WP Sync DB, WP Sync DB Media Files

= WP-CLI Commands =

* `wp stageguard debug_mode <on|off>` - Toggle debug mode
* `wp stageguard show_log [--lines=N]` - Display activity logs
* `wp stageguard clear_log` - Clear all logs

== Installation ==

1. Upload the `stageguard` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > StageGuard to configure options
4. The plugin will automatically:
   - Enable WooCommerce Coming Soon mode (if WooCommerce is installed)
   - Discourage search engines
   - Deactivate staging-incompatible plugins

== Frequently Asked Questions ==

= Does this plugin work with WooCommerce? =

Yes! StageGuard automatically detects WooCommerce and enables Coming Soon mode on WooCommerce 9.1 or higher.

= Will emails be sent from my staging site? =

No. StageGuard intercepts all emails using the `pre_wp_mail` filter and logs them instead of sending. Check Settings > StageGuard to view blocked emails.

= How do I allow specific IP addresses? =

Go to Settings > StageGuard, enable IP Restriction, and add allowed IPs in the "Allowed IPs" field. Supports:
- Individual IPs: `192.168.1.1`
- CIDR notation: `192.168.1.0/24`
- IP ranges: `192.168.1.1-192.168.1.10`
- IPv6: `2001:db8::1` or `2001:db8::/32`

= Can I add more plugins to the auto-deactivation list? =

Yes! Use the `stageguard_plugins_to_handle` filter:

`
add_filter( 'stageguard_plugins_to_handle', function( $plugins ) {
    $plugins[] = 'my-plugin/my-plugin.php';
    return $plugins;
} );
`

= Does this modify my robots.txt file? =

No. StageGuard uses WordPress's virtual robots.txt system via the `robots_txt` filter. If you have a physical robots.txt file, delete it to use the plugin's filter.

= How do I view logs? =

Two ways:
1. Admin interface: Settings > StageGuard (logs section)
2. WP-CLI: `wp stageguard show_log`

= What happens when I deactivate the plugin? =

The plugin stops all protections immediately. Settings are preserved in case you reactivate. To completely remove all data, delete the plugin (triggers uninstall.php).

== Screenshots ==

1. StageGuard settings page
2. Staging environment indicator banner
3. Admin notice showing staging environment
4. Activity logs view

== Changelog ==

= 1.0.1 =
* Added database-based logging system (replacing file-based logs)
* Added new CLI command: `clear_log`
* Improved namespace structure
* Enhanced security with better input sanitization
* Added proper uninstall.php for cleanup
* Created missing assets and documentation

= 1.0.0 =
* Refactored plugin architecture with separated concerns
* Added advanced IP restriction with CIDR notation support
* Added IP range support (e.g., 192.168.1.1-192.168.1.10)
* Added IPv6 support for IP restrictions
* Improved robots.txt handling (filter-only, no file modification)
* Enhanced security with better input sanitization
* Smart proxy header detection for accurate IP identification
* Automatic localhost whitelisting
* Updated to Open-WP-Club ownership
* Improved code documentation and PHPDoc blocks
* Better error messages for access denied scenarios

= 0.2.x =
* Initial release with basic staging protection features
* Plugin deactivation on staging
* Password protection and basic IP restriction
* Email catching and logging
* WP-CLI support

== Upgrade Notice ==

= 1.0.1 =
Logging system migrated to database. Old file-based logs will not be imported automatically.

= 1.0.0 =
Major refactoring with improved security and advanced IP restriction features. Review your settings after updating.

== Privacy Policy ==

StageGuard stores activity logs in your WordPress database, including:
* Timestamps of plugin activations/deactivations
* User login events (username and user ID)
* Blocked emails (recipient and subject)
* IP addresses (when IP restriction is enabled)

All data is stored locally in your WordPress database and never transmitted externally. Logs are automatically limited to the most recent 1000 entries.

== Support ==

For support, please visit:
* GitHub: https://github.com/Open-WP-Club/StageGuard/
* Open-WP-Club: https://openwpclub.com

== Contributing ==

We welcome contributions! Please submit pull requests or open issues on our GitHub repository.
