<?php
namespace PBay\Controllers;

use PBay\Models\ListingModel;
use PBay\Models\OrderModel;
use PBay\Models\PolicyWalletModel;
use PBay\Helpers\AnvilAPI;
use PBay\Helpers\EncryptionHelper;
use PBay\Helpers\PriceHelper;

class CheckoutController {

    public static function register() {
        add_action('wp_ajax_pbay_build_payment_tx', [self::class, 'ajaxBuildPaymentTx']);
        add_action('wp_ajax_nopriv_pbay_build_payment_tx', [self::class, 'ajaxBuildPaymentTx']);

        add_action('wp_ajax_pbay_submit_payment', [self::class, 'ajaxSubmitPayment']);
        add_action('wp_ajax_nopriv_pbay_submit_payment', [self::class, 'ajaxSubmitPayment']);

        add_action('wp_ajax_pbay_lookup_order', [self::class, 'ajaxLookupOrder']);
        add_action('wp_ajax_nopriv_pbay_lookup_order', [self::class, 'ajaxLookupOrder']);

        add_action('wp_ajax_pbay_get_buyer_orders', [self::class, 'ajaxGetBuyerOrders']);
        add_action('wp_ajax_nopriv_pbay_get_buyer_orders', [self::class, 'ajaxGetBuyerOrders']);

        // Public ADA price endpoint (uses checkout nonce, works for logged-out users)
        add_action('wp_ajax_pbay_get_ada_price', [self::class, 'ajaxGetAdaPrice']);
        add_action('wp_ajax_nopriv_pbay_get_ada_price', [self::class, 'ajaxGetAdaPrice']);
    }

