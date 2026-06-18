<?php
/**
 * WooCommerce Cart/Checkout Blocks integration for the BML Connect gateway.
 *
 * @package BML_WooCommerce_Gateway
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payment\Integrations\AbstractPaymentMethodType;

/**
 * Registers the gateway with the Blocks checkout.
 */
final class WC_Gateway_BML_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id (matches the gateway id).
	 *
	 * @var string
	 */
	protected $name = 'bml';

	/**
	 * Stored gateway settings.
	 *
	 * @var array
	 */
	private $gateway_settings;

	/**
	 * Load settings.
	 */
	public function initialize() {
		$this->gateway_settings = get_option( 'woocommerce_bml_settings', array() );
	}

	/**
	 * Whether the payment method is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		$gateways = WC()->payment_gateways()->payment_gateways();

		if ( isset( $gateways[ $this->name ] ) ) {
			return $gateways[ $this->name ]->is_available();
		}

		return ! empty( $this->gateway_settings['enabled'] ) && 'yes' === $this->gateway_settings['enabled'];
	}

	/**
	 * Register the front-end script and return its handle.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$handle = 'wc-bml-blocks';

		wp_register_script(
			$handle,
			BML_WC_GATEWAY_URL . 'assets/js/blocks.js',
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
			BML_WC_GATEWAY_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'bml-woocommerce-gateway' );
		}

		return array( $handle );
	}

	/**
	 * Data exposed to the front-end script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => isset( $this->gateway_settings['title'] ) ? $this->gateway_settings['title'] : __( 'Card / BML Connect', 'bml-woocommerce-gateway' ),
			'description' => isset( $this->gateway_settings['description'] ) ? $this->gateway_settings['description'] : '',
			'supports'    => array( 'products' ),
		);
	}
}
