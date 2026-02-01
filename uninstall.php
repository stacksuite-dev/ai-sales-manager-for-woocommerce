<?php
/**
 * AI Sales Manager Uninstall
 *
 * Fired when the plugin is uninstalled (deleted) via WordPress admin.
 * This file is called automatically by WordPress when the plugin is deleted.
 *
 * @package AISales_Sales_Manager
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
 * - Term meta from wp_termmeta table (AISales-specific only)
 * - Transients with our prefix
 */

// =============================================================================
// OPTIONS CLEANUP
// =============================================================================

$aisales_options_to_delete = array(
	'aisales_api_key',
	'aisales_user_email',
	'aisales_balance',
	'aisales_domain',
	'aisales_store_context',
	'aisales_abandoned_cart_settings',
	'aisales_abandoned_cart_email_templates',
);

foreach ( $aisales_options_to_delete as $aisales_option ) {
	delete_option( $aisales_option );
}

// =============================================================================
// USER META CLEANUP
// =============================================================================

// Delete user meta for all users using WordPress API.
// delete_metadata with empty string for object_id and delete_all=true removes for all users.
$aisales_user_meta_keys = array(
	'aisales_chat_visited',
);

foreach ( $aisales_user_meta_keys as $aisales_meta_key ) {
	delete_metadata( 'user', 0, $aisales_meta_key, '', true );
}

// =============================================================================
// TERM META CLEANUP
// =============================================================================

// Delete AISales-specific term meta from all terms using WordPress API.
// Note: We only delete our own meta keys, not Yoast/RankMath meta that we also write to.
$aisales_term_meta_keys = array(
	'aisales_seo_title',
	'aisales_meta_description',
);

foreach ( $aisales_term_meta_keys as $aisales_meta_key ) {
	delete_metadata( 'term', 0, $aisales_meta_key, '', true );
}

// =============================================================================
// TRANSIENTS CLEANUP
// =============================================================================

// Delete known transients used by the plugin.
// These are the cache transients used for store summary data.
$aisales_transients_to_delete = array(
	'aisales_empty_desc_count',
	'aisales_no_image_count',
);

foreach ( $aisales_transients_to_delete as $aisales_transient ) {
	delete_transient( $aisales_transient );
}

// =============================================================================
// ABANDONED CART TABLE CLEANUP
// =============================================================================

global $wpdb;

$aisales_cart_table = $wpdb->prefix . 'aisales_abandoned_carts';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup of custom table.
$wpdb->query( "DROP TABLE IF EXISTS {$aisales_cart_table}" );

// Clear any object cache data.
wp_cache_delete( 'aisales_empty_desc_count' );
wp_cache_delete( 'aisales_no_image_count' );
wp_cache_flush();
