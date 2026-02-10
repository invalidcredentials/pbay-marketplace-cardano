<?php
namespace PBay\Controllers;

use PBay\Models\PolicyWalletModel;
use PBay\Helpers\EncryptionHelper;
use PBay\Helpers\BlockfrostAPI;
use PBay\Helpers\AnvilAPI;

class PolicyWalletController {

    public static function register() {
        add_action('admin_init', [self::class, 'handleFormSubmissions']);
        add_action('wp_ajax_pbay_archive_wallet', [self::class, 'ajaxArchiveWallet']);
        add_action('wp_ajax_pbay_unarchive_wallet', [self::class, 'ajaxUnarchiveWallet']);
        add_action('wp_ajax_pbay_get_wallet_balance', [self::class, 'ajaxGetWalletBalance']);
        add_action('wp_ajax_pbay_send_ada', [self::class, 'ajaxSendAda']);
        add_action('wp_ajax_pbay_delete_archived_wallet', [self::class, 'ajaxDeleteArchivedWallet']);
    }

    /**
     * Render the Wallet page
     */
    public static function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $network = get_option('pbay_network', 'preprod');
        $active_wallet = PolicyWalletModel::getActiveWallet($network);
        $archived_wallets = PolicyWalletModel::getArchivedWallets($network);
        $has_blockfrost_key = BlockfrostAPI::hasApiKey($network);

        // Check for one-time mnemonic display
        $show_mnemonic = get_transient('pbay_wallet_mnemonic_' . get_current_user_id());
        if ($show_mnemonic) {
            delete_transient('pbay_wallet_mnemonic_' . get_current_user_id());
        }

