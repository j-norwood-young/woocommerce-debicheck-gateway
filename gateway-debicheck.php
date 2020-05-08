<?php
/**
 * Plugin Name: WooCommerce DebiCheck Gateway
 * Plugin URI: https://github.com/j-norwood-young/woocommerce-debicheck-gateway
 * Description: This plugin allows WooCommerce to handle payments through DebiCheck.
 * Author: DailyMaverick, Jason Norwood-Young
 * Author URI: https://dailymaverick.co.za
 * Version: 0.0.1
 * WC requires at least: 3.9
 * Tested up to: 3.9
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_debicheck_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
    }
    require_once(plugin_basename('includes/class-wc-gateway-debicheck.php' ) );
    add_filter( 'woocommerce_payment_gateways', 'woocommerce_debicheck_add_gateway' );
}
add_action( 'plugins_loaded', 'wc_debicheck_init', 11 );

function woocommerce_debicheck_plugin_links( $links ) {
	$settings_url = add_query_arg(
		array(
			'page' => 'wc-settings',
			'tab' => 'checkout',
			'section' => 'wc_gateway_debicheck',
		),
		admin_url( 'admin.php' )
	);

	$plugin_links = array(
		'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-gateway-debicheck' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_debicheck_plugin_links' );

/**
 * Add the gateway to WooCommerce
 * @since 1.0.0
 */
function woocommerce_debicheck_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_DebiCheck';
	return $methods;
}

// Shortcodes
function debicheck_form_shortcode($atts) {
	require(plugin_basename("templates/debicheck-form-shortcode.php"));
}

add_shortcode( 'debicheck-form', 'debicheck_form_shortcode' );