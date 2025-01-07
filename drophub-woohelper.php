<?php
/**
 * Plugin Name: DropHub WooHelper
 * Plugin URI: https://your-website.com/drophub-woohelper
 * Description: Enhances WooCommerce with return policy display, location validation, and delivery time calculations.
 * Version: 1.0.0
 * Author: Mohamad Gandomi
 * Author URI: https://your-website.com
 * Text Domain: drophub-woohelper
 * Domain Path: /languages/
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package DropHub_WooHelper
 */

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('remote_logging', __FILE__, true);
    }
});

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('DROPHUB_WOOHELPER_VERSION', '1.0.0');
define('DROPHUB_WOOHELPER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DROPHUB_WOOHELPER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Ensure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WooCommerce is not active. Please activate WooCommerce to use the DropHub WooHelper plugin.', 'drophub-woohelper'); ?></p>
        </div>
        <?php
    });
    return;
}

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'DropHub_WooHelper\\';
    $base_dir = DROPHUB_WOOHELPER_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load plugin text domain
function drophub_woohelper_load_textdomain() {
    load_plugin_textdomain(
        'drophub-woohelper',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('init', 'drophub_woohelper_load_textdomain');

// Initialize the plugin
function drophub_woohelper_init() {
    $plugin = new DropHub_WooHelper\DropHub_WooHelper();
    $plugin->init();
}
add_action('plugins_loaded', 'drophub_woohelper_init'); 