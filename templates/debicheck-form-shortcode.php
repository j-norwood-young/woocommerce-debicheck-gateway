<?php
/*
Template Name: Debicheck Checkout Shortcode
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access
if(!empty($_GET['orderId']) && is_user_logged_in()) {
    /** @var WC_Order $order */
    $order = wc_get_order($_GET['orderId']);
    if($order->get_user_id() == get_current_user_id() && $order->get_status() == 'pending'){
        $currentUserSubscriptionAmount = $order->get_total();
        $relatedSubscriptionIds = wcs_get_subscriptions_for_order($order);
        /**
         * @var int $relatedSubscriptionId
         * @var WC_Subscription $relatedSubscription
         */
        foreach ($relatedSubscriptionIds as $relatedSubscriptionId => $relatedSubscription) {
            if ($relatedSubscription->get_last_order(['ids'], ['parent'])->id === $order->get_id()) {
                $currentUserSubscriptionBillingPeriod = ($relatedSubscription->get_billing_period() == 'year') ? 'year' : 'month';
                break;
            }
        }
    }
}
?>
<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>
<form method="post"
    action-xhr="https://example.com/subscribe"    target="_top">
    <fieldset>
      <label>
        <span>Name:</span>
        <input type="text"
          name="name"
          required>
      </label>
      <br>
      <label>
        <span>Email:</span>
        <input type="email"
          name="email"
          required>
      </label>
      <br>
      <label>
        <span>Telephone Number:</span>
        <input type="tel"
          name="tel"
          required>
      </label>
      <br>
      <label>
        <span>Amount:</span>
        <input type="number"
          name="amount"
          required>
      </label>
      <br>
      <input type="submit"
        value="Subscribe">
    </fieldset>
    <div submit-success>
      <template type="amp-mustache">
        Subscription successful!
      </template>
    </div>
    <div submit-error>
      <template type="amp-mustache">
        Subscription failed!
      </template>
    </div>
  </form>