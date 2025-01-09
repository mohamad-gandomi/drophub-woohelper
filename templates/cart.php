<?php
/**
 * Cart Page with Shipping Groups
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    <?php do_action('woocommerce_before_cart_table'); ?>

    <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
        <thead>
            <tr>
                <th class="product-remove"><span class="screen-reader-text"><?php esc_html_e('Remove item', 'woocommerce'); ?></span></th>
                <th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e('Thumbnail', 'woocommerce'); ?></span></th>
                <th class="product-name"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                <th class="product-price"><?php esc_html_e('Price', 'woocommerce'); ?></th>
                <th class="product-quantity"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                <th class="product-subtotal"><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grouper = new DropHub_WooHelper\Shipping_Grouper();
            $grouped_items = $grouper->group_cart_items();

            foreach ($grouped_items as $group) :
                if (empty($group['items'])) continue;
            ?>
                <tr class="shipping-group-row">
                    <td colspan="6" class="p-0">
                        <div class="shipping-group">
                            <div class="shipping-group-header">
                                <strong><?php echo esc_html($group['shipping_class']); ?></strong>
                                <?php
                                if ($group['shipping_class'] !== __('Standard Shipping', 'drophub-woohelper')) {
                                    $shipping_methods = array();
                                    $customer_state = WC()->customer ? WC()->customer->get_shipping_state() : '';
                                    $processed_items = array(); // Track which items have been processed

                                    // First pass: Try to assign items to state-specific shipping methods
                                    foreach ($group['items'] as $cart_item_key => $cart_item) {
                                        $product_id = $cart_item['product_id'];
                                        $shipping_data = get_post_meta($product_id, '_drophub_shippings', true);
                                        $shipping_data = maybe_unserialize($shipping_data);

                                        if (!empty($shipping_data)) {
                                            $found_state_shipping = false;
                                            foreach ($shipping_data as $data) {
                                                if ($data['class'] === $group['shipping_class']) {
                                                    $zone_parts = explode(':', $data['zone_code']);
                                                    
                                                    // Check for state-specific shipping
                                                    if (count($zone_parts) === 2 && $zone_parts[0] === 'IR' && $zone_parts[1] === $customer_state) {
                                                        $method_key = $data['method'];
                                                        if (!isset($shipping_methods[$method_key])) {
                                                            $shipping_methods[$method_key] = array(
                                                                'method' => $data['method'],
                                                                'zone' => $zone_parts[1],
                                                                'rate' => floatval($data['rate']),
                                                                'extra_rate' => floatval($data['extra_item_rate']),
                                                                'prepaid' => $data['prepaid'],
                                                                'delivery_time' => sprintf(
                                                                    __('%d-%d days', 'drophub-woohelper'),
                                                                    absint($data['range']['min']),
                                                                    absint($data['range']['max'])
                                                                ),
                                                                'total_quantity' => 0,
                                                                'items' => array()
                                                            );
                                                        }
                                                        $shipping_methods[$method_key]['total_quantity'] += $cart_item['quantity'];
                                                        $shipping_methods[$method_key]['items'][] = $cart_item;
                                                        $processed_items[$cart_item_key] = true;
                                                        $found_state_shipping = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Second pass: Process remaining items with IR shipping
                                    foreach ($group['items'] as $cart_item_key => $cart_item) {
                                        if (!isset($processed_items[$cart_item_key])) {
                                            $product_id = $cart_item['product_id'];
                                            $shipping_data = get_post_meta($product_id, '_drophub_shippings', true);
                                            $shipping_data = maybe_unserialize($shipping_data);

                                            if (!empty($shipping_data)) {
                                                foreach ($shipping_data as $data) {
                                                    if ($data['class'] === $group['shipping_class'] && $data['zone_code'] === 'IR') {
                                                        $method_key = $data['method'];
                                                        if (!isset($shipping_methods[$method_key])) {
                                                            $shipping_methods[$method_key] = array(
                                                                'method' => $data['method'],
                                                                'zone' => 'IR',
                                                                'rate' => floatval($data['rate']),
                                                                'extra_rate' => floatval($data['extra_item_rate']),
                                                                'prepaid' => $data['prepaid'],
                                                                'delivery_time' => sprintf(
                                                                    __('%d-%d days', 'drophub-woohelper'),
                                                                    absint($data['range']['min']),
                                                                    absint($data['range']['max'])
                                                                ),
                                                                'total_quantity' => 0,
                                                                'items' => array()
                                                            );
                                                        }
                                                        $shipping_methods[$method_key]['total_quantity'] += $cart_item['quantity'];
                                                        $shipping_methods[$method_key]['items'][] = $cart_item;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (!empty($shipping_methods)) {
                                        echo '<div class="shipping-cost-info">';
                                        $total_shipping = 0;

                                        foreach ($shipping_methods as $method) {
                                            $base_rate = $method['rate'];
                                            $extra_rate = $method['extra_rate'];
                                            $total_quantity = $method['total_quantity'];

                                            // Calculate shipping cost for this method
                                            $method_cost = $base_rate;
                                            if ($total_quantity > 1 && $extra_rate > 0) {
                                                $method_cost += ($total_quantity - 1) * $extra_rate;
                                            }
                                            $total_shipping += $method_cost;

                                            echo '<div class="shipping-option">';
                                            echo '<span class="shipping-method">' . esc_html($method['method']) . '</span>';
                                            if ($method['zone'] !== 'IR') {
                                                echo '<span class="shipping-zone">' . sprintf(__('Zone: %s', 'drophub-woohelper'), esc_html($method['zone'])) . '</span>';
                                            }
                                            echo '<span class="delivery-time">' . sprintf(__('Delivery Time: %s', 'drophub-woohelper'), esc_html($method['delivery_time'])) . '</span>';
                                            echo '<span class="prepaid-status ' . ($method['prepaid'] ? 'prepaid' : 'not-prepaid') . '">' . 
                                                esc_html($method['prepaid'] ? __('Prepaid', 'drophub-woohelper') : __('Not Prepaid', 'drophub-woohelper')) . 
                                            '</span>';
                                            
                                            // List products using this shipping method
                                            echo '<div class="method-products">';
                                            foreach ($method['items'] as $item) {
                                                $_product = $item['data'];
                                                echo '<div class="product-item">';
                                                echo '<span class="product-name">' . esc_html($_product->get_name()) . '</span>';
                                                echo '<span class="product-quantity">× ' . esc_html($item['quantity']) . '</span>';
                                                echo '</div>';
                                            }
                                            echo '</div>';
                                            
                                            echo '<span class="base-rate">' . sprintf(__('Base Rate: %s', 'drophub-woohelper'), wc_price($base_rate)) . '</span>';
                                            if ($total_quantity > 1 && $extra_rate > 0) {
                                                echo '<span class="extra-items">' . sprintf(__('Extra Items (%d): %s', 'drophub-woohelper'), $total_quantity - 1, wc_price(($total_quantity - 1) * $extra_rate)) . '</span>';
                                            }
                                            echo '<span class="option-total">' . sprintf(__('Method Total: %s', 'drophub-woohelper'), wc_price($method_cost)) . '</span>';
                                            echo '</div>';
                                        }

                                        echo '<span class="total-shipping">' . sprintf(__('Total Group Shipping: %s', 'drophub-woohelper'), wc_price($total_shipping)) . '</span>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                            <table class="shop_table shop_table_responsive">
                                <tbody>
                                <?php
                                foreach ($group['items'] as $cart_item_key => $cart_item) :
                                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) :
                                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                                ?>
                                        <tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                                            <td class="product-remove">
                                                <?php
                                                echo apply_filters(
                                                    'woocommerce_cart_item_remove_link',
                                                    sprintf(
                                                        '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                                                        esc_url(wc_get_cart_remove_url($cart_item_key)),
                                                        esc_html__('Remove this item', 'woocommerce'),
                                                        esc_attr($product_id),
                                                        esc_attr($_product->get_sku())
                                                    ),
                                                    $cart_item_key
                                                );
                                                ?>
                                            </td>

                                            <td class="product-thumbnail">
                                                <?php
                                                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

                                                if (!$product_permalink) {
                                                    echo $thumbnail;
                                                } else {
                                                    printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                                                }
                                                ?>
                                            </td>

                                            <td class="product-name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                                <?php
                                                if (!$product_permalink) {
                                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
                                                } else {
                                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                                                }

                                                do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

                                                // Meta data
                                                echo wc_get_formatted_cart_item_data($cart_item);

                                                // Backorder notification
                                                if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                                }
                                                ?>
                                            </td>

                                            <td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                                                <?php
                                                echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
                                                ?>
                                            </td>

                                            <td class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                                <?php
                                                if ($_product->is_sold_individually()) {
                                                    $min_quantity = 1;
                                                    $max_quantity = 1;
                                                } else {
                                                    $min_quantity = 0;
                                                    $max_quantity = $_product->get_max_purchase_quantity();
                                                }

                                                $product_quantity = woocommerce_quantity_input(
                                                    array(
                                                        'input_name'   => "cart[{$cart_item_key}][qty]",
                                                        'input_value'  => $cart_item['quantity'],
                                                        'max_value'    => $max_quantity,
                                                        'min_value'    => $min_quantity,
                                                        'product_name' => $_product->get_name(),
                                                    ),
                                                    $_product,
                                                    false
                                                );

                                                echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
                                                ?>
                                            </td>

                                            <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                                                <?php
                                                echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
                                                ?>
                                            </td>
                                        </tr>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php do_action('woocommerce_cart_contents'); ?>

            <tr>
                <td colspan="6" class="actions">
                    <?php if (wc_coupons_enabled()) { ?>
                        <div class="coupon">
                            <label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Coupon:', 'woocommerce'); ?></label>
                            <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" />
                            <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
                            <?php do_action('woocommerce_cart_coupon'); ?>
                        </div>
                    <?php } ?>

                    <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>

                    <?php do_action('woocommerce_cart_actions'); ?>

                    <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                </td>
            </tr>

            <?php do_action('woocommerce_after_cart_contents'); ?>
        </tbody>
    </table>
    <?php do_action('woocommerce_after_cart_table'); ?>
</form>

<?php do_action('woocommerce_before_cart_collaterals'); ?>

<div class="cart-collaterals">
    <?php
    /**
     * Cart collaterals hook.
     *
     * @hooked woocommerce_cross_sell_display
     * @hooked woocommerce_cart_totals - 10
     */
    do_action('woocommerce_cart_collaterals');
    ?>
</div>

<?php do_action('woocommerce_after_cart'); ?> 