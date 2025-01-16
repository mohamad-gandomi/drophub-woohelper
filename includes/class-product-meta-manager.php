<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Product_Meta_Manager {
    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_meta_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_meta_fields'));
    }

    public function add_custom_meta_fields() {
        global $woocommerce, $post;
        echo '<div class="options_group">';
        $external_images = get_post_meta($post->ID, '_drophub_images', true);
        $external_images = maybe_unserialize($external_images);
        if (is_array($external_images)) {
            $external_images = maybe_serialize($external_images);
        }
        woocommerce_wp_textarea_input(array(
            'id' => '_drophub_images',
            'label' => __('External Images Data', 'drophub-woohelper'),
            'placeholder' => 'Serialized array of external images data',
            'desc_tip' => 'true',
            'description' => __('Enter the serialized array of external images data.', 'drophub-woohelper'),
            'value' => $external_images
        ));
        echo '</div>';
    }

    public function save_custom_meta_fields($post_id) {
        $external_images = maybe_unserialize($_POST['_drophub_images']);
        if (!empty($external_images)) {
            update_post_meta($post_id, '_drophub_images', maybe_serialize($external_images));
        }
    }
}
