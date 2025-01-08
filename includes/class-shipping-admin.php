<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Shipping_Admin {
    private $meta_key = '_drophub_prepaid_shippings';

    public function __construct() {
        // Add read-only display in admin product page
        add_action('add_meta_boxes', array($this, 'add_shipping_info_meta_box'));
    }

    public function add_shipping_info_meta_box() {
        add_meta_box(
            'drophub_shipping_info',
            __('Shipping Information', 'drophub-woohelper'),
            array($this, 'render_shipping_info'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_shipping_info($post) {
        $shipping_data = get_post_meta($post->ID, $this->meta_key, true);
        $shipping_data = maybe_unserialize($shipping_data);

        if (empty($shipping_data) || !is_array($shipping_data)) {
            echo '<p>' . esc_html__('No shipping information available.', 'drophub-woohelper') . '</p>';
            return;
        }

        echo '<div class="shipping-info-display" style="padding: 10px;">';
        foreach ($shipping_data as $index => $data) {
            echo '<div class="shipping-option" style="margin-bottom: 15px; padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">';
            echo '<h4 style="margin: 0 0 10px;">' . sprintf(esc_html__('Shipping Option %d', 'drophub-woohelper'), $index + 1) . '</h4>';
            echo '<table class="widefat" style="border: none;">';
            echo '<tr><td style="width: 150px;"><strong>' . esc_html__('Method:', 'drophub-woohelper') . '</strong></td><td>' . esc_html($data['method']) . '</td></tr>';
            echo '<tr><td><strong>' . esc_html__('Zone Code:', 'drophub-woohelper') . '</strong></td><td>' . esc_html($data['zone_code']) . '</td></tr>';
            echo '<tr><td><strong>' . esc_html__('Base Rate:', 'drophub-woohelper') . '</strong></td><td>' . wc_price($data['rate']) . '</td></tr>';
            echo '<tr><td><strong>' . esc_html__('Extra Item Rate:', 'drophub-woohelper') . '</strong></td><td>' . wc_price($data['extra_item_rate']) . '</td></tr>';
            echo '<tr><td><strong>' . esc_html__('Delivery Time:', 'drophub-woohelper') . '</strong></td><td>' . 
                sprintf(
                    esc_html__('%d-%d days', 'drophub-woohelper'),
                    absint($data['range']['min']),
                    absint($data['range']['max'])
                ) . '</td></tr>';
            echo '<tr><td><strong>' . esc_html__('Class ID:', 'drophub-woohelper') . '</strong></td><td>' . esc_html($data['class']) . '</td></tr>';
            echo '</table>';
            echo '</div>';
        }
        echo '</div>';
    }
} 