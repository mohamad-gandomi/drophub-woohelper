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
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }

    public function group_cart_items() {
        $cart = WC()->cart->get_cart();
        $grouped_items = array();
        
        foreach ($cart as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $shipping_data = get_post_meta($product_id, $this->meta_key, true);
            
            if (!empty($shipping_data)) {
                $shipping_data = maybe_unserialize($shipping_data);
                if (!empty($shipping_data)) {
                    $first_shipping = reset($shipping_data);
                    $group_key = $first_shipping['class'];
                    
                    if (!isset($grouped_items[$group_key])) {
                        $grouped_items[$group_key] = array(
                            'shipping_class' => $group_key,
                            'items' => array()
                        );
                    }
                    
                    $grouped_items[$group_key]['items'][$cart_item_key] = $cart_item;
                }
            } else {
                // Items without shipping data go to "ungrouped"
                if (!isset($grouped_items['ungrouped'])) {
                    $grouped_items['ungrouped'] = array(
                        'shipping_class' => __('Standard Shipping', 'drophub-woohelper'),
                        'items' => array()
                    );
                }
                $grouped_items['ungrouped']['items'][$cart_item_key] = $cart_item;
            }
        }
        
        return $grouped_items;
    }
} 