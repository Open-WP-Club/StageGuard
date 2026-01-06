<?php
/**
 * Uninstall StageGuard
 *
 * Removes all plugin data when the plugin is deleted.
 *
 * @package StageGuard
 */

// Exit if accessed directly or if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all StageGuard options.
 *
 * @return void
 */
function stageguard_delete_options(): void {
	$options = array(
		'stageguard_debug_mode',
		'stageguard_password_protection',
		'stageguard_ip_restriction',
		'stageguard_allowed_ips',
		'stageguard_woocommerce_activated',
		'stageguard_search_engine_visibility_activated',
		'stageguard_logs',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

// Delete all plugin options.
stageguard_delete_options();

// Note: We don't revert WooCommerce or WordPress settings here as they might
// have been manually configured by the user. If you want to revert these
// settings, uncomment the following lines:

// Revert WooCommerce Coming Soon mode.
// if ( class_exists( 'WooCommerce' ) ) {
//     delete_option( 'woocommerce_coming_soon' );
// }

// Revert WordPress search engine visibility.
// update_option( 'blog_public', 1 );
