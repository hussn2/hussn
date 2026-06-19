<?php
/**
 * BML Connect WooCommerce payment gateway.
 *
 * @package BML_WooCommerce_Gateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gateway implementation.
 */
class WC_Gateway_BML extends WC_Payment_Gateway {

	/**
	 * Currencies supported by BML Connect.
	 *
	 * @var array
	 */
	const SUPPORTED_CURRENCIES = array( 'MVR', 'USD' );

	/**
	 * Whether sandbox mode is enabled.
	 *
	 * @var bool
	 */
	protected $test_mode;

	/**
	 * Whether debug logging is enabled.
	 *
	 * @var bool
	 */
	protected $debug;

	/**
	 * Merchant API key.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Merchant application id.
	 *
	 * @var string
	 */
	protected $app_id;

	/**
	 * Shared logger instance.
	 *
	 * @var WC_Logger|null
	 */
	private static $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'bml';
		$this->method_title       = __( 'BML Connect', 'bml-woocommerce-gateway' );
		$this->method_description = __( 'Accept payments through BML Connect (Bank of Maldives). Customers are redirected to the BML hosted payment page to complete payment.', 'bml-woocommerce-gateway' );
		$this->has_fields         = false;
		$this->supports           = array( 'products' );
		$this->icon               = apply_filters( 'bml_wc_gateway_icon', '' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->test_mode   = 'yes' === $this->get_option( 'test_mode' );
		$this->debug       = 'yes' === $this->get_option( 'debug' );
		$this->api_key     = $this->test_mode ? $this->get_option( 'sandbox_api_key' ) : $this->get_option( 'live_api_key' );
		$this->app_id      = $this->test_mode ? $this->get_option( 'sandbox_app_id' ) : $this->get_option( 'live_app_id' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Server-to-server callback + browser return handler: ?wc-api=wc_gateway_bml.
		add_action( 'woocommerce_api_wc_gateway_bml', array( $this, 'handle_callback' ) );
	}

	/**
	 * Define admin settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'         => array(
				'title'   => __( 'Enable/Disable', 'bml-woocommerce-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable BML Connect', 'bml-woocommerce-gateway' ),
				'default' => 'no',
			),
			'title'           => array(
				'title'       => __( 'Title', 'bml-woocommerce-gateway' ),
				'type'        => 'text',
				'description' => __( 'Payment method title shown to customers at checkout.', 'bml-woocommerce-gateway' ),
				'default'     => __( 'Card / BML Connect', 'bml-woocommerce-gateway' ),
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => __( 'Description', 'bml-woocommerce-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description shown to customers at checkout.', 'bml-woocommerce-gateway' ),
				'default'     => __( 'Pay securely via BML Connect. You will be redirected to the Bank of Maldives payment page.', 'bml-woocommerce-gateway' ),
			),
			'test_mode'       => array(
				'title'       => __( 'Sandbox mode', 'bml-woocommerce-gateway' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable sandbox (test) mode', 'bml-woocommerce-gateway' ),
				'default'     => 'yes',
				'description' => __( 'Use the BML Connect sandbox environment for testing. Disable to take live payments.', 'bml-woocommerce-gateway' ),
			),
			'live_api_key'    => array(
				'title'       => __( 'Live API key', 'bml-woocommerce-gateway' ),
				'type'        => 'password',
				'description' => __( 'Production API key from the BML Merchant Portal (sent as the Authorization header).', 'bml-woocommerce-gateway' ),
				'default'     => '',
			),
			'live_app_id'     => array(
				'title'       => __( 'Live App ID', 'bml-woocommerce-gateway' ),
				'type'        => 'text',
				'description' => __( 'Production application id from the BML Merchant Portal.', 'bml-woocommerce-gateway' ),
				'default'     => '',
			),
			'sandbox_api_key' => array(
				'title'       => __( 'Sandbox API key', 'bml-woocommerce-gateway' ),
				'type'        => 'password',
				'description' => __( 'Sandbox API key from the BML Merchant Portal (sent as the Authorization header).', 'bml-woocommerce-gateway' ),
				'default'     => '',
			),
			'sandbox_app_id'  => array(
				'title'       => __( 'Sandbox App ID', 'bml-woocommerce-gateway' ),
				'type'        => 'text',
				'description' => __( 'Sandbox application id from the BML Merchant Portal.', 'bml-woocommerce-gateway' ),
				'default'     => '',
			),
			'debug'           => array(
				'title'       => __( 'Debug log', 'bml-woocommerce-gateway' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'bml-woocommerce-gateway' ),
				'default'     => 'no',
				'description' => sprintf(
					/* translators: %s: log location. */
					__( 'Log BML Connect API requests and responses to %s.', 'bml-woocommerce-gateway' ),
					'<code>WooCommerce &gt; Status &gt; Logs</code>'
				),
			),
		);
	}

	/**
	 * Whether the gateway can be used. Hides it for unsupported currencies or missing credentials.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( ! in_array( get_woocommerce_currency(), self::SUPPORTED_CURRENCIES, true ) ) {
			return false;
		}

		if ( empty( $this->api_key ) || empty( $this->app_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Instantiate the BML client with the active credentials.
	 *
	 * @return BML_Client
	 */
	protected function get_client() {
		return new BML_Client( $this->api_key, $this->app_id, $this->test_mode ? 'sandbox' : 'production' );
	}

	/**
	 * Process the payment: create a transaction and redirect to the BML page.
	 *
	 * @param int $order_id Order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array( 'result' => 'failure' );
		}

		$return_url = add_query_arg(
			array(
				'wc-api'    => 'wc_gateway_bml',
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
			),
			home_url( '/' )
		);

		$payload = array(
			'amount'            => (int) round( (float) $order->get_total() * 100 ),
			'currency'          => $order->get_currency(),
			'localId'           => (string) $order->get_id(),
			'customerReference' => $order->get_order_number(),
			'redirectUrl'       => $return_url,
		);

		$this->log( 'Creating transaction for order #' . $order->get_id() . ': ' . wp_json_encode( $payload ) );

		$transaction = $this->get_client()->create_transaction( $payload );

		if ( is_wp_error( $transaction ) ) {
			$this->log( 'Create transaction failed: ' . $transaction->get_error_message(), 'error' );
			wc_add_notice( __( 'Unable to start the BML payment. Please try again or choose another payment method.', 'bml-woocommerce-gateway' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( empty( $transaction['url'] ) || empty( $transaction['id'] ) ) {
			$this->log( 'Create transaction returned no url/id: ' . wp_json_encode( $transaction ), 'error' );
			wc_add_notice( __( 'BML Connect did not return a payment URL. Please try again.', 'bml-woocommerce-gateway' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( '_bml_transaction_id', $transaction['id'] );
		$order->update_status( 'pending', __( 'Awaiting BML Connect payment.', 'bml-woocommerce-gateway' ) );
		$order->save();

		return array(
			'result'   => 'success',
			'redirect' => $transaction['url'],
		);
	}

	/**
	 * Handle the BML callback (server-to-server webhook and browser redirect).
	 *
	 * Re-verifies the transaction server-side before completing the order.
	 */
	public function handle_callback() {
		$order = $this->resolve_order_from_request();

		if ( ! $order ) {
			$this->log( 'Callback could not resolve an order. Query: ' . wp_json_encode( $_GET ), 'error' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_die( esc_html__( 'Invalid BML payment callback.', 'bml-woocommerce-gateway' ), '', array( 'response' => 400 ) );
		}

		$transaction_id = $order->get_meta( '_bml_transaction_id' );

		if ( empty( $transaction_id ) ) {
			$this->log( 'No transaction id stored for order #' . $order->get_id(), 'error' );
			$this->finish_callback( $order, 'error', __( 'No BML transaction was found for this order.', 'bml-woocommerce-gateway' ) );
		}

		$transaction = $this->get_client()->get_transaction( $transaction_id );

		if ( is_wp_error( $transaction ) ) {
			$this->log( 'Verify transaction failed for order #' . $order->get_id() . ': ' . $transaction->get_error_message(), 'error' );
			$this->finish_callback( $order, 'error', __( 'We could not verify your payment with BML. Please contact us if you were charged.', 'bml-woocommerce-gateway' ) );
		}

		$state = isset( $transaction['state'] ) ? strtoupper( (string) $transaction['state'] ) : '';
		$this->log( 'Order #' . $order->get_id() . ' transaction ' . $transaction_id . ' state: ' . $state );

		// Idempotency: if already paid, just send the customer onward.
		if ( $order->is_paid() ) {
			$this->finish_callback( $order, 'success' );
		}

		switch ( $state ) {
			case 'CONFIRMED':
				$order->payment_complete( $transaction_id );
				$order->add_order_note( sprintf( /* translators: %s: transaction id. */ __( 'BML Connect payment confirmed (transaction %s).', 'bml-woocommerce-gateway' ), $transaction_id ) );
				if ( function_exists( 'WC' ) && WC()->cart ) {
					WC()->cart->empty_cart();
				}
				$this->finish_callback( $order, 'success' );
				break;

			case 'CANCELLED':
			case 'EXPIRED':
			case 'FAILED':
				$order->update_status( 'failed', sprintf( /* translators: %s: transaction state. */ __( 'BML Connect payment %s.', 'bml-woocommerce-gateway' ), strtolower( $state ) ) );
				$this->finish_callback( $order, 'failed', __( 'Your BML payment was not completed. Please try again.', 'bml-woocommerce-gateway' ) );
				break;

			default:
				// QR_CODE_GENERATED, RESERVED, PROCESSING, etc. — leave pending.
				$order->add_order_note( sprintf( /* translators: %s: transaction state. */ __( 'BML Connect payment pending (state: %s).', 'bml-woocommerce-gateway' ), $state ) );
				$this->finish_callback( $order, 'pending', __( 'Your BML payment is being processed. We will update your order once it is confirmed.', 'bml-woocommerce-gateway' ) );
				break;
		}
	}

	/**
	 * Resolve the order referenced by the callback request, validating the order key.
	 *
	 * @return WC_Order|false
	 */
	private function resolve_order_from_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$order_id  = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		$order_key = isset( $_GET['order_key'] ) ? sanitize_text_field( wp_unslash( $_GET['order_key'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $order_id ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
			return false;
		}

		return $order;
	}

	/**
	 * Finalise the callback: redirect a browser, or emit a plain 200 for a webhook.
	 *
	 * @param WC_Order $order   Order.
	 * @param string   $result  One of success|failed|pending|error.
	 * @param string   $message Optional notice for browser visitors.
	 */
	private function finish_callback( $order, $result, $message = '' ) {
		// A server-to-server webhook won't send a browser user agent we redirect;
		// detect a browser by the presence of an Accept: text/html header.
		$is_browser = isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) ), 'text/html' );

		if ( ! $is_browser ) {
			status_header( 200 );
			echo wp_json_encode( array( 'received' => true ) );
			exit;
		}

		if ( $message ) {
			$notice_type = ( 'success' === $result || 'pending' === $result ) ? 'notice' : 'error';
			wc_add_notice( $message, $notice_type );
		}

		if ( 'success' === $result || 'pending' === $result ) {
			$redirect = $this->get_return_url( $order );
		} else {
			$redirect = $order->get_checkout_payment_url();
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Write a message to the WooCommerce log when debug is enabled.
	 *
	 * @param string $message Message.
	 * @param string $level   Log level.
	 */
	private function log( $message, $level = 'info' ) {
		if ( ! $this->debug ) {
			return;
		}

		if ( null === self::$logger ) {
			self::$logger = wc_get_logger();
		}

		self::$logger->log( $level, $message, array( 'source' => 'bml-connect' ) );
	}
}
