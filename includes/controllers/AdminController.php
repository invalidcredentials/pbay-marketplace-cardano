<?php
namespace PBay\Controllers;

use PBay\Models\OrderModel;
use PBay\Models\PolicyWalletModel;

class AdminController {

    public static function register() {
        add_action('wp_ajax_pbay_test_anvil', [self::class, 'ajaxTestAnvil']);
        add_action('wp_ajax_pbay_test_pinata', [self::class, 'ajaxTestPinata']);
        // ADA price endpoint moved to CheckoutController (public-facing)
        add_action('wp_ajax_pbay_update_order_status', [self::class, 'ajaxUpdateOrderStatus']);
        add_action('wp_ajax_pbay_update_tracking', [self::class, 'ajaxUpdateTracking']);
        add_action('wp_ajax_pbay_send_nft', [self::class, 'ajaxSendNft']);
        add_action('wp_ajax_pbay_export_orders_csv', [self::class, 'ajaxExportOrdersCsv']);
    }

    /**
     * Render the Setup page
     */
    public static function renderSetupPage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $message = '';

        // Handle save
        if (isset($_POST['pbay_save_settings'])) {
            check_admin_referer('pbay_settings_nonce');

            $network = sanitize_text_field($_POST['pbay_network'] ?? 'preprod');
            $api_url = ($network === 'mainnet')
                ? 'https://prod.api.ada-anvil.app/v2/services'
                : 'https://preprod.api.ada-anvil.app/v2/services';

            update_option('pbay_network', $network);
            update_option('pbay_anvil_api_url', $api_url);
            update_option('pbay_anvil_api_key_mainnet', sanitize_text_field($_POST['pbay_anvil_api_key_mainnet'] ?? ''));
            update_option('pbay_anvil_api_key_preprod', sanitize_text_field($_POST['pbay_anvil_api_key_preprod'] ?? ''));

            // Set active API key based on network
            $active_key = ($network === 'mainnet')
                ? sanitize_text_field($_POST['pbay_anvil_api_key_mainnet'] ?? '')
                : sanitize_text_field($_POST['pbay_anvil_api_key_preprod'] ?? '');
            update_option('pbay_anvil_api_key', $active_key);

            update_option('pbay_merchant_address', sanitize_text_field($_POST['pbay_merchant_address'] ?? ''));
            update_option('pbay_store_name', sanitize_text_field($_POST['pbay_store_name'] ?? ''));

            // Blockfrost
            update_option('pbay_blockfrost_api_key_preprod', sanitize_text_field($_POST['pbay_blockfrost_api_key_preprod'] ?? ''));
            update_option('pbay_blockfrost_api_key_mainnet', sanitize_text_field($_POST['pbay_blockfrost_api_key_mainnet'] ?? ''));

            // Pinata
            update_option('pbay_pinata_jwt', sanitize_text_field($_POST['pbay_pinata_jwt'] ?? ''));
            update_option('pbay_pinata_enabled', isset($_POST['pbay_pinata_enabled']) ? 1 : 0);

            // Store wallet as payout
            update_option('pbay_use_store_wallet_payout', isset($_POST['pbay_use_store_wallet_payout']) ? 1 : 0);

            $message = 'Settings saved successfully.';
        }

        // Pass wallet status to view
        $network = get_option('pbay_network', 'preprod');
        $store_wallet = PolicyWalletModel::getActiveWallet($network);
        $use_store_wallet_payout = get_option('pbay_use_store_wallet_payout', 0);

