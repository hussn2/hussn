<?php
/**
 * Self-contained BML Connect API client built on the WordPress HTTP API.
 *
 * Reimplements just the two calls the gateway needs (create + retrieve a
 * transaction) so the plugin carries no Composer / Guzzle dependency.
 *
 * @package BML_WooCommerce_Gateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minimal client for the BML Connect "transactions" resource.
 */
class BML_Client {

	const PROD_BASE_URL    = 'https://api.merchants.bankofmaldives.com.mv/public/';
	const SANDBOX_BASE_URL = 'https://api.uat.merchants.bankofmaldives.com.mv/public/';
	const API_VERSION      = '2.0';
	const SIGN_METHOD      = 'sha1';

	/**
	 * Merchant API key (sent as the Authorization header).
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Merchant application id.
	 *
	 * @var string
	 */
	private $app_id;

	/**
	 * Environment: "sandbox" or "production".
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Merchant API key.
	 * @param string $app_id  Merchant application id.
	 * @param string $mode    "sandbox" or "production".
	 */
	public function __construct( $api_key, $app_id, $mode = 'production' ) {
		$this->api_key = $api_key;
		$this->app_id  = $app_id;
		$this->mode    = ( 'sandbox' === $mode ) ? 'sandbox' : 'production';
	}

	/**
	 * Resolve the base URL for the configured environment.
	 *
	 * @return string
	 */
	public function base_url() {
		return ( 'sandbox' === $this->mode ) ? self::SANDBOX_BASE_URL : self::PROD_BASE_URL;
	}

	/**
	 * Create a transaction.
	 *
	 * @param array $payload Transaction fields (amount, currency, localId, ...).
	 * @return array|WP_Error Decoded response, or WP_Error on failure.
	 */
	public function create_transaction( array $payload ) {
		$payload = array_merge(
			array(
				'apiVersion' => self::API_VERSION,
				'signMethod' => self::SIGN_METHOD,
				'appId'      => $this->app_id,
			),
			$payload
		);

		$response = wp_remote_post(
			$this->base_url() . 'transactions',
			array(
				'timeout' => 45,
				'headers' => $this->headers(),
				'body'    => wp_json_encode( $payload ),
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * Retrieve a transaction by id.
	 *
	 * @param string $transaction_id BML transaction id.
	 * @return array|WP_Error Decoded response, or WP_Error on failure.
	 */
	public function get_transaction( $transaction_id ) {
		$response = wp_remote_get(
			$this->base_url() . 'transactions/' . rawurlencode( $transaction_id ),
			array(
				'timeout' => 45,
				'headers' => $this->headers(),
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * Build the request headers.
	 *
	 * @return array
	 */
	private function headers() {
		return array(
			'Authorization' => $this->api_key,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		);
	}

	/**
	 * Decode a WP HTTP response and normalise errors into WP_Error.
	 *
	 * @param array|WP_Error $response Raw response from the WP HTTP API.
	 * @return array|WP_Error
	 */
	private function handle_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = '';
			if ( is_array( $data ) && ! empty( $data['message'] ) ) {
				$message = $data['message'];
			} elseif ( is_array( $data ) && ! empty( $data['error'] ) ) {
				$message = is_string( $data['error'] ) ? $data['error'] : wp_json_encode( $data['error'] );
			} else {
				$message = sprintf(
					/* translators: %d: HTTP status code. */
					__( 'BML Connect returned an unexpected response (HTTP %d).', 'bml-woocommerce-gateway' ),
					$code
				);
			}

			return new WP_Error( 'bml_api_error', $message, array( 'status' => $code, 'body' => $body ) );
		}

		if ( null === $data ) {
			return new WP_Error( 'bml_api_invalid_json', __( 'BML Connect returned an invalid response.', 'bml-woocommerce-gateway' ), array( 'body' => $body ) );
		}

		return $data;
	}
}
