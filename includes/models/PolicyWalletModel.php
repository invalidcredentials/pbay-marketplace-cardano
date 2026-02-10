<?php
namespace PBay\Models;

class PolicyWalletModel {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'pbay_policy_wallets';
    }

    /**
     * Create the policy wallets table
     */
    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            wallet_name varchar(255) NOT NULL DEFAULT 'Default Policy Wallet',
            mnemonic_encrypted TEXT NOT NULL,
            skey_encrypted TEXT NOT NULL,
            payment_address varchar(128) NOT NULL,
            payment_keyhash varchar(64) NOT NULL,
            stake_address varchar(128),
            network varchar(20) NOT NULL DEFAULT 'preprod',
            archived tinyint(1) NOT NULL DEFAULT 0,
            archived_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_network (network),
            INDEX idx_archived (archived)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get active (non-archived) policy wallet for network
     */
    public static function getActiveWallet($network = 'preprod') {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE network = %s AND archived = 0 ORDER BY id DESC LIMIT 1",
                $network
            ),
            ARRAY_A
        );
    }

    /**
     * Get wallet by ID
     */
    public static function getById($id) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)),
            ARRAY_A
        );
    }

    /**
     * Insert new policy wallet
     */
    public static function insert($walletData) {
        global $wpdb;
        $table = self::table_name();

        $wpdb->insert($table, [
            'wallet_name' => $walletData['wallet_name'] ?? 'Default Policy Wallet',
            'mnemonic_encrypted' => $walletData['mnemonic_encrypted'],
            'skey_encrypted' => $walletData['skey_encrypted'],
            'payment_address' => $walletData['payment_address'],
            'payment_keyhash' => $walletData['payment_keyhash'],
            'stake_address' => $walletData['stake_address'] ?? '',
            'network' => $walletData['network'],
            'created_at' => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Delete policy wallet
     */
    public static function delete($id) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->delete($table, ['id' => intval($id)]);
    }

    /**
     * Archive a policy wallet
     */
    public static function archive($wallet_id) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->update(
            $table,
            ['archived' => 1, 'archived_at' => current_time('mysql')],
            ['id' => intval($wallet_id)],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Unarchive a policy wallet
     */
    public static function unarchive($wallet_id) {
        global $wpdb;
        $table = self::table_name();

        $wallet = self::getById($wallet_id);
        if (!$wallet) {
            return new \WP_Error('wallet_not_found', 'Wallet not found.');
        }

        // Check if another wallet is active for this network
        $active = self::getActiveWallet($wallet['network']);
        if ($active && $active['id'] != $wallet_id) {
            return new \WP_Error('active_wallet_exists', 'Another wallet is already active for ' . $wallet['network'] . '. Archive it first.');
        }

        return $wpdb->update(
            $table,
            ['archived' => 0, 'archived_at' => null],
            ['id' => intval($wallet_id)],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Get all archived wallets
     */
    public static function getArchivedWallets($network = null) {
        global $wpdb;
        $table = self::table_name();

        if ($network) {
            return $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table WHERE archived = 1 AND network = %s ORDER BY archived_at DESC", $network),
                ARRAY_A
            );
        }

        return $wpdb->get_results("SELECT * FROM $table WHERE archived = 1 ORDER BY archived_at DESC", ARRAY_A);
    }
}