        include PBAY_PLUGIN_DIR . 'includes/views/admin/setup.php';
    }

    /**
     * Render Orders page
     */
    public static function renderOrdersPage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Check for order detail view
        if (isset($_GET['order_id'])) {
            $order = OrderModel::getById(intval($_GET['order_id']));
            if ($order) {
                $listing = \PBay\Models\ListingModel::getById($order['listing_id']);
                include PBAY_PLUGIN_DIR . 'includes/views/admin/order-detail.php';
                return;
            }
        }

        $status_filter = sanitize_text_field($_GET['status'] ?? '');
        $orders = OrderModel::getAll($status_filter ?: null);
        $stats = OrderModel::getStats();

        include PBAY_PLUGIN_DIR . 'includes/views/admin/orders.php';
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    public static function ajaxTestAnvil() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $result = \PBay\Helpers\AnvilAPI::testConnection();

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public static function ajaxTestPinata() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $result = \PBay\Helpers\PinataAPI::testConnection();

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public static function ajaxGetAdaPrice() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');

        $info = \PBay\Helpers\PriceHelper::getExchangeRateInfo();
        wp_send_json_success($info);
    }

    public static function ajaxUpdateOrderStatus() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        $valid_statuses = ['pending', 'paid', 'processing', 'shipped', 'completed', 'disputed', 'refunded'];
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(['message' => 'Invalid status']);
        }

        $result = OrderModel::updateStatus($order_id, $status);
        if ($result !== false) {
            wp_send_json_success(['message' => 'Order status updated']);
        }
        wp_send_json_error(['message' => 'Failed to update order status']);
    }

    public static function ajaxUpdateTracking() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $tracking_number = sanitize_text_field($_POST['tracking_number'] ?? '');
        $tracking_carrier = sanitize_text_field($_POST['tracking_carrier'] ?? '');

        $result = OrderModel::updateTracking($order_id, $tracking_number, $tracking_carrier);
        if ($result !== false) {
            wp_send_json_success(['message' => 'Tracking info updated']);
        }
        wp_send_json_error(['message' => 'Failed to update tracking']);
    }

    public static function ajaxSendNft() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $order = OrderModel::getById($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found']);
        }

        if (!empty($order['nft_delivery_tx_hash'])) {
            wp_send_json_error(['message' => 'NFT already delivered (TX: ' . $order['nft_delivery_tx_hash'] . ')']);
        }

        $listing = \PBay\Models\ListingModel::getById($order['listing_id']);
        if (!$listing || empty($listing['policy_id']) || empty($listing['asset_name'])) {
            wp_send_json_error(['message' => 'Listing has no minted NFT']);
        }

        $network = get_option('pbay_network', 'preprod');
        $wallet = \PBay\Models\PolicyWalletModel::getActiveWallet($network);

        if (!$wallet) {
            wp_send_json_error(['message' => 'No active policy wallet']);
        }

        $skey_hex = \PBay\Helpers\EncryptionHelper::decrypt($wallet['skey_encrypted']);
        if (empty($skey_hex)) {
            wp_send_json_error(['message' => 'Could not decrypt policy wallet signing key']);
        }

        // Build transfer TX
        $build_result = \PBay\Helpers\AnvilAPI::buildAssetTransferTransaction(
            $wallet['payment_address'],
            $order['buyer_address'],
            $listing['policy_id'],
            $listing['asset_name']
        );

        if (is_wp_error($build_result)) {
            wp_send_json_error(['message' => 'Build failed: ' . $build_result->get_error_message()]);
        }

        $unsigned_tx = $build_result['complete'] ?? $build_result['stripped'] ?? null;
        if (!$unsigned_tx) {
            wp_send_json_error(['message' => 'No transaction hex returned from Anvil']);
        }

        // Sign with policy wallet
        $sign_result = \CardanoTransactionSignerPHP::signTransaction($unsigned_tx, $skey_hex);

        if (!$sign_result || !$sign_result['success']) {
            wp_send_json_error(['message' => 'Signing failed: ' . ($sign_result['error'] ?? 'Unknown')]);
        }

        // Submit
        $submit_result = \PBay\Helpers\AnvilAPI::submitTransaction($unsigned_tx, [$sign_result['witnessSetHex']]);

        if (is_wp_error($submit_result)) {
            wp_send_json_error(['message' => 'Submit failed: ' . $submit_result->get_error_message()]);
        }

        $tx_hash = $submit_result['txHash'] ?? $submit_result['hash'] ?? '';
        OrderModel::updateNftDelivery($order_id, $tx_hash);

        $explorer_url = ($network === 'mainnet')
            ? 'https://cardanoscan.io/transaction/' . $tx_hash
            : 'https://preprod.cardanoscan.io/transaction/' . $tx_hash;

        error_log('[PBay][NFT_DELIVERY] Admin manual send SUCCESS: ' . $tx_hash);

        wp_send_json_success([
            'message' => 'NFT delivered successfully!',
            'tx_hash' => $tx_hash,
            'explorer_url' => $explorer_url,
        ]);
    }

    public static function ajaxExportOrdersCsv() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $orders = OrderModel::getAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pbay-orders-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Listing', 'Buyer', 'Email', 'USD', 'ADA', 'TX Hash', 'Status', 'Created', 'Tracking']);

        foreach ($orders as $order) {
            fputcsv($output, [
                $order['order_id'],
                $order['listing_title'] ?? '',
                $order['buyer_address'],
                $order['buyer_email'] ?? '',
                $order['price_usd'],
                $order['price_ada'],
                $order['tx_hash'] ?? '',
                $order['status'],
                $order['created_at'],
                $order['tracking_number'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }
}
