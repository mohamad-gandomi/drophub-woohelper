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
        
        // Add shipping cost calculation only if not ignored
        if (get_option('drophub_ignore_shipping', 'no') === 'no') {
            add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_shipping_costs'));
            add_filter('woocommerce_package_rates', array($this, 'filter_shipping_methods'), 10, 2);
            add_filter('woocommerce_cart_totals_fee_html', array($this, 'add_dynamic_class_to_fee_td') , 10, 2);
            add_action('wp_head', array($this, 'add_styles'));
        }
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

        $has_standard_product = false;

        foreach ($grouped_items as $group) {
            // Check for standard products
            if ($group['shipping_class'] === __('Standard Shipping', 'drophub-woohelper')) {
                $has_standard_product = true;
                break;
            }
        }

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

        // Add the selected WooCommerce shipping method's rate
        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
        if (!empty($chosen_shipping_methods)) {
            $chosen_method = $chosen_shipping_methods[0]; // Assuming one shipping method is selected
            $packages = WC()->shipping->get_packages();
            
            foreach ($packages as $package) {
                if (isset($package['rates'][$chosen_method])) {
                    $rate = $package['rates'][$chosen_method]->cost;
                    $total_shipping += floatval($rate); // Add the WooCommerce shipping method cost
                    $cart->add_fee(__('Hidden Shipping Cost', 'drophub-woohelper'), -floatval($rate));
                    break;
                }
            }
        }

        // Add the total shipping fee
        if ($total_shipping > 0) {
            $cart->add_fee(__('Shipping Cost', 'drophub-woohelper'), $total_shipping);
        }
    }

    public function filter_shipping_methods($rates, $package) {
        $grouper = new Shipping_Grouper();
        $grouped_items = $grouper->group_cart_items();

        $has_standard_product = false;

        foreach ($grouped_items as $group) {
            // Check for standard products
            if ($group['shipping_class'] === __('Standard Shipping', 'drophub-woohelper')) {
                $has_standard_product = true;
                break;
            }
        }

        if (!$has_standard_product) {
            // Remove all existing shipping methods
            $rates = array();

            // Add a fallback shipping method to avoid checkout errors
            $fallback_rate = new \WC_Shipping_Rate(
                'fallback_shipping',
                __('Fallback Shipping', 'drophub-woohelper'),
                0,
                array(),
                'fallback'
            );
            $rates['fallback_shipping'] = $fallback_rate;
        }

        return $rates;
    }

    public function add_dynamic_class_to_fee_td($fee_html, $fee) {
        // Create a class name based on the fee name
        $class = sanitize_title($fee->name); // Convert the fee name into a safe CSS class
        return '<span class="' . esc_attr($class) . '">' . $fee_html . '</span>';
    }

    public function add_styles() {

         echo '<style type="text/css">
        .woocommerce-shipping-methods,
        .woocommerce-checkout .woocommerce-shipping-totals {
            display: none;
        }
        </style>';
    }


}
