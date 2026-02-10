<?php
namespace PBay\Controllers;

use PBay\Models\ListingCategoryModel;
use PBay\Models\PolicyWalletModel;
use PBay\Helpers\AnvilAPI;

class ListingCategoryController {

    public static function register() {
        add_action('wp_ajax_pbay_create_category', [self::class, 'ajaxCreateCategory']);
        add_action('wp_ajax_pbay_delete_category', [self::class, 'ajaxDeleteCategory']);
        add_action('wp_ajax_pbay_get_categories', [self::class, 'ajaxGetCategories']);
    }

    /**
     * Render the Listing Categories admin page
     */
    public static function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $network = get_option('pbay_network', 'preprod');
        $categories = ListingCategoryModel::getAll($network);
        $active_wallet = PolicyWalletModel::getActiveWallet($network);

        include PBAY_PLUGIN_DIR . 'includes/views/admin/listing-categories.php';
    }

    /**
     * Create a new listing category with auto-generated policy
     */
    public static function ajaxCreateCategory() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));

        if (empty($name)) {
            wp_send_json_error(['message' => 'Category name is required.']);
        }

        $network = get_option('pbay_network', 'preprod');

        // Check for duplicate name
        if (ListingCategoryModel::nameExists($name, $network)) {
            wp_send_json_error(['message' => 'A category with this name already exists.']);
        }

        // Get active wallet
        $wallet = PolicyWalletModel::getActiveWallet($network);
        if (!$wallet) {
            wp_send_json_error(['message' => 'No active policy wallet. Create one first on the Policy Wallet page.']);
        }

        // Calculate expiration: +1 year from now
        $expiration = date('Y-m-d\TH:i:s\Z', strtotime('+1 year'));

        // Generate policy via Anvil
        $result = AnvilAPI::generatePolicy($wallet['payment_keyhash'], $expiration);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Policy generation failed: ' . $result->get_error_message()]);
        }

        // Insert category
        $category_id = ListingCategoryModel::insert([
            'name' => $name,
            'description' => $description,
            'policy_id' => $result['policyId'],
            'policy_json' => wp_json_encode($result),
            'policy_keyhash' => $wallet['payment_keyhash'],
            'wallet_id' => $wallet['id'],
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'network' => $network,
            'status' => 'active',
        ]);

        if (!$category_id) {
            wp_send_json_error(['message' => 'Failed to save category to database.']);
        }

        wp_send_json_success([
            'message' => 'Category created!',
            'category_id' => $category_id,
        ]);
    }

    /**
     * Delete a listing category
     */
    public static function ajaxDeleteCategory() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $category_id = intval($_POST['category_id'] ?? 0);
        if ($category_id <= 0) {
            wp_send_json_error(['message' => 'Invalid category ID.']);
        }

        $result = ListingCategoryModel::delete($category_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Category deleted.']);
    }

    /**
     * Get active categories as JSON
     */
    public static function ajaxGetCategories() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $network = get_option('pbay_network', 'preprod');
        $categories = ListingCategoryModel::getActiveWithPolicy($network);

        wp_send_json_success(['categories' => $categories]);
    }
}
