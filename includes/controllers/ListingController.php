<?php
namespace PBay\Controllers;

use PBay\Models\ListingModel;
use PBay\Models\ListingCategoryModel;
use PBay\Models\PolicyWalletModel;
use PBay\Helpers\AnvilAPI;
use PBay\Helpers\EncryptionHelper;
use PBay\Helpers\MetadataHelper;
use PBay\Helpers\PinataAPI;
use PBay\Helpers\PriceHelper;

class ListingController {

    public static function register() {
        add_action('wp_ajax_pbay_save_listing', [self::class, 'ajaxSaveListing']);
        add_action('wp_ajax_pbay_delete_listing', [self::class, 'ajaxDeleteListing']);
        add_action('wp_ajax_pbay_pin_image_ipfs', [self::class, 'ajaxPinImageIPFS']);
        add_action('wp_ajax_pbay_generate_policy', [self::class, 'ajaxGeneratePolicy']);
        add_action('wp_ajax_pbay_mint_listing_nft', [self::class, 'ajaxMintListingNFT']);
        add_action('wp_ajax_pbay_archive_listing', [self::class, 'ajaxArchiveListing']);
    }

    /**
     * Render Create Listing page
     */
    public static function renderCreatePage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        wp_enqueue_media();

        // Check if editing existing
        $listing = null;
        $meta = [];
        if (isset($_GET['edit'])) {
            $listing = ListingModel::getById(intval($_GET['edit']));
            if ($listing) {
                $meta = ListingModel::getMeta($listing['id']);
            }
        }

        $network = get_option('pbay_network', 'preprod');
        $listing_categories = ListingCategoryModel::getActiveWithPolicy($network);

        $conditions = ['New', 'Like New', 'Very Good', 'Good', 'Acceptable', 'For Parts'];