    /**
     * Build payment transaction for buyer
     */
    public static function ajaxBuildPaymentTx() {
        check_ajax_referer('pbay_checkout_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $buyer_address = sanitize_text_field($_POST['buyer_address'] ?? '');

        if (!$listing_id || empty($buyer_address)) {
            wp_send_json_error(['message' => 'Missing listing ID or buyer address']);
        }

        $listing = ListingModel::getById($listing_id);
        if (!$listing || $listing['status'] !== 'active') {
            wp_send_json_error(['message' => 'Listing not available']);
        }

        if (!ListingModel::hasStock($listing_id)) {
            wp_send_json_error(['message' => 'Item is out of stock']);
        }

        $merchant_address = get_option('pbay_merchant_address', '');
        if (empty($merchant_address)) {
            wp_send_json_error(['message' => 'Store not configured for payments']);
        }

        $item_price = floatval($listing['price_usd']);
        $shipping_rate = floatval($listing['shipping_rate'] ?? 0);
        $usd_price = $item_price + $shipping_rate;
        if ($usd_price <= 0) {
            wp_send_json_error(['message' => 'Invalid listing price']);
        }

        error_log('[PBay][BUILD] listing_id=' . $listing_id . ' item_price=' . $item_price . ' shipping=' . $shipping_rate . ' total=' . $usd_price . ' merchant_address=' . $merchant_address . ' buyer_address=' . $buyer_address);

        $build_result = AnvilAPI::buildPaymentTransaction(
            $merchant_address,
            $buyer_address,
            $usd_price,
            $listing['title']
        );

        if (is_wp_error($build_result)) {
            wp_send_json_error(['message' => 'Failed to build transaction: ' . $build_result->get_error_message()]);
        }

        $tx_hex = $build_result['complete'] ?? $build_result['stripped'] ?? null;
        error_log('[PBay] Returning to JS — tx_hex is ' . ($tx_hex ? strlen($tx_hex) . ' chars' : 'NULL'));

        wp_send_json_success([
            'transaction' => $tx_hex,
            'price_usd' => $usd_price,
            'exchange_rate' => PriceHelper::getAdaPrice(),
        ]);
    }

    /**
     * Submit signed payment transaction
     */
    public static function ajaxSubmitPayment() {
        check_ajax_referer('pbay_checkout_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $transaction = sanitize_text_field($_POST['transaction'] ?? '');
        $signature = sanitize_text_field($_POST['signature'] ?? '');
        $buyer_address = sanitize_text_field($_POST['buyer_address'] ?? '');

        if (!$listing_id || empty($transaction) || empty($signature)) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }

        $listing = ListingModel::getById($listing_id);
        if (!$listing) {
            wp_send_json_error(['message' => 'Listing not found']);
        }

        // Submit to Anvil
        $submit_result = AnvilAPI::submitTransaction($transaction, [$signature]);

        if (is_wp_error($submit_result)) {
            wp_send_json_error(['message' => 'Transaction failed: ' . $submit_result->get_error_message()]);
        }

        $tx_hash = $submit_result['txHash'] ?? $submit_result['hash'] ?? '';

        // Calculate prices at time of purchase (total = item + shipping)
        $ada_price = PriceHelper::getAdaPrice();
        $shipping_rate = floatval($listing['shipping_rate'] ?? 0);
        $total_usd = floatval($listing['price_usd']) + $shipping_rate;
        $price_ada = PriceHelper::usdToAda($total_usd);

        // Create order record
        $order_data = [
            'listing_id' => $listing_id,
            'buyer_address' => $buyer_address,
            'buyer_email' => sanitize_email(wp_unslash($_POST['buyer_email'] ?? '')),
            'buyer_name' => sanitize_text_field(wp_unslash($_POST['buyer_name'] ?? '')),
            'shipping_name' => sanitize_text_field(wp_unslash($_POST['shipping_name'] ?? '')),
            'shipping_address_1' => sanitize_text_field(wp_unslash($_POST['shipping_address_1'] ?? '')),
            'shipping_address_2' => sanitize_text_field(wp_unslash($_POST['shipping_address_2'] ?? '')),
            'shipping_city' => sanitize_text_field(wp_unslash($_POST['shipping_city'] ?? '')),
            'shipping_state' => sanitize_text_field(wp_unslash($_POST['shipping_state'] ?? '')),
            'shipping_postal' => sanitize_text_field(wp_unslash($_POST['shipping_postal'] ?? '')),
            'shipping_country' => sanitize_text_field(wp_unslash($_POST['shipping_country'] ?? '')),
            'shipping_phone' => sanitize_text_field(wp_unslash($_POST['shipping_phone'] ?? '')),
            'price_usd' => $total_usd,
            'shipping_rate' => $shipping_rate,
            'price_ada' => $price_ada,
            'exchange_rate' => $ada_price,
            'tx_hash' => $tx_hash,
            'status' => 'paid',
        ];

        $order = OrderModel::create($order_data);

        // Increment sold count
        ListingModel::incrementSold($listing_id);

        // Check if fully sold out
        $updated_listing = ListingModel::getById($listing_id);
        if ($updated_listing && $updated_listing['quantity_sold'] >= $updated_listing['quantity']) {
            ListingModel::update($listing_id, ['status' => 'sold', 'sold_at' => current_time('mysql')]);
        }

        // Attempt NFT delivery (non-blocking — failure does NOT fail the order)
        $nft_delivery_tx_hash = null;
        $listing_has_nft = !empty($listing['policy_id']) && !empty($listing['asset_name']);

        if ($listing_has_nft && !empty($buyer_address)) {
            try {
                $nft_delivery_tx_hash = self::deliverNftToBuyer(
                    $listing['policy_id'],
                    $listing['asset_name'],
                    $buyer_address
                );
                if ($nft_delivery_tx_hash && $order) {
                    OrderModel::updateNftDelivery($order['id'], $nft_delivery_tx_hash);
                }
            } catch (\Exception $e) {
                error_log('[PBay][NFT_DELIVERY] FAILED: ' . $e->getMessage());
            }
        }

        $network = get_option('pbay_network', 'preprod');
        $explorer_base = ($network === 'mainnet')
            ? 'https://cardanoscan.io/transaction/'
            : 'https://preprod.cardanoscan.io/transaction/';

        $response_data = [
            'message' => 'Payment successful!',
            'order_id' => $order ? $order['order_id'] : null,
            'tx_hash' => $tx_hash,
            'explorer_url' => $explorer_base . $tx_hash,
        ];

        if ($nft_delivery_tx_hash) {
            $response_data['nft_delivery_tx_hash'] = $nft_delivery_tx_hash;
            $response_data['nft_delivery_explorer_url'] = $explorer_base . $nft_delivery_tx_hash;
        }

        wp_send_json_success($response_data);
    }

    /**
     * Deliver NFT from policy wallet to buyer (server-side sign + submit)
     */
    private static function deliverNftToBuyer($policy_id, $asset_name, $buyer_address) {
        $network = get_option('pbay_network', 'preprod');
        $wallet = PolicyWalletModel::getActiveWallet($network);

        if (!$wallet) {
            error_log('[PBay][NFT_DELIVERY] No active policy wallet');
            return null;
        }

        $skey_hex = EncryptionHelper::decrypt($wallet['skey_encrypted']);
        if (empty($skey_hex)) {
            error_log('[PBay][NFT_DELIVERY] Could not decrypt policy wallet signing key');
            return null;
        }

        // Build transfer TX
        $build_result = AnvilAPI::buildAssetTransferTransaction(
            $wallet['payment_address'],
            $buyer_address,
            $policy_id,
            $asset_name
        );

        if (is_wp_error($build_result)) {
            error_log('[PBay][NFT_DELIVERY] Build failed: ' . $build_result->get_error_message());
            return null;
        }

        $unsigned_tx = $build_result['complete'] ?? $build_result['stripped'] ?? null;
        if (!$unsigned_tx) {
            error_log('[PBay][NFT_DELIVERY] No transaction hex from Anvil');
            return null;
        }

        // Sign with policy wallet
        $sign_result = \CardanoTransactionSignerPHP::signTransaction($unsigned_tx, $skey_hex);

        if (!$sign_result || !$sign_result['success']) {
            error_log('[PBay][NFT_DELIVERY] Signing failed: ' . ($sign_result['error'] ?? 'Unknown'));
            return null;
        }

        // Submit
        $submit_result = AnvilAPI::submitTransaction($unsigned_tx, [$sign_result['witnessSetHex']]);

        if (is_wp_error($submit_result)) {
            error_log('[PBay][NFT_DELIVERY] Submit failed: ' . $submit_result->get_error_message());
            return null;
        }

        $delivery_tx_hash = $submit_result['txHash'] ?? $submit_result['hash'] ?? '';
        error_log('[PBay][NFT_DELIVERY] SUCCESS: ' . $delivery_tx_hash);

        return $delivery_tx_hash ?: null;
    }

    /**
     * Look up order by order_id or tx_hash
     */
    public static function ajaxLookupOrder() {
        check_ajax_referer('pbay_checkout_nonce', 'nonce');

        $query = sanitize_text_field($_POST['query'] ?? '');

        if (empty($query)) {
            wp_send_json_error(['message' => 'Please enter an order ID or transaction hash']);
        }

        // Try order_id first
        $order = OrderModel::getByOrderId($query);
        if (!$order) {
            $order = OrderModel::getByTxHash($query);
        }

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found']);
        }

        $listing = ListingModel::getById($order['listing_id']);

        wp_send_json_success([
            'order' => $order,
            'listing_title' => $listing ? $listing['title'] : 'Unknown',
        ]);
    }

