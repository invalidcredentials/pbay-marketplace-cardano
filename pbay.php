<?php
/*
Plugin Name: PBay - Cardano Marketplace
Description: Web2/Web3 hybrid marketplace powered by Cardano. NFT as Inventory - sellers mint CIP-25 NFTs, buyers pay in ADA via CIP-30 wallets.
Version: 0.1.2-alpha
Author: PB
Text Domain: pbay
*/

if (!defined('ABSPATH')) {
    exit;
}

define('PBAY_VERSION', '0.1.2-alpha');
define('PBAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PBAY_PLUGIN_URL', plugin_dir_url(__FILE__));

// --- Autoload helpers (no namespace, verbatim crypto stack) ---
require_once PBAY_PLUGIN_DIR . 'includes/helpers/Ed25519Pure.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/Ed25519Compat.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/CardanoWalletPHP.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/CardanoTransactionSignerPHP.php';

// --- Autoload namespaced helpers ---
require_once PBAY_PLUGIN_DIR . 'includes/helpers/EncryptionHelper.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/AnvilAPI.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/PinataAPI.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/MetadataHelper.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/PriceHelper.php';
require_once PBAY_PLUGIN_DIR . 'includes/helpers/BlockfrostAPI.php';

// --- Models ---
require_once PBAY_PLUGIN_DIR . 'includes/models/PolicyWalletModel.php';
require_once PBAY_PLUGIN_DIR . 'includes/models/ListingCategoryModel.php';
require_once PBAY_PLUGIN_DIR . 'includes/models/ListingModel.php';
require_once PBAY_PLUGIN_DIR . 'includes/models/OrderModel.php';

// --- Controllers ---
require_once PBAY_PLUGIN_DIR . 'includes/controllers/AdminController.php';
require_once PBAY_PLUGIN_DIR . 'includes/controllers/PolicyWalletController.php';
require_once PBAY_PLUGIN_DIR . 'includes/controllers/ListingCategoryController.php';
require_once PBAY_PLUGIN_DIR . 'includes/controllers/ListingController.php';
require_once PBAY_PLUGIN_DIR . 'includes/controllers/CheckoutController.php';
require_once PBAY_PLUGIN_DIR . 'includes/controllers/CatalogController.php';
require_once PBAY_PLUGIN_DIR . 'includes/controllers/AppearanceController.php';

// ============================================================
// Activation / Deactivation
// ============================================================

register_activation_hook(__FILE__, 'pbay_activate');

function pbay_activate() {
    PBay\Models\PolicyWalletModel::create_table();
    PBay\Models\ListingCategoryModel::create_table();
    PBay\Models\ListingModel::create_tables();
    PBay\Models\OrderModel::create_table();
}

register_deactivation_hook(__FILE__, 'pbay_deactivate');

function pbay_deactivate() {
    // Intentionally left empty - data preserved on deactivation
    // Cleanup handled by uninstall.php
}

// ============================================================
// Initialize Controllers
// ============================================================

add_action('init', function () {
    PBay\Controllers\AdminController::register();
    PBay\Controllers\PolicyWalletController::register();
    PBay\Controllers\ListingCategoryController::register();
    PBay\Controllers\ListingController::register();
    PBay\Controllers\CheckoutController::register();
    PBay\Controllers\CatalogController::register();
    PBay\Controllers\AppearanceController::register();
});

// ============================================================
// Admin Menu
// ============================================================

add_action('admin_menu', 'pbay_admin_menu');

