<?php
/**
 * Balance Indicator Partial
 *
 * Displays the token balance pill indicator used in page headers.
 * JavaScript in admin.js automatically makes it clickable and adds
 * low balance warning state when balance < 1000.
 *
 * Expected variable:
 * - $aisales_balance (int) - Current token balance (prefixed)
 * - $balance (int) - Legacy fallback for unprefixed templates
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Get balance from parent template (prefixed or legacy) or default to 0.
if ( ! isset( $aisales_balance ) ) {
	$aisales_balance = isset( $balance ) ? (int) $balance : 0;
}
?>
<span class="aisales-balance-indicator">
	<span class="dashicons dashicons-money-alt"></span>
	<span id="aisales-balance-display"><?php echo esc_html( number_format( $aisales_balance ) ); ?></span>
	<?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?>
</span>
