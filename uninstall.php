<?php
/**
 * PBay Uninstall
 * Fired when the plugin is deleted from WordPress admin.
 * Removes all database tables and options.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop tables (order matters for FK-like dependencies)
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pbay_listing_meta");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pbay_orders");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pbay_listings");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pbay_listing_categories");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pbay_policy_wallets");

// Delete options
$options = [
    'pbay_network',
    'pbay_anvil_api_url',
    'pbay_anvil_api_key',
    'pbay_anvil_api_key_preprod',
    'pbay_anvil_api_key_mainnet',
    'pbay_merchant_address',
    'pbay_store_name',
    'pbay_pinata_enabled',
    'pbay_pinata_jwt',
    'pbay_pinata_api_key',
    'pbay_pinata_secret_key',
    'pbay_db_version',
];

foreach ($options as $option) {
    delete_option($option);
}

// Clean up transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pbay_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pbay_%'");
