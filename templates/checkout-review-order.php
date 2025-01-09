<?php
/**
 * Review order table with grouped products
 */

defined('ABSPATH') || exit;

$shipping_grouper = new DropHub_WooHelper\Shipping_Grouper();
$grouped_items = $shipping_grouper->group_cart_items();
?>
<div class="checkout-review-groups">
    <?php foreach ($grouped_items as $group_key => $group) : ?>
        <div class="checkout-shipping-group">
            <div class="shipping-group-header">
                <?php if (isset($group['shipping_method']) && $group['shipping_method'] !== __('Standard Shipping', 'drophub-woohelper')): ?>
                    <div class="shipping-details">
                        <div class="shipping-method-info">
                            <span class="method-name"><?php echo esc_html($group['shipping_method']); ?></span>
                        </div>
                        
                        <?php if (isset($group['delivery_time'])): ?>
                            <span class="delivery-time">
                                <?php echo sprintf(
                                    __('Delivery Time: %d-%d days', 'drophub-woohelper'),
                                    $group['delivery_time']['min'],
                                    $group['delivery_time']['max']
                                ); ?>
                            </span>
                        <?php endif; ?>

                        <span class="prepaid-status <?php echo $group['prepaid'] ? 'prepaid' : 'not-prepaid'; ?>">
                            <?php 
                            if (!$group['prepaid']) {
                                echo '<strong>' . esc_html__('Pay Upon Delivery', 'drophub-woohelper') . '</strong>';
                            }
                            ?>
                        </span>

                        <?php if ($group['prepaid'] && isset($group['rate'])): ?>
                            <div class="shipping-rates">
                                <?php
                                $total_quantity = array_sum(array_column($group['items'], 'quantity'));
                                if ($total_quantity > 1 && $group['extra_rate'] > 0):
                                    $total_cost = $group['rate'] + (($total_quantity - 1) * $group['extra_rate']);
                                ?>
                                    <span class="total-cost">
                                        <?php echo sprintf(__('Total Shipping: %s', 'drophub-woohelper'), wc_price($total_cost)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <table class="shop_table group-products">
                <?php foreach ($group['items'] as $cart_item_key => $cart_item) : 
                    $_product = $cart_item['data'];
                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) : ?>
                        <tr class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                            <td class="product-name">
                                <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key)) . '&nbsp;'; ?>
                                <?php echo apply_filters('woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', $cart_item['quantity']) . '</strong>', $cart_item, $cart_item_key); ?>
                                <?php echo wc_get_formatted_cart_item_data($cart_item); ?>
                            </td>
                            <td class="product-total">
                                <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                            </td>
                        </tr>
                    <?php endif;
                endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<table class="shop_table woocommerce-checkout-review-order-table">
    <tfoot>
        <tr class="cart-subtotal">
            <th><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
            <td><?php wc_cart_totals_subtotal_html(); ?></td>
        </tr>

        <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
            <tr class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                <th><?php wc_cart_totals_coupon_label($coupon); ?></th>
                <td><?php wc_cart_totals_coupon_html($coupon); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
            <?php do_action('woocommerce_review_order_before_shipping'); ?>
            <?php wc_cart_totals_shipping_html(); ?>
            <?php do_action('woocommerce_review_order_after_shipping'); ?>
        <?php endif; ?>

        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
            <tr class="fee">
                <th><?php echo esc_html($fee->name); ?></th>
                <td><?php wc_cart_totals_fee_html($fee); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
            <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
                    <tr class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
                        <th><?php echo esc_html($tax->label); ?></th>
                        <td><?php echo wp_kses_post($tax->formatted_amount); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="tax-total">
                    <th><?php echo esc_html(WC()->countries->tax_or_vat()); ?></th>
                    <td><?php wc_cart_totals_taxes_total_html(); ?></td>
                </tr>
            <?php endif; ?>
        <?php endif; ?>

        <?php do_action('woocommerce_review_order_before_order_total'); ?>

        <tr class="order-total">
            <th><?php esc_html_e('Total', 'woocommerce'); ?></th>
            <td><?php wc_cart_totals_order_total_html(); ?></td>
        </tr>

        <?php do_action('woocommerce_review_order_after_order_total'); ?>
    </tfoot>
</table> 