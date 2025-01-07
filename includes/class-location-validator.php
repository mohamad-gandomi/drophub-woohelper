<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Location_Validator {
    private $meta_key = '_drophub_excluded_locations';

    // Add city code to localized name mapping
    private function get_localized_city_name($city_code) {
        $city_mapping = array(
            'THR' => 'تهران',
            'ALB' => 'البرز', 
            'ADL' => 'اردبیل',
            'ESF' => 'اصفهان',
            'ILM' => 'ایلام',
            'AZE' => 'آذربایجان شرقی',
            'AZW' => 'آذربایجان غربی',
            'BSH' => 'بوشهر',
            'CHB' => 'چهارمحال و بختیاری',
            'KHS' => 'خراسان جنوبی',
            'KHR' => 'خراسان رضوی',
            'KHN' => 'خراسان شمالی',
            'KHZ' => 'خوزستان',
            'ZJN' => 'زنجان',
            'SMN' => 'سمنان',
            'SBN' => 'سیستان و بلوچستان',
            'FRS' => 'فارس',
            'QZV' => 'قزوین',
            'QOM' => 'قم',
            'KRD' => 'کردستان',
            'KRM' => 'کرمان',
            'KMS' => 'کرمانشاه',
            'KBD' => 'کهگیلویه و بویراحمد',
            'GLS' => 'گلستان',
            'GIL' => 'گیلان',
            'LRS' => 'لرستان',
            'MZN' => 'مازندران',
            'MKZ' => 'مرکزی',
            'HRZ' => 'هرمزگان',
            'HMD' => 'همدان',
            'YZD' => 'یزد',
        );

        return isset($city_mapping[$city_code]) ? $city_mapping[$city_code] : $city_code;
    }

    public function __construct() {
        // Add product meta box for excluded locations
        add_action('add_meta_boxes', array($this, 'add_excluded_locations_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_excluded_locations_meta'));
        
        // Validate location on checkout
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_checkout_location'), 10, 2);
        
        // Add error styling
        add_filter('woocommerce_cart_item_class', array($this, 'add_error_class'), 10, 3);
    }

    public function add_excluded_locations_meta_box() {
        add_meta_box(
            'drophub_excluded_locations',
            __('Excluded Locations', 'drophub-woohelper'),
            array($this, 'render_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('drophub_excluded_locations_nonce', 'drophub_excluded_locations_nonce');
        $excluded_locations = get_post_meta($post->ID, $this->meta_key, true);
        $locations = maybe_unserialize($excluded_locations);
        $locations_text = is_array($locations) ? implode("\n", $locations) : '';
        ?>
        <p><?php esc_html_e('Enter excluded locations (one per line)', 'drophub-woohelper'); ?></p>
        <textarea name="drophub_excluded_locations" id="drophub_excluded_locations" rows="5" style="width: 100%;"><?php echo esc_textarea($locations_text); ?></textarea>
        <?php
    }

    public function save_excluded_locations_meta($post_id) {
        if (!isset($_POST['drophub_excluded_locations_nonce']) || 
            !wp_verify_nonce($_POST['drophub_excluded_locations_nonce'], 'drophub_excluded_locations_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['drophub_excluded_locations'])) {
            $locations = array_filter(array_map('trim', explode("\n", sanitize_textarea_field($_POST['drophub_excluded_locations']))));
            update_post_meta(
                $post_id,
                $this->meta_key,
                $locations
            );
        }
    }

    public function validate_checkout_location($data, $errors) {
        $shipping_country = $data['shipping_country'];
        $shipping_city = $data['shipping_state'];
        $location_key = $shipping_country . ':' . $shipping_city;
        
        $cart_items = WC()->cart->get_cart();

        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $excluded_locations = get_post_meta($product_id, $this->meta_key, true);
            
            if (!empty($excluded_locations)) {
                $locations = maybe_unserialize($excluded_locations);
                if (!is_array($locations)) {
                    $locations = array($locations);
                }
                
                if (in_array($location_key, $locations)) {
                    $product = wc_get_product($product_id);
                    $display_city = $this->get_localized_city_name($shipping_city);
                    $errors->add(
                        'location_error',
                        sprintf(
                            __('Sorry, %s cannot be shipped to %s', 'drophub-woohelper'),
                            $product->get_name(),
                            $display_city
                        )
                    );
                    WC()->session->set('location_error_' . $cart_item_key, true);
                }
            }
        }
    }

    public function add_error_class($class, $cart_item, $cart_item_key) {
        if (WC()->session->get('location_error_' . $cart_item_key)) {
            $class .= ' location-error';
        }
        return $class;
    }
} 