        include PBAY_PLUGIN_DIR . 'includes/views/admin/create-listing.php';
    }

    /**
     * Render Inventory page
     */
    public static function renderInventoryPage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $status_filter = sanitize_text_field($_GET['status'] ?? '');
        $listings = $status_filter ? ListingModel::getAll($status_filter) : ListingModel::getAll();

        $counts = [
            'all' => count(ListingModel::getAll()),
            'draft' => ListingModel::countByStatus('draft'),
            'minting' => ListingModel::countByStatus('minting'),
            'active' => ListingModel::countByStatus('active'),
            'sold' => ListingModel::countByStatus('sold'),
            'archived' => ListingModel::countByStatus('archived'),
        ];

        include PBAY_PLUGIN_DIR . 'includes/views/admin/inventory.php';
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    /**
     * Save listing (create or update)
     */
    public static function ajaxSaveListing() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);

        // Resolve category_id → category name (dual-write)
        $category_id = intval($_POST['category_id'] ?? 0) ?: null;
        $category_name = '';
        if ($category_id) {
            $cat = ListingCategoryModel::getById($category_id);
            if ($cat) {
                $category_name = $cat['name'];
            }
        }

        $data = [
            'title' => sanitize_text_field(wp_unslash($_POST['title'] ?? '')),
            'description' => wp_kses_post(wp_unslash($_POST['description'] ?? '')),
            'price_usd' => floatval($_POST['price_usd'] ?? 0),
            'category_id' => $category_id,
            'category' => $category_name,
            'condition_type' => sanitize_text_field($_POST['condition_type'] ?? ''),
            'quantity' => intval($_POST['quantity'] ?? 1),
            'image_id' => intval($_POST['image_id'] ?? 0) ?: null,
            'ipfs_cid' => sanitize_text_field($_POST['ipfs_cid'] ?? ''),
            'ipfs_cid_manual' => sanitize_text_field($_POST['ipfs_cid_manual'] ?? ''),
            'gallery_ids' => sanitize_text_field($_POST['gallery_ids'] ?? ''),
            'weight_lbs' => floatval($_POST['weight_lbs'] ?? 0) ?: null,
            'dimensions' => sanitize_text_field(wp_unslash($_POST['dimensions'] ?? '')),
            'ships_from' => sanitize_text_field(wp_unslash($_POST['ships_from'] ?? '')),
            'shipping_notes' => sanitize_textarea_field(wp_unslash($_POST['shipping_notes'] ?? '')),
            'shipping_rate' => floatval($_POST['shipping_rate'] ?? 0),
            'ships_to' => sanitize_text_field(wp_unslash($_POST['ships_to'] ?? '')),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft'),
        ];

        // Calculate ADA price
        if ($data['price_usd'] > 0) {
            $data['price_ada'] = PriceHelper::usdToAda($data['price_usd']);
        }

        if ($listing_id > 0) {
            // Track category changes for listing_count
            $old_listing = ListingModel::getById($listing_id);
            $old_cat_id = $old_listing ? intval($old_listing['category_id'] ?? 0) : 0;
            $new_cat_id = intval($category_id ?? 0);

            ListingModel::update($listing_id, $data);

            // Update listing counts if category changed
            if ($old_cat_id !== $new_cat_id) {
                if ($old_cat_id > 0) {
                    ListingCategoryModel::decrementListingCount($old_cat_id);
                }
                if ($new_cat_id > 0) {
                    ListingCategoryModel::incrementListingCount($new_cat_id);
                }
            }
        } else {
            $listing_id = ListingModel::insert($data);

            // Increment listing count for new listing
            if ($category_id > 0) {
                ListingCategoryModel::incrementListingCount($category_id);
            }
        }

        if (!$listing_id) {
            wp_send_json_error(['message' => 'Failed to save listing']);
        }

        // Save meta attributes
        if (isset($_POST['meta_keys']) && is_array($_POST['meta_keys'])) {
            $meta_array = [];
            $keys = wp_unslash($_POST['meta_keys']);
            $values = wp_unslash($_POST['meta_values'] ?? []);
            for ($i = 0; $i < count($keys); $i++) {
                $k = sanitize_text_field($keys[$i]);
                $v = sanitize_text_field($values[$i] ?? '');
                if (!empty($k)) {
                    $meta_array[] = ['key' => $k, 'value' => $v];
                }
            }
            ListingModel::replaceMeta($listing_id, $meta_array);
        }

        wp_send_json_success([
            'message' => 'Listing saved',
            'listing_id' => $listing_id,
            'edit_url' => admin_url('admin.php?page=pbay-create-listing&edit=' . $listing_id),
        ]);
    }

    /**
     * Delete listing
     */
    public static function ajaxDeleteListing() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        if ($listing_id > 0) {
            ListingModel::delete($listing_id);
            wp_send_json_success(['message' => 'Listing deleted']);
        }
        wp_send_json_error(['message' => 'Invalid listing ID']);
    }

    /**
     * Pin image to IPFS via Pinata
     */
    public static function ajaxPinImageIPFS() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $image_id = intval($_POST['image_id'] ?? 0);
        if (!$image_id) {
            wp_send_json_error(['message' => 'No image ID provided']);
        }

        $file_path = get_attached_file($image_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(['message' => 'Image file not found']);
        }

        $name = sanitize_text_field($_POST['name'] ?? basename($file_path));
        $result = PinataAPI::uploadImage($file_path, $name);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'cid' => $result['cid'],
            'mediaType' => $result['mediaType'],
            'ipfs_url' => 'ipfs://' . $result['cid'],
        ]);
    }

    /**
     * Generate policy for a listing
     */
    public static function ajaxGeneratePolicy() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $expiration_date = sanitize_text_field($_POST['expiration_date'] ?? '');

        $network = get_option('pbay_network', 'preprod');
        $wallet = PolicyWalletModel::getActiveWallet($network);

        if (!$wallet) {
            wp_send_json_error(['message' => 'No active policy wallet. Generate one first in Policy Wallet page.']);
        }

        $result = AnvilAPI::generatePolicy($wallet['payment_keyhash'], $expiration_date ?: null);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Save policy data to listing
        if ($listing_id > 0) {
            ListingModel::update($listing_id, [
                'policy_id' => $result['policyId'],
                'policy_json' => wp_json_encode($result),
                'asset_name' => sanitize_text_field($_POST['asset_name'] ?? ('PBAY_' . $listing_id . '_' . time())),
            ]);
        }

        wp_send_json_success([
            'policyId' => $result['policyId'],
            'policy_json' => $result,
            'message' => 'Policy generated: ' . $result['policyId'],
        ]);
    }

    /**
     * Mint listing NFT (Phase 4 integration point)
     */
    public static function ajaxMintListingNFT() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $listing = ListingModel::getById($listing_id);

        if (!$listing) {
            wp_send_json_error(['message' => 'Listing not found']);
        }

        // Source policy: check listing's own policy first (backward compat), then category
        $policy_id = $listing['policy_id'] ?? null;
        $policy_json_raw = $listing['policy_json'] ?? null;

        if (empty($policy_id) || empty($policy_json_raw)) {
            // Look up from category
            $cat_id = intval($listing['category_id'] ?? 0);
            if ($cat_id <= 0) {
                wp_send_json_error(['message' => 'No category assigned. Select a category before minting.']);
            }

            $category = ListingCategoryModel::getById($cat_id);
            if (!$category || empty($category['policy_id'])) {
                wp_send_json_error(['message' => 'Category has no policy. Recreate the category or contact support.']);
            }

            $policy_id = $category['policy_id'];
            $policy_json_raw = $category['policy_json'];
        }

        // Get policy wallet
        $network = get_option('pbay_network', 'preprod');
        $wallet = PolicyWalletModel::getActiveWallet($network);

        if (!$wallet) {
            wp_send_json_error(['message' => 'No active policy wallet']);
        }

        // Validate wallet keyhash matches the policy's keyhash (if sourced from category)
        if (!empty($listing['category_id'])) {
            if (!isset($category)) {
                $category = ListingCategoryModel::getById(intval($listing['category_id']));
            }
            if ($category && !empty($category['policy_keyhash']) && $wallet['payment_keyhash'] !== $category['policy_keyhash']) {
                wp_send_json_error(['message' => 'Active wallet does not match the wallet used to create this category\'s policy. Switch to the correct wallet or create a new category.']);
            }
        }

        // Decrypt signing key
        $skey_hex = EncryptionHelper::decrypt($wallet['skey_encrypted']);
        if (empty($skey_hex)) {
            wp_send_json_error(['message' => 'Could not decrypt policy wallet signing key']);
        }

        // Auto-pin main image to IPFS if not already pinned
        if (empty($listing['ipfs_cid']) && empty($listing['ipfs_cid_manual']) && !empty($listing['image_id'])) {
            $file_path = get_attached_file(intval($listing['image_id']));
            if ($file_path && file_exists($file_path)) {
                error_log('[PBay][MINT] Auto-pinning main image to IPFS...');
                $pin_result = PinataAPI::uploadImage($file_path, $listing['title'] ?? 'listing-image');
                if (!is_wp_error($pin_result) && !empty($pin_result['cid'])) {
                    ListingModel::update($listing_id, ['ipfs_cid' => $pin_result['cid']]);
                    $listing['ipfs_cid'] = $pin_result['cid'];
                    error_log('[PBay][MINT] Auto-pinned to IPFS: ' . $pin_result['cid']);
                } else {
                    $pin_err = is_wp_error($pin_result) ? $pin_result->get_error_message() : 'Unknown';
                    error_log('[PBay][MINT] IPFS auto-pin failed: ' . $pin_err . ' — falling back to WP media URL');
                }
            }
        }

        // Auto-pin gallery images to IPFS
        $gallery_ids = array_filter(explode(',', $listing['gallery_ids'] ?? ''));
        $existing_cids = array_filter(explode(',', $listing['gallery_ipfs_cids'] ?? ''));

        if (!empty($gallery_ids)) {
            $gallery_cids = [];
            foreach ($gallery_ids as $i => $gid) {
                $gid = intval($gid);
                // Use existing CID if already pinned
                if (isset($existing_cids[$i]) && !empty($existing_cids[$i])) {
                    $gallery_cids[] = $existing_cids[$i];
                    continue;
                }
                $gfile = get_attached_file($gid);
                if ($gfile && file_exists($gfile)) {
                    error_log('[PBay][MINT] Auto-pinning gallery image #' . ($i + 1) . ' to IPFS...');
                    $gpin = PinataAPI::uploadImage($gfile, ($listing['title'] ?? 'gallery') . '-' . ($i + 1));
                    if (!is_wp_error($gpin) && !empty($gpin['cid'])) {
                        $gallery_cids[] = $gpin['cid'];
                        error_log('[PBay][MINT] Gallery image #' . ($i + 1) . ' pinned: ' . $gpin['cid']);
                    } else {
                        $gallery_cids[] = '';
                        error_log('[PBay][MINT] Gallery image #' . ($i + 1) . ' pin failed');
                    }
                } else {
                    $gallery_cids[] = '';
                }
            }
            $cids_str = implode(',', $gallery_cids);
            ListingModel::update($listing_id, ['gallery_ipfs_cids' => $cids_str]);
            $listing['gallery_ipfs_cids'] = $cids_str;
        }

        // Build CIP-25 metadata
        $meta_rows = ListingModel::getMeta($listing_id);
        $cip25_metadata = MetadataHelper::buildCIP25Metadata($listing, $meta_rows);

        // Parse policy schema from stored JSON
        $policy_data = json_decode($policy_json_raw, true);
        $policy_schema = $policy_data['schema'] ?? null;

        $asset_name = 'PBAY_' . $listing_id . '_' . time();

        // Update status to minting
        ListingModel::update($listing_id, ['status' => 'minting']);

        // Build mint TX via Anvil
        $build_result = AnvilAPI::buildMintTransaction(
            $wallet['payment_address'],
            $policy_id,
            $asset_name,
            $cip25_metadata,
            $policy_schema
        );

        if (is_wp_error($build_result)) {
            ListingModel::update($listing_id, ['status' => 'draft']);
            wp_send_json_error(['message' => 'Failed to build mint TX: ' . $build_result->get_error_message()]);
        }

        $unsigned_tx = $build_result['complete'] ?? $build_result['stripped'] ?? null;
        if (!$unsigned_tx) {
            ListingModel::update($listing_id, ['status' => 'draft']);
            wp_send_json_error(['message' => 'No transaction hex returned from Anvil']);
        }

        // Sign with policy wallet
        $sign_result = \CardanoTransactionSignerPHP::signTransaction($unsigned_tx, $skey_hex);

        if (!$sign_result || !$sign_result['success']) {
            ListingModel::update($listing_id, ['status' => 'draft']);
            wp_send_json_error(['message' => 'Signing failed: ' . ($sign_result['error'] ?? 'Unknown')]);
        }

        // Submit via Anvil
        $submit_result = AnvilAPI::submitTransaction($unsigned_tx, [$sign_result['witnessSetHex']]);

        if (is_wp_error($submit_result)) {
            ListingModel::update($listing_id, ['status' => 'draft']);
            wp_send_json_error(['message' => 'Submit failed: ' . $submit_result->get_error_message()]);
        }

        $tx_hash = $submit_result['txHash'] ?? $submit_result['hash'] ?? '';

        // Update listing to active with mint data (write policy to listing for self-containment)
        ListingModel::update($listing_id, [
            'status' => 'active',
            'policy_id' => $policy_id,
            'policy_json' => $policy_json_raw,
            'mint_tx_hash' => $tx_hash,
            'asset_name' => $asset_name,
            'nft_metadata' => wp_json_encode($cip25_metadata),
            'published_at' => current_time('mysql'),
        ]);

        $explorer_url = ($network === 'mainnet')
            ? 'https://cardanoscan.io/transaction/' . $tx_hash
            : 'https://preprod.cardanoscan.io/transaction/' . $tx_hash;

        wp_send_json_success([
            'message' => 'NFT minted successfully!',
            'tx_hash' => $tx_hash,
            'explorer_url' => $explorer_url,
        ]);
    }

    /**
     * Archive listing
     */
    public static function ajaxArchiveListing() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $result = ListingModel::update($listing_id, ['status' => 'archived']);

        if ($result !== false) {
            wp_send_json_success(['message' => 'Listing archived']);
        }
        wp_send_json_error(['message' => 'Failed to archive listing']);
    }
}
