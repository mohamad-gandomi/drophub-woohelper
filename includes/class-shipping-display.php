<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Shipping_Display {
    private $meta_key = '_drophub_shippings';
    
    public function __construct() {
        add_filter('woocommerce_cart_item_name', array($this, 'add_shipping_info_to_cart_item'), 10, 3);
        // Temporarily disabled to prevent duplicate shipping info in checkout
        // add_filter('woocommerce_checkout_cart_item_quantity', array($this, 'add_shipping_info_to_checkout_item'), 10, 3);
    }

    public function add_shipping_info_to_cart_item($product_name, $cart_item, $cart_item_key) {
        $product_id = $cart_item['product_id'];
        $shipping_info = $this->get_shipping_info_for_product($product_id);
        
        if ($shipping_info) {
            $product_name .= '<div class="drophub-shipping-info">';
            $product_name .= sprintf(
                '<span class="shipping-method">%s: %s</span><br>',
                __('Shipping Method', 'drophub-woohelper'),
                esc_html($shipping_info['method'])
            );
            if (!empty($shipping_info['estimate_time'])) {
                $product_name .= sprintf(
                    '<span class="delivery-estimate">%s: %s</span>',
                    __('Estimated Delivery', 'drophub-woohelper'),
                    esc_html($shipping_info['estimate_time'])
                );
            }
            $product_name .= '</div>';
        }
        
        return $product_name;
    }

    public function add_shipping_info_to_checkout_item($quantity_html, $cart_item, $cart_item_key) {
        $product_id = $cart_item['product_id'];
        $shipping_info = $this->get_shipping_info_for_product($product_id);
        
        if ($shipping_info) {
            $quantity_html .= '<div class="drophub-shipping-info">';
            $quantity_html .= sprintf(
                '<span class="shipping-method">%s: %s</span><br>',
                __('Shipping Method', 'drophub-woohelper'),
                esc_html($shipping_info['method'])
            );
            if (!empty($shipping_info['estimate_time'])) {
                $quantity_html .= sprintf(
                    '<span class="delivery-estimate">%s: %s</span>',
                    __('Estimated Delivery', 'drophub-woohelper'),
                    esc_html($shipping_info['estimate_time'])
                );
            }
            $quantity_html .= '</div>';
        }
        
        return $quantity_html;
    }

    private function get_shipping_info_for_product($product_id) {
        $shipping_data = get_post_meta($product_id, $this->meta_key, true);
        if (empty($shipping_data)) {
            return false;
        }

        $shipping_data = maybe_unserialize($shipping_data);
        $customer_state = WC()->customer ? WC()->customer->get_shipping_state() : '';
        
        foreach ($shipping_data as $data) {
            // Check for state-specific shipping (IR:STATE format)
            $zone_parts = explode(':', $data['zone_code']);
            if (count($zone_parts) === 2 && $zone_parts[0] === 'IR') {
                if ($zone_parts[1] === $customer_state) {
                    return array(
                        'method' => $data['method'],
                        'estimate_time' => isset($data['range']) ? 
                            sprintf(
                                __('%d-%d days', 'drophub-woohelper'),
                                absint($data['range']['min']),
                                absint($data['range']['max'])
                            ) : ''
                    );
                }
            }
            // If zone is IR only, use as fallback for all states
            elseif ($data['zone_code'] === 'IR') {
                $fallback = array(
                    'method' => $data['method'],
                    'estimate_time' => isset($data['range']) ? 
                        sprintf(
                            __('%d-%d days', 'drophub-woohelper'),
                            absint($data['range']['min']),
                            absint($data['range']['max'])
                        ) : ''
                );
            }
        }
        
        // Return fallback if exists
        if (isset($fallback)) {
            return $fallback;
        }
        
        // Return first available shipping method if no specific match
        if (!empty($shipping_data[0])) {
            return array(
                'method' => $shipping_data[0]['method'],
                'estimate_time' => isset($shipping_data[0]['range']) ? 
                    sprintf(
                        __('%d-%d days', 'drophub-woohelper'),
                        absint($shipping_data[0]['range']['min']),
                        absint($shipping_data[0]['range']['max'])
                    ) : ''
            );
        }
        
        return false;
    }
} 