        include PBAY_PLUGIN_DIR . 'includes/views/admin/policy-wallet.php';
    }

    /**
     * Handle form submissions
     */
    public static function handleFormSubmissions() {
        if (!isset($_POST['pbay_wallet_action'])) {
            return;
        }

        if ($_POST['pbay_wallet_action'] === 'generate') {
            check_admin_referer('pbay_generate_wallet');
            if (!current_user_can('manage_options')) {
                wp_die('Insufficient permissions');
            }
            self::generateWallet();
            return;
        }

        if ($_POST['pbay_wallet_action'] === 'delete') {
            check_admin_referer('pbay_delete_wallet');
            if (!current_user_can('manage_options')) {
                wp_die('Insufficient permissions');
            }
            self::deleteWallet();
            return;
        }
    }

    /**
     * Generate new policy wallet using pure PHP CardanoWalletPHP
     */
    private static function generateWallet() {
        $wallet_name = sanitize_text_field($_POST['wallet_name'] ?? 'Default Policy Wallet');
        $network = get_option('pbay_network', 'preprod');

        try {
            \Ed25519Compat::init();
            $result = \CardanoWalletPHP::generateWallet($network);
        } catch (\Throwable $e) {
            set_transient('pbay_wallet_error', 'Wallet generation failed: ' . $e->getMessage(), 30);
            wp_redirect(admin_url('admin.php?page=pbay-policy-wallet&error=1'));
            exit;
        }

        if (!isset($result['mnemonic']) || !isset($result['payment_skey_extended'])) {
            set_transient('pbay_wallet_error', 'Invalid wallet data returned.', 30);
            wp_redirect(admin_url('admin.php?page=pbay-policy-wallet&error=1'));
            exit;
        }

        // Encrypt sensitive data
        $mnemonic_encrypted = EncryptionHelper::encrypt($result['mnemonic']);
        $skey_encrypted = EncryptionHelper::encrypt($result['payment_skey_extended']);

        if (empty($mnemonic_encrypted) || empty($skey_encrypted)) {
            set_transient('pbay_wallet_error', 'Failed to encrypt wallet data.', 30);
            wp_redirect(admin_url('admin.php?page=pbay-policy-wallet&error=1'));
            exit;
        }

        $payment_address = $result['addresses']['payment_address'] ?? null;
        $stake_address = $result['addresses']['stake_address'] ?? null;
        $payment_keyhash = $result['payment_keyhash'] ?? null;

        $wallet_id = PolicyWalletModel::insert([
            'wallet_name' => $wallet_name,
            'mnemonic_encrypted' => $mnemonic_encrypted,
            'skey_encrypted' => $skey_encrypted,
            'payment_address' => $payment_address,
            'payment_keyhash' => $payment_keyhash,
            'stake_address' => $stake_address,
            'network' => $network,
        ]);

        if ($wallet_id) {
            // Store mnemonic for ONE-TIME display (5 minutes)
            set_transient('pbay_wallet_mnemonic_' . get_current_user_id(), $result['mnemonic'], 300);
            set_transient('pbay_wallet_success', 'Policy wallet created. KeyHash: ' . $payment_keyhash, 30);
            wp_redirect(admin_url('admin.php?page=pbay-policy-wallet&created=1'));
        } else {
            set_transient('pbay_wallet_error', 'Failed to save wallet to database.', 30);
            wp_redirect(admin_url('admin.php?page=pbay-policy-wallet&error=1'));
        }
        exit;
    }

    /**
     * Delete policy wallet
     */
    private static function deleteWallet() {
        $wallet_id = intval($_POST['wallet_id'] ?? 0);

        if ($wallet_id > 0) {
            PolicyWalletModel::delete($wallet_id);
            set_transient('pbay_wallet_success', 'Wallet deleted.', 30);
        }

        wp_redirect(admin_url('admin.php?page=pbay-policy-wallet'));
        exit;
    }

    public static function ajaxArchiveWallet() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $wallet_id = intval($_POST['wallet_id'] ?? 0);
        $result = PolicyWalletModel::archive($wallet_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Wallet archived']);
    }

    public static function ajaxUnarchiveWallet() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $wallet_id = intval($_POST['wallet_id'] ?? 0);
        $result = PolicyWalletModel::unarchive($wallet_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Wallet restored']);
    }

    /**
     * AJAX: Get wallet balance via Blockfrost
     */
    public static function ajaxGetWalletBalance() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $network = get_option('pbay_network', 'preprod');
        $wallet = PolicyWalletModel::getActiveWallet($network);

        if (!$wallet) {
            wp_send_json_error(['message' => 'No active wallet']);
        }

        if (!BlockfrostAPI::hasApiKey($network)) {
            wp_send_json_error(['message' => 'no_blockfrost_key']);
        }

        $balance = BlockfrostAPI::getAddressBalance($wallet['payment_address'], $network);

        if (is_wp_error($balance)) {
            wp_send_json_error(['message' => $balance->get_error_message()]);
        }

        // Enrich assets with on-chain metadata (images) — cap at 20 to avoid timeout
        if (!empty($balance['assets'])) {
            $limit = min(count($balance['assets']), 20);
            for ($i = 0; $i < $limit; $i++) {
                $info = BlockfrostAPI::getAssetInfo($balance['assets'][$i]['unit'], $network);
                if (!is_wp_error($info)) {
                    $balance['assets'][$i]['image'] = $info['image'];
                    $balance['assets'][$i]['metadata'] = $info['metadata'];
                    $balance['assets'][$i]['fingerprint'] = $info['fingerprint'];
                    $balance['assets'][$i]['mint_quantity'] = $info['mint_quantity'];
                    if (!empty($info['name'])) {
                        $balance['assets'][$i]['asset_name'] = $info['name'];
                    }
                }
            }
        }

        wp_send_json_success($balance);
    }

    /**
     * AJAX: Send ADA from policy wallet
     */
    public static function ajaxSendAda() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $recipient = sanitize_text_field($_POST['recipient_address'] ?? '');
        $ada_amount = floatval($_POST['ada_amount'] ?? 0);

        // Validate recipient address (basic bech32 check)
        if (empty($recipient) || !preg_match('/^addr(_test)?1[a-z0-9]+$/i', $recipient)) {
            wp_send_json_error(['message' => 'Invalid recipient address. Must be a valid Cardano bech32 address.']);
        }

        if ($ada_amount < 1) {
            wp_send_json_error(['message' => 'Minimum send amount is 1 ADA.']);
        }

        $lovelace = intval($ada_amount * 1000000);

        $network = get_option('pbay_network', 'preprod');
        $wallet = PolicyWalletModel::getActiveWallet($network);

        if (!$wallet) {
            wp_send_json_error(['message' => 'No active wallet']);
        }

        $skey_hex = EncryptionHelper::decrypt($wallet['skey_encrypted']);
        if (empty($skey_hex)) {
            wp_send_json_error(['message' => 'Could not decrypt wallet signing key']);
        }

        // Build TX via Anvil
        $build_result = AnvilAPI::buildSimpleTransaction(
            $wallet['payment_address'],
            $recipient,
            $lovelace
        );

        if (is_wp_error($build_result)) {
            $error_msg = $build_result->get_error_message();
            // Friendly error messages
            if (stripos($error_msg, 'insufficient') !== false) {
                $error_msg = 'Insufficient funds. Check your wallet balance.';
            }
            wp_send_json_error(['message' => 'Build failed: ' . $error_msg]);
        }

        $unsigned_tx = $build_result['complete'] ?? null;
        if (!$unsigned_tx) {
            wp_send_json_error(['message' => 'No transaction hex returned from Anvil']);
        }

        // Sign with policy wallet
        $sign_result = \CardanoTransactionSignerPHP::signTransaction($unsigned_tx, $skey_hex);

        if (!$sign_result || !$sign_result['success']) {
            wp_send_json_error(['message' => 'Signing failed: ' . ($sign_result['error'] ?? 'Unknown')]);
        }

        // Submit
        $submit_result = AnvilAPI::submitTransaction($unsigned_tx, [$sign_result['witnessSetHex']]);

        if (is_wp_error($submit_result)) {
            wp_send_json_error(['message' => 'Submit failed: ' . $submit_result->get_error_message()]);
        }

        $tx_hash = $submit_result['txHash'] ?? $submit_result['hash'] ?? '';
        $explorer_url = ($network === 'mainnet')
            ? 'https://cardanoscan.io/transaction/' . $tx_hash
            : 'https://preprod.cardanoscan.io/transaction/' . $tx_hash;

        error_log('[PBay][SEND_ADA] Success: ' . $ada_amount . ' ADA sent. TX: ' . $tx_hash);

        wp_send_json_success([
            'message' => $ada_amount . ' ADA sent successfully!',
            'tx_hash' => $tx_hash,
            'explorer_url' => $explorer_url,
        ]);
    }

    /**
     * AJAX: Delete an archived wallet permanently
     */
    public static function ajaxDeleteArchivedWallet() {
        check_ajax_referer('pbay_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $wallet_id = intval($_POST['wallet_id'] ?? 0);
        $wallet = PolicyWalletModel::getById($wallet_id);

        if (!$wallet) {
            wp_send_json_error(['message' => 'Wallet not found']);
        }

        if (!$wallet['archived']) {
            wp_send_json_error(['message' => 'Cannot delete active wallet via this action. Archive it first.']);
        }

        PolicyWalletModel::delete($wallet_id);
        wp_send_json_success(['message' => 'Wallet deleted permanently']);
    }
}
