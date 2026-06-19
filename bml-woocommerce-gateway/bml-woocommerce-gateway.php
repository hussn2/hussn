<?php
/**
 * Plugin Name: BML Connect Gateway for WooCommerce
 * Plugin URI: https://github.com/hussn2/hussn
 * Description: Accept online payments through BML Connect (Bank of Maldives hosted payment gateway). Supports classic and block-based checkout, MVR and USD.
 * Version: 1.1.0
 * Author: hussn2
 * Author URI: https://github.com/hussn2
 * Text Domain: bml-woocommerce-gateway
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 8.2
 * WC tested up to: 10.8
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package BML_WooCommerce_Gateway
 */

defined( 'ABSPATH' ) || exit;

define( 'BML_WC_GATEWAY_VERSION', '1.1.0' );
define( 'BML_WC_GATEWAY_FILE', __FILE__ );
define( 'BML_WC_GATEWAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'BML_WC_GATEWAY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Show an admin notice and bail out if WooCommerce is not active.
 */
function bml_wc_gateway_missing_wc_notice() {
	echo '<div class="error"><p>';
	echo esc_html__( 'BML Connect Gateway for WooCommerce requires WooCommerce to be installed and active.', 'bml-woocommerce-gateway' );
	echo '</p></div>';
}

/**
 * Bootstrap the gateway once all plugins are loaded.
 */
function bml_wc_gateway_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'bml_wc_gateway_missing_wc_notice' );
		return;
	}

	require_once BML_WC_GATEWAY_PATH . 'includes/class-bml-client.php';
	require_once BML_WC_GATEWAY_PATH . 'includes/class-wc-gateway-bml.php';

	// Register the gateway with WooCommerce.
	add_filter(
		'woocommerce_payment_gateways',
		function ( $gateways ) {
			$gateways[] = 'WC_Gateway_BML';
			return $gateways;
		}
	);
}
add_action( 'plugins_loaded', 'bml_wc_gateway_init' );

/**
 * Declare compatibility with HPOS (custom order tables) and Cart/Checkout Blocks.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', BML_WC_GATEWAY_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', BML_WC_GATEWAY_FILE, true );
		}
	}
);

/**
 * Register the WooCommerce Blocks payment method integration.
 */
add_action(
	'woocommerce_blocks_payment_method_type_registration',
	function ( $payment_method_registry ) {
		require_once BML_WC_GATEWAY_PATH . 'includes/class-bml-blocks-support.php';
		$payment_method_registry->register( new WC_Gateway_BML_Blocks_Support() );
	}
);

/**
 * Add a "Settings" link on the plugins list row.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bml' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'bml-woocommerce-gateway' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);
