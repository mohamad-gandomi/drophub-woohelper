<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Shipping_Grouper {
    private $meta_key = '_drophub_shippings';
    
    public function __construct() {
        // Add template overrides
        add_filter('wc_get_template', array($this, 'override_cart_template'), 10, 5);
        add_filter('woocommerce_locate_template', array($this, 'override_checkout_template'), 10, 3);
        add_filter('woocommerce_locate_template', array($this, 'override_thankyou_template'), 10, 3);
    }

    public function override_cart_template($template, $template_name, $args, $template_path, $default_path) {
        if ($template_name === 'cart/cart.php') {
            $template = plugin_dir_path(dirname(__FILE__)) . 'templates/cart.php';
        }
        return $template;
    }

    public function override_checkout_template($template, $template_name, $template_path) {
        if ($template_name === 'checkout/review-order.php') {
            $custom_template = plugin_dir_path(dirname(__FILE__)) . 'templates/checkout-review-order.php';
            return file_exists($custom_template) ? $custom_template : $template;
        }
        return $template;
    }

    public function override_thankyou_template($template, $template_name, $template_path) {
        if ($template_name === 'checkout/thankyou.php') {
            $custom_template = plugin_dir_path(dirname(__FILE__)) . 'templates/checkout-thankyou.php';
            return file_exists($custom_template) ? $custom_template : $template;
        }
        return $template;
    }

    public function group_cart_items() {
        $cart = WC()->cart->get_cart();
        $grouped_items = array();
        $customer_state = WC()->customer ? WC()->customer->get_shipping_state() : '';
        
        foreach ($cart as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $shipping_data = get_post_meta($product_id, $this->meta_key, true);
            
            if (!empty($shipping_data)) {
                $shipping_data = maybe_unserialize($shipping_data);
                if (!empty($shipping_data)) {
                    $assigned = false;
                    
                    // First try to find state-specific shipping
                    foreach ($shipping_data as $data) {
                        $zone_parts = explode(':', $data['zone_code']);
                        if (count($zone_parts) === 2 && $zone_parts[0] === 'IR' && $zone_parts[1] === $customer_state) {
                            // Include prepaid status in the group key
                            $group_key = $data['class'] . '_' . $data['method'] . '_' . ($data['prepaid'] ? 'prepaid' : 'not_prepaid');
                            
                            if (!isset($grouped_items[$group_key])) {
                                $grouped_items[$group_key] = array(
                                    'shipping_class' => $data['class'],
                                    'shipping_method' => $data['method'],
                                    'zone' => $zone_parts[1],
                                    'rate' => $data['prepaid'] ? floatval($data['rate']) : 0,
                                    'extra_rate' => $data['prepaid'] ? floatval($data['extra_item_rate']) : 0,
                                    'prepaid' => $data['prepaid'],
                                    'delivery_time' => array(
                                        'min' => absint($data['range']['min']),
                                        'max' => absint($data['range']['max'])
                                    ),
                                    'items' => array()
                                );
                            }
                            
                            $grouped_items[$group_key]['items'][$cart_item_key] = $cart_item;
                            $assigned = true;
                            break;
                        }
                    }
                    
                    // If no state-specific shipping found, use general IR shipping
                    if (!$assigned) {
                        foreach ($shipping_data as $data) {
                            if ($data['zone_code'] === 'IR') {
                                // Include prepaid status in the group key
                                $group_key = $data['class'] . '_' . $data['method'] . '_' . ($data['prepaid'] ? 'prepaid' : 'not_prepaid');
                                
                                if (!isset($grouped_items[$group_key])) {
                                    $grouped_items[$group_key] = array(
                                        'shipping_class' => $data['class'],
                                        'shipping_method' => $data['method'],
                                        'zone' => 'IR',
                                        'rate' => $data['prepaid'] ? floatval($data['rate']) : 0,
                                        'extra_rate' => $data['prepaid'] ? floatval($data['extra_item_rate']) : 0,
                                        'prepaid' => $data['prepaid'],
                                        'delivery_time' => array(
                                            'min' => absint($data['range']['min']),
                                            'max' => absint($data['range']['max'])
                                        ),
                                        'items' => array()
                                    );
                                }
                                
                                $grouped_items[$group_key]['items'][$cart_item_key] = $cart_item;
                                break;
                            }
                        }
                    }
                }
            } else {
                // Items without shipping data go to "standard shipping" (always prepaid)
                $group_key = 'standard_shipping_prepaid';
                if (!isset($grouped_items[$group_key])) {
                    $grouped_items[$group_key] = array(
                        'shipping_class' => __('Standard Shipping', 'drophub-woohelper'),
                        'shipping_method' => __('Standard Shipping', 'drophub-woohelper'),
                        'prepaid' => true,
                        'items' => array()
                    );
                }
                $grouped_items[$group_key]['items'][$cart_item_key] = $cart_item;
            }
        }
        
        return $grouped_items;
    }

    public function group_order_items( $order_items ) {
        $grouped_items = [];
        $customer_state = WC()->customer ? WC()->customer->get_shipping_state() : '';

        foreach ( $order_items as $item_id => $item ) {
            $product = $item->get_product();
            $product_id = $product->get_id();
            $shipping_data = get_post_meta($product_id, $this->meta_key, true);

            if (!empty($shipping_data)) {
                $shipping_data = maybe_unserialize($shipping_data);
                if (!empty($shipping_data)) {
                    $assigned = false;

                    // First try to find state-specific shipping
                    foreach ($shipping_data as $data) {
                        $zone_parts = explode(':', $data['zone_code']);
                        if (count($zone_parts) === 2 && $zone_parts[0] === 'IR' && $zone_parts[1] === $customer_state) {
                            // Include prepaid status in the group key
                            $group_key = $data['class'] . '_' . $data['method'] . '_' . ($data['prepaid'] ? 'prepaid' : 'not_prepaid');

                            if (!isset($grouped_items[$group_key])) {
                                $grouped_items[$group_key] = array(
                                    'shipping_class' => $data['class'],
                                    'shipping_method' => $data['method'],
                                    'zone' => $zone_parts[1],
                                    'rate' => $data['prepaid'] ? floatval($data['rate']) : 0,
                                    'extra_rate' => $data['prepaid'] ? floatval($data['extra_item_rate']) : 0,
                                    'prepaid' => $data['prepaid'],
                                    'delivery_time' => array(
                                        'min' => absint($data['range']['min']),
                                        'max' => absint($data['range']['max'])
                                    ),
                                    'items' => array()
                                );
                            }

                            $grouped_items[$group_key]['items'][$item_id] = $item;
                            $assigned = true;
                            break;
                        }
                    }

                    // If no state-specific shipping found, use general IR shipping
                    if (!$assigned) {
                        foreach ($shipping_data as $data) {
                            if ($data['zone_code'] === 'IR') {
                                // Include prepaid status in the group key
                                $group_key = $data['class'] . '_' . $data['method'] . '_' . ($data['prepaid'] ? 'prepaid' : 'not_prepaid');

                                if (!isset($grouped_items[$group_key])) {
                                    $grouped_items[$group_key] = array(
                                        'shipping_class' => $data['class'],
                                        'shipping_method' => $data['method'],
                                        'zone' => 'IR',
                                        'rate' => $data['prepaid'] ? floatval($data['rate']) : 0,
                                        'extra_rate' => $data['prepaid'] ? floatval($data['extra_item_rate']) : 0,
                                        'prepaid' => $data['prepaid'],
                                        'delivery_time' => array(
                                            'min' => absint($data['range']['min']),
                                            'max' => absint($data['range']['max'])
                                        ),
                                        'items' => array()
                                    );
                                }

                                $grouped_items[$group_key]['items'][$item_id] = $item;
                                break;
                            }
                        }
                    }
                }
            } else {
                // Items without shipping data go to "standard shipping" (always prepaid)
                $group_key = 'standard_shipping_prepaid';
                if (!isset($grouped_items[$group_key])) {
                    $grouped_items[$group_key] = array(
                        'shipping_class' => __('Standard Shipping', 'drophub-woohelper'),
                        'shipping_method' => __('Standard Shipping', 'drophub-woohelper'),
                        'prepaid' => true,
                        'items' => array()
                    );
                }
                $grouped_items[$group_key]['items'][$item_id] = $item;
            }
        }

        return $grouped_items;
    }

    private function get_shipping_method_for_product( $product ) {
        // Implement logic to get the shipping method for the product
    }

    private function get_delivery_time_for_product( $product ) {
        // Implement logic to get the delivery time for the product
    }

    private function is_prepaid_shipping( $product ) {
        // Implement logic to determine if the shipping is prepaid for the product
    }

    private function get_shipping_rate_for_product( $product ) {
        // Implement logic to get the shipping rate for the product
    }

    private function get_extra_shipping_rate_for_product( $product ) {
        // Implement logic to get the extra shipping rate for the product
    }
}