    /**
     * Get live ADA price (works from both admin and frontend)
     */
    public static function ajaxGetAdaPrice() {
        $nonce = sanitize_text_field($_POST['nonce'] ?? $_GET['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'pbay_checkout_nonce') && !wp_verify_nonce($nonce, 'pbay_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $info = PriceHelper::getExchangeRateInfo();
        wp_send_json_success($info);
    }

    /**
     * Get orders for a buyer wallet address
     */
    public static function ajaxGetBuyerOrders() {
        check_ajax_referer('pbay_checkout_nonce', 'nonce');

        $buyer_address = sanitize_text_field($_POST['buyer_address'] ?? '');

        if (empty($buyer_address)) {
            wp_send_json_error(['message' => 'No wallet address provided']);
        }

        $orders = OrderModel::getByBuyer($buyer_address);

        $network = get_option('pbay_network', 'preprod');
        $explorer_base = ($network === 'mainnet')
            ? 'https://cardanoscan.io/transaction/'
            : 'https://preprod.cardanoscan.io/transaction/';

        // Enrich each order with image URL and explorer links
        foreach ($orders as &$o) {
            $o['explorer_url'] = !empty($o['tx_hash']) ? $explorer_base . $o['tx_hash'] : '';
            $o['nft_delivery_explorer_url'] = !empty($o['nft_delivery_tx_hash']) ? $explorer_base . $o['nft_delivery_tx_hash'] : '';

            // Get listing image URL
            $o['listing_image_url'] = '';
            if (!empty($o['listing_image_id'])) {
                $img = wp_get_attachment_image_url(intval($o['listing_image_id']), 'medium');
                if ($img) {
                    $o['listing_image_url'] = $img;
                }
            }
        }
        unset($o);

        wp_send_json_success(['orders' => $orders]);
    }
}
