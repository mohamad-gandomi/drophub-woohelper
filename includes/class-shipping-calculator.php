<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Shipping_Calculator {
    private $meta_key = '_drophub_shipping_time';

    public function __construct() {
        // Add product meta box for shipping time
        add_action('add_meta_boxes', array($this, 'add_shipping_time_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_shipping_time_meta'));
        
        // Display shipping time on cart and checkout
        add_action('woocommerce_after_cart_item_name', array($this, 'display_cart_shipping_time'), 10, 2);
        add_action('woocommerce_checkout_cart_item_quantity', array($this, 'display_checkout_shipping_time'), 10, 3);
    }

    public function add_shipping_time_meta_box() {
        add_meta_box(
            'drophub_shipping_time',
            __('Shipping Time Range', 'drophub-woohelper'),
            array($this, 'render_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('drophub_shipping_time_nonce', 'drophub_shipping_time_nonce');
        $shipping_time = get_post_meta($post->ID, $this->meta_key, true);
        
        // Set default values or get existing values
        $min_days = '';
        $max_days = '';
        $class_id = '';
        
        if (!empty($shipping_time) && is_array($shipping_time)) {
            if (isset($shipping_time[0]['range'])) {
                $min_days = $shipping_time[0]['range']['min'];
                $max_days = $shipping_time[0]['range']['max'];
                $class_id = isset($shipping_time[0]['class']) ? $shipping_time[0]['class'] : '';
            }
        }
        ?>
        <p><?php esc_html_e('Enter estimated shipping time range in days', 'drophub-woohelper'); ?></p>
        <div style="margin-bottom: 10px;">
            <label for="drophub_shipping_time_min" style="display: inline-block; width: 100px;">
                <?php esc_html_e('Minimum Days:', 'drophub-woohelper'); ?>
            </label>
            <input type="number" 
                   name="drophub_shipping_time_min" 
                   id="drophub_shipping_time_min" 
                   value="<?php echo esc_attr($min_days); ?>" 
                   min="1" 
                   step="1" 
                   style="width: 80px;" />
        </div>
        <div style="margin-bottom: 10px;">
            <label for="drophub_shipping_time_max" style="display: inline-block; width: 100px;">
                <?php esc_html_e('Maximum Days:', 'drophub-woohelper'); ?>
            </label>
            <input type="number" 
                   name="drophub_shipping_time_max" 
                   id="drophub_shipping_time_max" 
                   value="<?php echo esc_attr($max_days); ?>" 
                   min="1" 
                   step="1" 
                   style="width: 80px;" />
        </div>
        <div>
            <label for="drophub_shipping_time_class" style="display: inline-block; width: 100px;">
                <?php esc_html_e('Class ID:', 'drophub-woohelper'); ?>
            </label>
            <input type="text" 
                   name="drophub_shipping_time_class" 
                   id="drophub_shipping_time_class" 
                   value="<?php echo esc_attr($class_id); ?>" 
                   style="width: 300px;" 
                   placeholder="e.g., d3bd4a80-886b-4b2d-abaa-45d092398a6c" />
        </div>
        <?php
    }

    public function save_shipping_time_meta($post_id) {
        if (!isset($_POST['drophub_shipping_time_nonce']) || 
            !wp_verify_nonce($_POST['drophub_shipping_time_nonce'], 'drophub_shipping_time_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['drophub_shipping_time_min']) && isset($_POST['drophub_shipping_time_max'])) {
            $min_days = absint($_POST['drophub_shipping_time_min']);
            $max_days = absint($_POST['drophub_shipping_time_max']);
            $class_id = isset($_POST['drophub_shipping_time_class']) ? sanitize_text_field($_POST['drophub_shipping_time_class']) : '';
            
            // Ensure max is not less than min
            if ($max_days < $min_days) {
                $max_days = $min_days;
            }
            
            $shipping_time = array(
                0 => array(
                    'range' => array(
                        'min' => $min_days,
                        'max' => $max_days
                    ),
                    'class' => $class_id
                )
            );
            
            update_post_meta(
                $post_id,
                $this->meta_key,
                $shipping_time
            );
        }
    }

    public function display_cart_shipping_time($cart_item, $cart_item_key) {
        $product_id = $cart_item['product_id'];
        $shipping_time = get_post_meta($product_id, $this->meta_key, true);
        
        if (!empty($shipping_time)) {
            echo $this->get_shipping_time_html($shipping_time);
        }
    }

    public function display_checkout_shipping_time($quantity_html, $cart_item, $cart_item_key) {
        $product_id = $cart_item['product_id'];
        $shipping_time = get_post_meta($product_id, $this->meta_key, true);
        
        if (!empty($shipping_time)) {
            return $quantity_html . $this->get_shipping_time_html($shipping_time);
        }
        
        return $quantity_html;
    }

    private function get_shipping_time_html($shipping_time) {
        if (!is_array($shipping_time) || !isset($shipping_time[0]['range'])) {
            return '';
        }

        $min_days = $shipping_time[0]['range']['min'];
        $max_days = $shipping_time[0]['range']['max'];

        if ($min_days === $max_days) {
            return wp_kses_post(sprintf(
                '<div class="shipping-time">%s</div>',
                sprintf(
                    /* translators: %d: number of days */
                    esc_html__('Estimated shipping: %d days', 'drophub-woohelper'),
                    absint($min_days)
                )
            ));
        }

        return wp_kses_post(sprintf(
            '<div class="shipping-time">%s</div>',
            sprintf(
                /* translators: %1$d: minimum days, %2$d: maximum days */
                esc_html__('Estimated shipping: %1$d-%2$d days', 'drophub-woohelper'),
                absint($min_days),
                absint($max_days)
            )
        ));
    }
} 