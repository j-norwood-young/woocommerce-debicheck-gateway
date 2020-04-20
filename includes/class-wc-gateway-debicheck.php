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
				'title'       => __( 'E-Mandate URL', 'woocommerce-gateway-debicheck' ),
				'type'        => 'text',
				'description' => __( 'URL to the E-Mandate platform.', 'woocommerce-gateway-debicheck' ),
				'default'     => __( 'https://www.electronicmandate.com/organisation', 'woocommerce-gateway-debicheck' ),
				'desc_tip'    => true,
			),
			'enable_logging' => array(
				'title'   => __( 'Enable Logging', 'woocommerce-gateway-debicheck' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable transaction logging for gateway.', 'woocommerce-gateway-debicheck' ),
				'default' => 'no',
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