function pbay_admin_menu() {
    add_menu_page(
        'PBay',
        'PBay',
        'manage_options',
        'pbay-setup',
        [PBay\Controllers\AdminController::class, 'renderSetupPage'],
        'dashicons-store',
        56
    );

    add_submenu_page(
        'pbay-setup',
        'Setup',
        'Setup',
        'manage_options',
        'pbay-setup',
        [PBay\Controllers\AdminController::class, 'renderSetupPage']
    );

    add_submenu_page(
        'pbay-setup',
        'How It Works',
        'How It Works',
        'manage_options',
        'pbay-how-it-works',
        [PBay\Controllers\AdminController::class, 'renderHowItWorksPage']
    );

    add_submenu_page(
        'pbay-setup',
        'Listing Categories',
        'Listing Categories',
        'manage_options',
        'pbay-listing-categories',
        [PBay\Controllers\ListingCategoryController::class, 'renderPage']
    );

    add_submenu_page(
        'pbay-setup',
        'Create Listing',
        'Create Listing',
        'manage_options',
        'pbay-create-listing',
        [PBay\Controllers\ListingController::class, 'renderCreatePage']
    );

    add_submenu_page(
        'pbay-setup',
        'Inventory',
        'Inventory',
        'manage_options',
        'pbay-inventory',
        [PBay\Controllers\ListingController::class, 'renderInventoryPage']
    );

    add_submenu_page(
        'pbay-setup',
        'Orders',
        'Orders',
        'manage_options',
        'pbay-orders',
        [PBay\Controllers\AdminController::class, 'renderOrdersPage']
    );

    add_submenu_page(
        'pbay-setup',
        'Appearance',
        'Appearance',
        'manage_options',
        'pbay-appearance',
        [PBay\Controllers\AppearanceController::class, 'renderPage']
    );

    add_submenu_page(
        'pbay-setup',
        'Wallet',
        'Wallet',
        'manage_options',
        'pbay-policy-wallet',
        [PBay\Controllers\PolicyWalletController::class, 'renderPage']
    );
}

// ============================================================
// Admin Scripts & Styles
// ============================================================

add_action('admin_enqueue_scripts', function ($hook) {
    // Only on PBay admin pages
    if (strpos($hook, 'pbay') === false) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
        'pbay-admin-css',
        PBAY_PLUGIN_URL . 'assets/css/pbay-admin.css',
        [],
        PBAY_VERSION
    );

    wp_enqueue_script(
        'pbay-admin-js',
        PBAY_PLUGIN_URL . 'assets/js/pbay-admin.js',
        ['jquery'],
        PBAY_VERSION,
        true
    );

    wp_localize_script('pbay-admin-js', 'pbayAdmin', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pbay_admin_nonce'),
        'network' => get_option('pbay_network', 'preprod'),
        'tosAgreed' => (int) get_option('pbay_tos_agreed', 0),
    ]);
});

// ============================================================
// Frontend Scripts & Styles (only on pages with shortcodes)
// ============================================================

add_action('wp_enqueue_scripts', function () {
    global $post;

    // Check if any PBay shortcode is present
    if (!is_a($post, 'WP_Post')) {
        return;
    }

    $has_shortcode = has_shortcode($post->post_content, 'pbay-catalog')
        || has_shortcode($post->post_content, 'pbay-product')
        || has_shortcode($post->post_content, 'pbay-orders');

    if (!$has_shortcode) {
        return;
    }

    wp_enqueue_style(
        'pbay-frontend-css',
        PBAY_PLUGIN_URL . 'assets/css/pbay-frontend.css',
        [],
        PBAY_VERSION
    );

    // Inject theme customizer CSS overrides
    PBay\Controllers\AppearanceController::outputFrontendCSS();

    // Load checkout JS on product, catalog, and orders pages
    if (has_shortcode($post->post_content, 'pbay-product') || has_shortcode($post->post_content, 'pbay-catalog') || has_shortcode($post->post_content, 'pbay-orders')) {
        wp_enqueue_script(
            'pbay-checkout-js',
            PBAY_PLUGIN_URL . 'assets/js/pbay-checkout.js',
            ['jquery'],
            PBAY_VERSION,
            true
        );

        $network = get_option('pbay_network', 'preprod');
        wp_localize_script('pbay-checkout-js', 'pbayCheckout', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pbay_checkout_nonce'),
            'network' => $network,
            'explorer_url' => ($network === 'mainnet')
                ? 'https://cardanoscan.io'
                : 'https://preprod.cardanoscan.io',
        ]);
    }
});

