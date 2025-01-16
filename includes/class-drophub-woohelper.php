<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class DropHub_WooHelper {
    private $return_policy;
    private $location_validator;
    private $delivery_calculator;
    private $admin;
    private $settings;
    private $product_meta_manager;
    private $external_image_handler;

    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->return_policy = new Return_Policy();
        $this->location_validator = new Location_Validator();
        $this->delivery_calculator = new Shipping_Calculator();
        $this->settings = new Settings();
        $this->product_meta_manager = new Product_Meta_Manager();
        $this->external_image_handler = new External_Image_Handler();
        // $this->admin = new Admin();

        // Register hooks
        $this->register_hooks();
    }

    private function load_dependencies() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    private function register_hooks() {
        // Add activation hook
        register_activation_hook(DROPHUB_WOOHELPER_PLUGIN_DIR . 'drophub-woohelper.php', array($this, 'activate'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'drophub-woohelper',
            DROPHUB_WOOHELPER_PLUGIN_URL . 'assets/css/drophub-woohelper.css',
            array(),
            DROPHUB_WOOHELPER_VERSION
        );

        wp_enqueue_script(
            'drophub-woohelper',
            DROPHUB_WOOHELPER_PLUGIN_URL . 'assets/js/drophub-woohelper.js',
            array('jquery'),
            DROPHUB_WOOHELPER_VERSION,
            true
        );

        wp_localize_script('drophub-woohelper', 'drophubWooHelper', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('drophub-woohelper-nonce')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook || 'product' !== get_post_type()) {
            return;
        }

        wp_enqueue_style(
            'drophub-woohelper-admin',
            DROPHUB_WOOHELPER_PLUGIN_URL . 'assets/css/drophub-woohelper-admin.css',
            array(),
            DROPHUB_WOOHELPER_VERSION
        );
    }

    public function activate() {
        // Activation tasks if needed
        flush_rewrite_rules();
    }
}
