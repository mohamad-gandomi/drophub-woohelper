<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Shipping_Calculator {
    private $meta_key = '_drophub_prepaid_shippings';
    private $display;
    private $grouper;
    private $admin;

    public function __construct() {
        // Initialize components
        $this->display = new Shipping_Display();
        $this->grouper = new Shipping_Grouper();
        $this->admin = new Shipping_Admin();
        
        // Add shipping cost calculation
        add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_shipping_costs'));
    }

    public function calculate_shipping_costs($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $shipping_groups = array();

        // Group cart items by shipping class and zone
        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $shipping_data = get_post_meta($product_id, $this->meta_key, true);
            
            if (!empty($shipping_data)) {
                $shipping_data = maybe_unserialize($shipping_data);
                foreach ($shipping_data as $data) {
                    $group_key = $data['class'];
                    
                    if (!isset($shipping_groups[$group_key])) {
                        $shipping_groups[$group_key] = array(
                            'items' => array(),
                            'data' => $data
                        );
                    }
                    
                    $shipping_groups[$group_key]['items'][] = $cart_item;
                }
            }
        }

        // Calculate shipping cost for each group
        foreach ($shipping_groups as $group_key => $group) {
            $items_count = count($group['items']);
            if ($items_count > 0) {
                $base_rate = floatval($group['data']['rate']);
                $extra_rate = floatval($group['data']['extra_item_rate']);
                
                // Calculate total shipping cost for this group
                $total_cost = $base_rate;
                if ($items_count > 1) {
                    $total_cost += ($items_count - 1) * $extra_rate;
                }

                // Add fee for this shipping group
                $cart->add_fee(
                    sprintf(
                        __('Shipping via %s (%s)', 'drophub-woohelper'),
                        $group['data']['method'],
                        $group['data']['zone_code']
                    ),
                    $total_cost
                );
            }
        }
    }
} 