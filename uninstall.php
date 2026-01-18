<?php
/**
 * WooAI Sales Manager Uninstall
 *
 * Fired when the plugin is uninstalled (deleted) via WordPress admin.
 * This file is called automatically by WordPress when the plugin is deleted.
 *
 * @package WooAI_Sales_Manager
 * @since 1.1.0
 */

// Exit if accessed directly or not in uninstall context.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up all plugin data.
 *
 * Removes:
 * - Plugin options from wp_options table
 * - User meta from wp_usermeta table
 * - Term meta from wp_termmeta table (WooAI-specific only)
 */

// =============================================================================
// OPTIONS CLEANUP
// =============================================================================

$options_to_delete = array(
	'wooai_api_key',
	'wooai_user_email',
	'wooai_balance',
	'wooai_domain',
	'wooai_store_context',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// =============================================================================
// USER META CLEANUP
// =============================================================================

// Delete user meta for all users.
// Using direct query for efficiency with large user bases.
global $wpdb;

$user_meta_keys = array(
	'wooai_chat_visited',
);

foreach ( $user_meta_keys as $meta_key ) {
	$wpdb->delete(
		$wpdb->usermeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

// =============================================================================
// TERM META CLEANUP
// =============================================================================

// Delete WooAI-specific term meta from product categories.
// Note: We only delete our own meta keys, not Yoast/RankMath meta that we also write to.
$term_meta_keys = array(
	'wooai_seo_title',
	'wooai_meta_description',
);

foreach ( $term_meta_keys as $meta_key ) {
	$wpdb->delete(
		$wpdb->termmeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

// =============================================================================
// TRANSIENTS CLEANUP (if any were added in future versions)
// =============================================================================

// Delete any transients with our prefix.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_wooai_%',
		'_transient_timeout_wooai_%'
	)
);

// Clear any cached data.
wp_cache_flush();
