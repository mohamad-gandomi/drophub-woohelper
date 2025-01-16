<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class External_Image_Handler {
    public function __construct() {
        add_filter('post_thumbnail_html', array($this, 'display_external_featured_image'), 10, 5);
        add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'display_external_gallery_images'), 10, 2);
        add_filter('woocommerce_get_product_thumbnail', array($this, 'display_external_shop_catalog_image'), 10, 2);
        add_action('woocommerce_init', array($this, 'replacing_template_loop_product_thumbnail'));
    }

    public function display_external_featured_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
        $external_images = maybe_unserialize(get_post_meta($post_id, '_drophub_images', true));
        if ($external_images) {
            foreach ($external_images as $image) {
                if ($image['is_cover']) {
                    $html = $this->generate_image_html($image, $post_id);
                    break;
                }
            }
        }
        return $html;
    }

    public function display_external_gallery_images($html, $attachment_id) {
        $post_id = get_the_ID(); // Get current product ID
        $external_images = maybe_unserialize(get_post_meta($post_id, '_drophub_images', true));

        if ($external_images) {
            $html = ''; // Reset HTML to append new images
            foreach ($external_images as $image) {
                $html .= $this->generate_image_html($image, $post_id);
            }
        }
        return $html;
    }

    public function display_external_shop_catalog_image($html, $post_id) {
        $external_images = maybe_unserialize(get_post_meta($post_id, '_drophub_images', true));
        if ($external_images) {
            foreach ($external_images as $image) {
                if ($image['is_cover']) {
                    $html = $this->generate_image_html($image, $post_id);
                    break;
                }
            }
        }
        return $html;
    }

    public function replacing_template_loop_product_thumbnail() {
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
        add_action('woocommerce_before_shop_loop_item_title', array($this, 'wc_template_loop_product_replaced_thumb'), 10);
    }

    public function wc_template_loop_product_replaced_thumb() {
        global $product;
        $post_id = $product->get_id();
        $external_images = maybe_unserialize(get_post_meta($post_id, '_drophub_images', true));
        if ($external_images) {
            foreach ($external_images as $image) {
                if ($image['is_cover']) {
                    echo '<img src="' . esc_url($image['src']) . '" alt="' . get_the_title($post_id) . '" />';
                    return;
                }
            }
        }
        echo woocommerce_get_product_thumbnail();
    }

    private function generate_image_html($image, $post_id) {
        $src = esc_url($image['src']);
        $alt = get_the_title($post_id);
        $srcset = isset($image['srcset']) ? esc_attr($image['srcset']) : '';
        $sizes = isset($image['sizes']) ? esc_attr($image['sizes']) : '';
        $large_image = isset($image['large_image']) ? esc_url($image['large_image']) : '';
        $large_image_width = isset($image['large_image_width']) ? esc_attr($image['large_image_width']) : '';
        $large_image_height = isset($image['large_image_height']) ? esc_attr($image['large_image_height']) : '';

        return '<div data-thumb="' . $src . '" data-thumb-alt="' . $alt . '" data-thumb-srcset="' . $src . '" data-thumb-sizes="' . $sizes . '" class="woocommerce-product-gallery__image flex-active-slide" style="width: 416.328px; margin-right: 0px; float: left; display: block; position: relative; overflow: hidden;">
                    <a href="' . $src . '">
                        <img width="416" height="416" src="' . $src . '" class="" alt="' . $alt . '" data-caption="" data-src="' . $src . '" data-large_image="' . $src . '" data-large_image_width="' . $large_image_width . '" data-large_image_height="' . $large_image_height . '" decoding="async" loading="lazy" srcset="' . $src . '" sizes="' . $sizes . '" draggable="false">
                    </a>
                    <img alt="" src="' . $src . '" class="zoomImg" style="position: absolute; top: -323.51px; left: -1.42566px; opacity: 0; width: 800px; height: 800px; border: none; max-width: none; max-height: none;" aria-hidden="true">
                </div>';
    }
}
