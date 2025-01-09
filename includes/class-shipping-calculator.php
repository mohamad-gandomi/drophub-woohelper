<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Shipping_Calculator {
    private $meta_key = '_drophub_shippings';
    //private $display;
    private $grouper;
    private $admin;

    public function __construct() {
        // Initialize components
        //$this->display = new Shipping_Display();
        $this->grouper = new Shipping_Grouper();
        $this->admin = new Shipping_Admin();
        
        // Add shipping cost calculation
        add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_shipping_costs'));
    }

    public function calculate_shipping_costs($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Remove any existing shipping fees
        $fees = $cart->get_fees();
        foreach ($fees as $fee) {
            if (strpos($fee->name, 'Shipping Cost') !== false) {
                $cart->remove_fee($fee->name);
            }
        }

        $total_shipping = 0;
        $grouper = new Shipping_Grouper();
        $grouped_items = $grouper->group_cart_items();
        $customer_state = WC()->customer ? WC()->customer->get_shipping_state() : '';

        foreach ($grouped_items as $group) {
            // Skip empty groups, standard shipping, or non-prepaid groups
            if (empty($group['items']) || 
                $group['shipping_class'] === __('Standard Shipping', 'drophub-woohelper') ||
                (isset($group['prepaid']) && !$group['prepaid'])) {
                continue;
            }

            $shipping_methods = array();
            $processed_items = array();

            // Calculate shipping for state-specific items
            foreach ($group['items'] as $cart_item_key => $cart_item) {
                $product_id = $cart_item['product_id'];
                $shipping_data = get_post_meta($product_id, $this->meta_key, true);
                $shipping_data = maybe_unserialize($shipping_data);

                if (!empty($shipping_data)) {
                    foreach ($shipping_data as $data) {
                        // Skip non-prepaid shipping methods
                        if (!$data['prepaid']) {
                            continue;
                        }

                        if ($data['class'] === $group['shipping_class']) {
                            $zone_parts = explode(':', $data['zone_code']);
                            
                            if (count($zone_parts) === 2 && $zone_parts[0] === 'IR' && $zone_parts[1] === $customer_state) {
                                $method_key = $data['method'];
                                if (!isset($shipping_methods[$method_key])) {
                                    $shipping_methods[$method_key] = array(
                                        'rate' => floatval($data['rate']),
                                        'extra_rate' => floatval($data['extra_item_rate']),
                                        'total_quantity' => 0
                                    );
                                }
                                $shipping_methods[$method_key]['total_quantity'] += $cart_item['quantity'];
                                $processed_items[$cart_item_key] = true;
                                break;
                            }
                        }
                    }
                }
            }

            // Calculate shipping for remaining items with IR shipping
            foreach ($group['items'] as $cart_item_key => $cart_item) {
                if (!isset($processed_items[$cart_item_key])) {
                    $product_id = $cart_item['product_id'];
                    $shipping_data = get_post_meta($product_id, $this->meta_key, true);
                    $shipping_data = maybe_unserialize($shipping_data);

                    if (!empty($shipping_data)) {
                        foreach ($shipping_data as $data) {
                            // Skip non-prepaid shipping methods
                            if (!$data['prepaid']) {
                                continue;
                            }

                            if ($data['class'] === $group['shipping_class'] && $data['zone_code'] === 'IR') {
                                $method_key = $data['method'];
                                if (!isset($shipping_methods[$method_key])) {
                                    $shipping_methods[$method_key] = array(
                                        'rate' => floatval($data['rate']),
                                        'extra_rate' => floatval($data['extra_item_rate']),
                                        'total_quantity' => 0
                                    );
                                }
                                $shipping_methods[$method_key]['total_quantity'] += $cart_item['quantity'];
                                break;
                            }
                        }
                    }
                }
            }

            // Calculate total shipping for this group
            foreach ($shipping_methods as $method) {
                $method_cost = $method['rate'];
                if ($method['total_quantity'] > 1 && $method['extra_rate'] > 0) {
                    $method_cost += ($method['total_quantity'] - 1) * $method['extra_rate'];
                }
                $total_shipping += $method_cost;
            }
        }

        // Add the total shipping fee
        if ($total_shipping > 0) {
            $cart->add_fee(__('Shipping Cost', 'drophub-woohelper'), $total_shipping);
        }
    }
} 