<?php
namespace PBay\Models;

class OrderModel {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'pbay_orders';
    }

    /**
     * Create orders table
     */
    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id varchar(20) NOT NULL,
            listing_id bigint(20) unsigned NOT NULL,
            buyer_address varchar(128) NOT NULL,
            buyer_email varchar(255) DEFAULT NULL,
            buyer_name varchar(255) DEFAULT NULL,
            shipping_name varchar(255) DEFAULT NULL,
            shipping_address_1 varchar(255) DEFAULT NULL,
            shipping_address_2 varchar(255) DEFAULT NULL,
            shipping_city varchar(100) DEFAULT NULL,
            shipping_state varchar(100) DEFAULT NULL,
            shipping_postal varchar(20) DEFAULT NULL,
            shipping_country varchar(100) DEFAULT NULL,
            shipping_phone varchar(50) DEFAULT NULL,
            price_usd decimal(10,2) NOT NULL DEFAULT 0.00,
            price_ada decimal(20,6) NOT NULL DEFAULT 0.000000,
            exchange_rate decimal(10,4) DEFAULT NULL,
            tx_hash varchar(128) DEFAULT NULL,
            nft_delivery_tx_hash varchar(128) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            tracking_number varchar(100) DEFAULT NULL,
            tracking_carrier varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL,
            paid_at datetime DEFAULT NULL,
            shipped_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_order_id (order_id),
            INDEX idx_listing_id (listing_id),
            INDEX idx_status (status),
            INDEX idx_buyer_address (buyer_address),
            INDEX idx_tx_hash (tx_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Generate unique order ID: PBAY-XXXX-XXXX
     */
    public static function generateOrderId() {
        $part1 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        $part2 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        return 'PBAY-' . $part1 . '-' . $part2;
    }

    /**
     * Create a new order
     */
    public static function create($data) {
        global $wpdb;
        $table = self::table_name();

        $order_id = self::generateOrderId();

        $wpdb->insert($table, [
            'order_id' => $order_id,
            'listing_id' => intval($data['listing_id']),
            'buyer_address' => $data['buyer_address'],
            'buyer_email' => $data['buyer_email'] ?? null,
            'buyer_name' => $data['buyer_name'] ?? null,
            'shipping_name' => $data['shipping_name'] ?? null,
            'shipping_address_1' => $data['shipping_address_1'] ?? null,
            'shipping_address_2' => $data['shipping_address_2'] ?? null,
            'shipping_city' => $data['shipping_city'] ?? null,
            'shipping_state' => $data['shipping_state'] ?? null,
            'shipping_postal' => $data['shipping_postal'] ?? null,
            'shipping_country' => $data['shipping_country'] ?? null,
            'shipping_phone' => $data['shipping_phone'] ?? null,
            'price_usd' => floatval($data['price_usd'] ?? 0),
            'price_ada' => floatval($data['price_ada'] ?? 0),
            'exchange_rate' => !empty($data['exchange_rate']) ? floatval($data['exchange_rate']) : null,
            'tx_hash' => $data['tx_hash'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'created_at' => current_time('mysql'),
            'paid_at' => ($data['status'] ?? '') === 'paid' ? current_time('mysql') : null,
        ]);

        if ($wpdb->insert_id) {
            return ['id' => $wpdb->insert_id, 'order_id' => $order_id];
        }
        return false;
    }

    /**
     * Get order by internal ID
     */
    public static function getById($id) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)), ARRAY_A);
    }

    /**
     * Get order by order_id string
     */
    public static function getByOrderId($order_id) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE order_id = %s", $order_id), ARRAY_A);
    }

    /**
     * Get order by TX hash
     */
    public static function getByTxHash($tx_hash) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE tx_hash = %s", $tx_hash), ARRAY_A);
    }

    /**
     * Get all orders with optional status filter
     */
    public static function getAll($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        $table = self::table_name();

        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT o.*, l.title as listing_title FROM $table o LEFT JOIN {$wpdb->prefix}pbay_listings l ON o.listing_id = l.id WHERE o.status = %s ORDER BY o.id DESC LIMIT %d OFFSET %d",
                $status, $limit, $offset
            ), ARRAY_A);
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, l.title as listing_title FROM $table o LEFT JOIN {$wpdb->prefix}pbay_listings l ON o.listing_id = l.id ORDER BY o.id DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ), ARRAY_A);
    }

    /**
     * Get orders by buyer address
     */
    public static function getByBuyer($buyer_address, $limit = 50) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, l.title as listing_title, l.policy_id as listing_policy_id, l.asset_name as listing_asset_name, l.image_id as listing_image_id FROM $table o LEFT JOIN {$wpdb->prefix}pbay_listings l ON o.listing_id = l.id WHERE o.buyer_address = %s ORDER BY o.id DESC LIMIT %d",
            $buyer_address, $limit
        ), ARRAY_A);
    }

    /**
     * Update order status
     */
    public static function updateStatus($id, $status) {
        global $wpdb;
        $table = self::table_name();

        $update = ['status' => $status];

        switch ($status) {
            case 'paid':
                $update['paid_at'] = current_time('mysql');
                break;
            case 'shipped':
                $update['shipped_at'] = current_time('mysql');
                break;
            case 'completed':
                $update['completed_at'] = current_time('mysql');
                break;
        }

        return $wpdb->update($table, $update, ['id' => intval($id)]);
    }

    /**
     * Update NFT delivery TX hash
     */
    public static function updateNftDelivery($id, $tx_hash) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->update($table, [
            'nft_delivery_tx_hash' => $tx_hash,
        ], ['id' => intval($id)]);
    }

    /**
     * Update tracking info
     */
    public static function updateTracking($id, $tracking_number, $tracking_carrier) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->update($table, [
            'tracking_number' => $tracking_number,
            'tracking_carrier' => $tracking_carrier,
            'status' => 'shipped',
            'shipped_at' => current_time('mysql'),
        ], ['id' => intval($id)]);
    }

    /**
     * Count orders by status
     */
    public static function countByStatus($status = null) {
        global $wpdb;
        $table = self::table_name();

        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", $status));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Get order stats
     */
    public static function getStats() {
        global $wpdb;
        $table = self::table_name();

        return [
            'total' => self::countByStatus(),
            'pending' => self::countByStatus('pending'),
            'paid' => self::countByStatus('paid'),
            'processing' => self::countByStatus('processing'),
            'shipped' => self::countByStatus('shipped'),
            'completed' => self::countByStatus('completed'),
            'total_revenue_usd' => floatval($wpdb->get_var("SELECT COALESCE(SUM(price_usd), 0) FROM $table WHERE status IN ('paid','processing','shipped','completed')")),
            'total_revenue_ada' => floatval($wpdb->get_var("SELECT COALESCE(SUM(price_ada), 0) FROM $table WHERE status IN ('paid','processing','shipped','completed')")),
        ];
    }
}
