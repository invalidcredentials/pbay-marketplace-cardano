<?php
namespace PBay\Models;

class ListingModel {

    public static function listings_table() {
        global $wpdb;
        return $wpdb->prefix . 'pbay_listings';
    }

    public static function meta_table() {
        global $wpdb;
        return $wpdb->prefix . 'pbay_listing_meta';
    }

    /**
     * Create listings + listing_meta tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $listings = self::listings_table();
        $meta = self::meta_table();

        $sql_listings = "CREATE TABLE IF NOT EXISTS $listings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext,
            policy_id varchar(64) DEFAULT NULL,
            asset_name varchar(255) DEFAULT NULL,
            policy_json longtext,
            nft_metadata longtext,
            mint_tx_hash varchar(128) DEFAULT NULL,
            image_id bigint(20) unsigned DEFAULT NULL,
            ipfs_cid varchar(255) DEFAULT NULL,
            ipfs_cid_manual varchar(255) DEFAULT NULL,
            gallery_ids text,
            gallery_ipfs_cids text,
            price_usd decimal(10,2) NOT NULL DEFAULT 0.00,
            price_ada decimal(20,6) DEFAULT NULL,
            category_id bigint(20) unsigned DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            condition_type varchar(50) DEFAULT NULL,
            quantity int(11) unsigned NOT NULL DEFAULT 1,
            quantity_sold int(11) unsigned NOT NULL DEFAULT 0,
            weight_lbs decimal(8,2) DEFAULT NULL,
            dimensions varchar(100) DEFAULT NULL,
            ships_from varchar(100) DEFAULT NULL,
            shipping_notes text,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            published_at datetime DEFAULT NULL,
            sold_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_slug (slug),
            INDEX idx_category_id (category_id),
            INDEX idx_category (category),
            INDEX idx_policy_id (policy_id)
        ) $charset_collate;";

        $sql_meta = "CREATE TABLE IF NOT EXISTS $meta (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) unsigned NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY (meta_id),
            INDEX idx_listing_id (listing_id),
            INDEX idx_meta_key (meta_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_listings);
        dbDelta($sql_meta);
    }

    // ========================================
    // CRUD Operations
    // ========================================

    /**
     * Insert a new listing
     */
    public static function insert($data) {
        global $wpdb;
        $table = self::listings_table();

        $now = current_time('mysql');
        $slug = sanitize_title($data['title'] ?? 'untitled');

        // Ensure unique slug
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug));
        if ($existing > 0) {
            $slug .= '-' . time();
        }

        $wpdb->insert($table, [
            'title' => $data['title'] ?? '',
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'policy_id' => $data['policy_id'] ?? null,
            'asset_name' => $data['asset_name'] ?? null,
            'policy_json' => $data['policy_json'] ?? null,
            'nft_metadata' => $data['nft_metadata'] ?? null,
            'mint_tx_hash' => $data['mint_tx_hash'] ?? null,
            'image_id' => !empty($data['image_id']) ? intval($data['image_id']) : null,
            'ipfs_cid' => $data['ipfs_cid'] ?? null,
            'ipfs_cid_manual' => $data['ipfs_cid_manual'] ?? null,
            'gallery_ids' => $data['gallery_ids'] ?? null,
            'price_usd' => floatval($data['price_usd'] ?? 0),
            'price_ada' => !empty($data['price_ada']) ? floatval($data['price_ada']) : null,
            'category_id' => !empty($data['category_id']) ? intval($data['category_id']) : null,
            'category' => $data['category'] ?? null,
            'condition_type' => $data['condition_type'] ?? null,
            'quantity' => intval($data['quantity'] ?? 1),
            'quantity_sold' => 0,
            'weight_lbs' => !empty($data['weight_lbs']) ? floatval($data['weight_lbs']) : null,
            'dimensions' => $data['dimensions'] ?? null,
            'ships_from' => $data['ships_from'] ?? null,
            'shipping_notes' => $data['shipping_notes'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Update a listing
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = self::listings_table();

        $update = [];
        $allowed = [
            'title', 'description', 'policy_id', 'asset_name', 'policy_json',
            'nft_metadata', 'mint_tx_hash', 'image_id', 'ipfs_cid', 'ipfs_cid_manual',
            'gallery_ids', 'price_usd', 'price_ada', 'category_id', 'category',
            'condition_type', 'quantity', 'quantity_sold', 'weight_lbs', 'dimensions',
            'ships_from', 'shipping_notes', 'status', 'published_at', 'sold_at',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (!empty($data['title']) && !isset($data['slug'])) {
            $update['slug'] = sanitize_title($data['title']);
        }

        $update['updated_at'] = current_time('mysql');

        return $wpdb->update($table, $update, ['id' => intval($id)]);
    }

    /**
     * Get listing by ID
     */
    public static function getById($id) {
        global $wpdb;
        $table = self::listings_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)), ARRAY_A);
    }

    /**
     * Get listing by slug
     */
    public static function getBySlug($slug) {
        global $wpdb;
        $table = self::listings_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug), ARRAY_A);
    }

    /**
     * Get all listings (optionally filtered by status)
     */
    public static function getAll($status = null) {
        global $wpdb;
        $table = self::listings_table();

        if ($status) {
            return $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table WHERE status = %s ORDER BY id DESC", $status),
                ARRAY_A
            );
        }

        return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
    }

    /**
     * Get active listings for the catalog
     */
    public static function getActiveListings($category = null, $limit = 50, $offset = 0) {
        global $wpdb;
        $table = self::listings_table();

        if ($category) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE status = 'active' AND category = %s ORDER BY published_at DESC LIMIT %d OFFSET %d",
                $category, $limit, $offset
            ), ARRAY_A);
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'active' ORDER BY published_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ), ARRAY_A);
    }

    /**
     * Delete a listing and its meta
     */
    public static function delete($id) {
        global $wpdb;
        $wpdb->delete(self::meta_table(), ['listing_id' => intval($id)]);
        return $wpdb->delete(self::listings_table(), ['id' => intval($id)]);
    }

    /**
     * Increment sold quantity
     */
    public static function incrementSold($id) {
        global $wpdb;
        $table = self::listings_table();
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET quantity_sold = quantity_sold + 1, updated_at = %s WHERE id = %d",
            current_time('mysql'), intval($id)
        ));
    }

    /**
     * Check if listing has available stock
     */
    public static function hasStock($id) {
        $listing = self::getById($id);
        if (!$listing) return false;
        return ($listing['quantity'] - $listing['quantity_sold']) > 0;
    }

    // ========================================
    // Meta Operations
    // ========================================

    /**
     * Get all meta for a listing
     */
    public static function getMeta($listing_id) {
        global $wpdb;
        $table = self::meta_table();
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE listing_id = %d ORDER BY meta_id ASC", intval($listing_id)),
            ARRAY_A
        );
    }

    /**
     * Set a meta value (insert or update)
     */
    public static function setMeta($listing_id, $key, $value) {
        global $wpdb;
        $table = self::meta_table();

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_id FROM $table WHERE listing_id = %d AND meta_key = %s",
            intval($listing_id), $key
        ));

        if ($existing) {
            return $wpdb->update($table, ['meta_value' => $value], ['meta_id' => $existing]);
        }

        $wpdb->insert($table, [
            'listing_id' => intval($listing_id),
            'meta_key' => $key,
            'meta_value' => $value,
        ]);
        return $wpdb->insert_id;
    }

    /**
     * Delete all meta for a listing
     */
    public static function deleteAllMeta($listing_id) {
        global $wpdb;
        $table = self::meta_table();
        return $wpdb->delete($table, ['listing_id' => intval($listing_id)]);
    }

    /**
     * Replace all meta for a listing (delete + bulk insert)
     */
    public static function replaceMeta($listing_id, $meta_array) {
        self::deleteAllMeta($listing_id);
        foreach ($meta_array as $row) {
            if (!empty($row['key']) && $row['value'] !== '' && $row['value'] !== null) {
                self::setMeta($listing_id, $row['key'], $row['value']);
            }
        }
    }

    /**
     * Count listings by status
     */
    public static function countByStatus($status) {
        global $wpdb;
        $table = self::listings_table();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", $status));
    }

    /**
     * Get distinct categories from active listings
     */
    public static function getCategories() {
        global $wpdb;
        $table = self::listings_table();
        return $wpdb->get_col("SELECT DISTINCT category FROM $table WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    }
}
