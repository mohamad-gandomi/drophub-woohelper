<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Return_Policy {
    private $meta_key = '_drophub_return_policy';

    public function __construct() {
        // Add product meta box
        add_action('add_meta_boxes', array($this, 'add_return_policy_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_return_policy_meta'));
        
        // Display return policy on product page
        add_action('woocommerce_single_product_summary', array($this, 'display_return_policy'), 25);
    }

    public function add_return_policy_meta_box() {
        add_meta_box(
            'drophub_return_policy',
            __('Return Policy', 'drophub-woohelper'),
            array($this, 'render_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('drophub_return_policy_nonce', 'drophub_return_policy_nonce');
        $return_policy = get_post_meta($post->ID, $this->meta_key, true);
        ?>
        <textarea name="drophub_return_policy" id="drophub_return_policy" rows="5" style="width: 100%;"><?php echo esc_textarea($return_policy); ?></textarea>
        <?php
    }

    public function save_return_policy_meta($post_id) {
        if (!isset($_POST['drophub_return_policy_nonce']) || 
            !wp_verify_nonce($_POST['drophub_return_policy_nonce'], 'drophub_return_policy_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['drophub_return_policy'])) {
            update_post_meta(
                $post_id,
                $this->meta_key,
                sanitize_textarea_field($_POST['drophub_return_policy'])
            );
        }
    }

    public function display_return_policy() {
        global $product;
        
        if (!$product) {
            return;
        }

        $return_policy = get_post_meta($product->get_id(), $this->meta_key, true);
        
        if (!empty($return_policy)) {
            echo '<div class="drophub-return-policy">';
            echo '<h3>' . esc_html__('Return Policy', 'drophub-woohelper') . '</h3>';
            echo '<div class="return-policy-content">' . wp_kses_post(wpautop($return_policy)) . '</div>';
            echo '</div>';
        }
    }
} 