// ============================================================
// ToS Gate: Redirect to How It Works if not agreed
// ============================================================

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('pbay_tos_agreed', 0)) {
        return;
    }

    $page = isset($_GET['page']) ? $_GET['page'] : '';

    // Only act on PBay pages (except how-it-works)
    if (strpos($page, 'pbay') === 0 && $page !== 'pbay-how-it-works') {
        wp_safe_redirect(admin_url('admin.php?page=pbay-how-it-works'));
        exit;
    }
}, 5);

// ============================================================
// DB Migration (adds new columns via dbDelta on version change)
// ============================================================

add_action('admin_init', function () {
    $current_db_version = '1.5.0';
    $installed_version = get_option('pbay_db_version', '1.0.0');

    if (version_compare($installed_version, $current_db_version, '<')) {
        global $wpdb;

        PBay\Models\OrderModel::create_table();
        PBay\Models\ListingCategoryModel::create_table();
        PBay\Models\ListingModel::create_tables();

        // dbDelta won't reliably add columns to existing tables, so add directly
        $listings_table = $wpdb->prefix . 'pbay_listings';
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $listings_table LIKE 'category_id'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $listings_table ADD COLUMN category_id bigint(20) unsigned DEFAULT NULL AFTER category");
            $wpdb->query("ALTER TABLE $listings_table ADD INDEX idx_category_id (category_id)");
        }

        $col_gallery_ipfs = $wpdb->get_results("SHOW COLUMNS FROM $listings_table LIKE 'gallery_ipfs_cids'");
        if (empty($col_gallery_ipfs)) {
            $wpdb->query("ALTER TABLE $listings_table ADD COLUMN gallery_ipfs_cids text DEFAULT NULL AFTER gallery_ids");
        }

        // Listings: shipping_rate column
        $col_shipping_rate = $wpdb->get_results("SHOW COLUMNS FROM $listings_table LIKE 'shipping_rate'");
        if (empty($col_shipping_rate)) {
            $wpdb->query("ALTER TABLE $listings_table ADD COLUMN shipping_rate decimal(10,2) NOT NULL DEFAULT 0.00 AFTER shipping_notes");
        }

        // Listings: ships_to column
        $col_ships_to = $wpdb->get_results("SHOW COLUMNS FROM $listings_table LIKE 'ships_to'");
        if (empty($col_ships_to)) {
            $wpdb->query("ALTER TABLE $listings_table ADD COLUMN ships_to varchar(255) DEFAULT NULL AFTER shipping_rate");
        }

        // Orders: nft_delivery_tx_hash (dbDelta misses this on existing tables)
        $orders_table = $wpdb->prefix . 'pbay_orders';
        $col_nft_delivery = $wpdb->get_results("SHOW COLUMNS FROM $orders_table LIKE 'nft_delivery_tx_hash'");
        if (empty($col_nft_delivery)) {
            $wpdb->query("ALTER TABLE $orders_table ADD COLUMN nft_delivery_tx_hash varchar(128) DEFAULT NULL AFTER tx_hash");
        }

        // Orders: shipping_rate column
        $col_order_shipping = $wpdb->get_results("SHOW COLUMNS FROM $orders_table LIKE 'shipping_rate'");
        if (empty($col_order_shipping)) {
            $wpdb->query("ALTER TABLE $orders_table ADD COLUMN shipping_rate decimal(10,2) NOT NULL DEFAULT 0.00 AFTER price_usd");
        }

        update_option('pbay_db_version', $current_db_version);
    }
});

// ============================================================
// Helper: Get network name
// ============================================================

function pbay_get_network() {
    return get_option('pbay_network', 'preprod');
}
