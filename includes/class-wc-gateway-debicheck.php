<?php
/**
 * DebiCheck Payment Gateway
 *
 * Provides a DebiCheck Payment Gateway.
 *
 * @class  woocommerce_debicheck_gateway
 * @package WooCommerce
 * @category Payment Gateways
 * @author Jason Norwood-Young
 */
class WC_Gateway_DebiCheck extends WC_Payment_Gateway {
    function __construct() {
        $this->id = 'debicheck';
        $this->method_title = __( 'DebiCheck', 'woocommerce-gateway-debicheck' );
        $this->method_description = sprintf( __( 'DebiCheck works by sending the user to e-Mandate to enter their payment information.', 'woocommerce-gateway-debicheck' ), '<a href="https://www.electronicmandate.com">', '</a>' );
        $this->has_fields = true;

        $this->supports = array( 
            'subscriptions', 
            // 'subscription_cancellation', 
            // 'subscription_suspension', 
            // 'subscription_reactivation',
            // 'subscription_amount_changes',
            // 'subscription_date_changes',
        );

        $this->init_form_fields();
        $this->init_settings();
        
        $this->url = $this->get_option( 'url' );
        // print_r($this->emandate_url);
        // die();
        $this->enable_logging   = 'yes' === $this->get_option( 'enable_logging' );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 0.0.1
	 */
	public function init_form_fields() {
		// TODO: String(s) to identify the mandate on the debtor's account
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-debicheck' ),
				'label'       => __( 'Enable DebiCheck', 'woocommerce-gateway-debicheck' ),
				'type'        => 'checkbox',
				'description' => __( 'This controls whether or not this gateway is enabled within WooCommerce.', 'woocommerce-gateway-debicheck' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'url' => array(
				'title'       => __( 'DebiCheck API', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
				'description' => __( 'Eg. https://fintecapisandbox.azurewebsites.net/api.', 'woocommerce-gateway-debicheck' ),
				'default'     => __( 'https://fintecapisandbox.azurewebsites.net/api', 'woocommerce-gateway-debicheck' ),
				'desc_tip'    => false,
			),
			'apikey' => array(
				'title'       => __( 'API Key', 'woocommerce-gateway-debicheck' ),
				'type'        => 'password',
				'description' => __( 'API Key for the E-Mandate platform.', 'woocommerce-gateway-debicheck' ),
				'default'     => "",
				'desc_tip'    => false,
			),
			'enable_logging' => array(
				'title'   => __( 'Enable Logging', 'woocommerce-gateway-debicheck' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable transaction logging for gateway.', 'woocommerce-gateway-debicheck' ),
				'default' => 'no',
			),
			'tracking_allowed' => array(
				'title'   => __( 'Enable Tracking', 'woocommerce-gateway-debicheck' ),
				'type'    => 'checkbox',
				'label'   => 'Enable Tracking',
				'description'   => __( 'NOTE: This will most likely result in a higher cost per transaction.', 'woocommerce-gateway-debicheck' ),
				'default' => 'no',
				'desc_tip'    => false,
			),
			'tracking_days' => array(
				'title'       => __( 'Tracking Days', 'woocommerce-gateway-debicheck' ),
				'type'        => 'number',
				'description' => __( 'Number of days to retry if not enough funds available.', 'woocommerce-gateway-debicheck.' ),
				'default'     => __( '2' ),
				'desc_tip'    => true,
			),
			'bank' => array(
				'title'       => __( 'Bank', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
				'description' => __( 'Eg. ABSA' ),
				'desc_tip'    => false,
			),
			'currency' => array(
				'title'       => __( 'Currency', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
				'description' => __( 'Currency to perform transactions in.' ),
				'default'     => __( 'ZAR' ),
				'desc_tip'    => false,
			),
			// 'mandate_identification' => array(
			// 	'title'       => __( 'Creditor Account Number', 'woocommerce-gateway-debicheck' ),
			// 	'type'        => 'text',
			// ),
			'creditor_account_number' => array(
				'title'       => __( 'Creditor Account Number', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
			'creditor_account_name' => array(
				'title'       => __( 'Creditor Account Name', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
			'creditor_branch_code' => array(
				'title'       => __( 'Creditor Branch Code', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
			'creditor_email' => array(
				'title'       => __( 'Creditor Email', 'woocommerce-gateway-debicheck' ),
				'type'        => 'email',
			),
			'creditor_phone_number' => array(
				'title'       => __( 'Creditor Phone Number', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
			'creditor_scheme_name' => array(
				'title'       => __( 'Scheme Name', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
			'creditor_short_name' => array(
				'title'       => __( 'Creditor Short Name', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
			'creditor_ultimate_name' => array(
				'title'       => __( 'Creditor Ultimate Name', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
			),
		);
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        // Mark as on-hold (we're awaiting the payment)
        $order->update_status( 'on-hold', __( 'Awaiting E-Mandate', 'wc-gateway-debicheck' ) );
        // Reduce stock levels
        $order->reduce_order_stock();
        // Remove cart
        WC()->cart->empty_cart();
        // Return thankyou redirect
        return array(
            'result'    => 'success',
            'redirect'  => $this->get_return_url( $order )
        );
    }
    
    /**
	 * Log system processes.
	 * @since 1.0.0
	 */
	public function log( $message ) {
		if ( 'yes' === $this->get_option( 'testmode' ) || $this->enable_logging ) {
			if ( empty( $this->logger ) ) {
				$this->logger = new WC_Logger();
			}
			$this->logger->add( 'debicheck', $message );
		}
	}

}