<?php
namespace DropHub_WooHelper;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {
    public function __construct() {
        // Add tab
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        
        // Add settings
        add_action('woocommerce_settings_drophub', array($this, 'settings_tab'));
        add_action('woocommerce_settings_save_drophub', array($this, 'save_settings'));
    }

    public function add_settings_tab($settings_tabs) {
        $settings_tabs['drophub'] = __('DropHub', 'drophub-woohelper');
        return $settings_tabs;
    }

    public function settings_tab() {
        woocommerce_admin_fields($this->get_settings());
    }

    public function save_settings() {
        woocommerce_update_options($this->get_settings());
    }

    public function get_settings() {
        $settings = array(
            array(
                'title' => __('DropHub Settings', 'drophub-woohelper'),
                'type'  => 'title',
                'desc'  => __('Configure DropHub integration settings.', 'drophub-woohelper'),
                'id'    => 'drophub_section_title'
            ),
            array(
                'title'   => __('Ignore DropHub Shippings', 'drophub-woohelper'),
                'desc'    => __('Enable to ignore DropHub shipping methods', 'drophub-woohelper'),
                'id'      => 'drophub_ignore_shipping',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'drophub_section_end'
            )
        );

        return apply_filters('drophub_woohelper_settings', $settings);
    }
}
