<?php
/*
Plugin Name: GMP WooCommerce Brand Manager
Description: A plugin to copy or move products between brands.
Version: 1.0.0
Author: GMP
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'includes/class-gmp-woocommerce-brand-manager.php';

add_action('plugins_loaded', function () {
    WooCommerce_Brand_Manager::get_instance();
});
?>