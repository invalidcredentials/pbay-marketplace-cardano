<?php
namespace PBay\Models;

class ListingCategoryModel {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'pbay_listing_categories';
    }

    /**
     * Create the listing categories table
     */
    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            policy_id varchar(64) DEFAULT NULL,
            policy_json longtext,
            policy_keyhash varchar(64) DEFAULT NULL,
            wallet_id int(10) unsigned DEFAULT NULL,
            expiration_date datetime DEFAULT NULL,
            network varchar(20) NOT NULL DEFAULT 'preprod',
            status varchar(20) NOT NULL DEFAULT 'active',
            listing_count int(10) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_slug (slug),
            INDEX idx_network (network),
            INDEX idx_status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert a new category
     */
    public static function insert($data) {
        global $wpdb;
        $table = self::table_name();

        $slug = sanitize_title($data['name'] ?? 'untitled');

        // Ensure unique slug
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug));
        if ($existing > 0) {
            $slug .= '-' . time();
        }

        $wpdb->insert($table, [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'policy_id' => $data['policy_id'] ?? null,
            'policy_json' => $data['policy_json'] ?? null,
            'policy_keyhash' => $data['policy_keyhash'] ?? null,
            'wallet_id' => !empty($data['wallet_id']) ? intval($data['wallet_id']) : null,
            'expiration_date' => $data['expiration_date'] ?? null,
            'network' => $data['network'] ?? 'preprod',
            'status' => $data['status'] ?? 'active',
            'listing_count' => 0,
            'created_at' => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Update a category
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = self::table_name();

        $update = [];
        $allowed = [
            'name', 'slug', 'description', 'policy_id', 'policy_json',
            'policy_keyhash', 'wallet_id', 'expiration_date', 'network',
            'status', 'listing_count',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (empty($update)) {
            return false;
        }

        return $wpdb->update($table, $update, ['id' => intval($id)]);
    }

    /**
     * Get category by ID
     */
    public static function getById($id) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)), ARRAY_A);
    }

    /**
     * Get all categories
     */
    public static function getAll($network = null) {
        global $wpdb;
        $table = self::table_name();

        if ($network) {
            return $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table WHERE network = %s ORDER BY name ASC", $network),
                ARRAY_A
            );
        }

        return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC", ARRAY_A);
    }

    /**
     * Get active categories with a valid policy for a network
     */
    public static function getActiveWithPolicy($network = 'preprod') {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE network = %s AND status = 'active' AND policy_id IS NOT NULL ORDER BY name ASC",
                $network
            ),
            ARRAY_A
        );
    }

    /**
     * Delete a category (only if no listings reference it)
     */
    public static function delete($id) {
        global $wpdb;
        $table = self::table_name();

        $category = self::getById($id);
        if (!$category) {
            return new \WP_Error('not_found', 'Category not found.');
        }

        if ((int) $category['listing_count'] > 0) {
            return new \WP_Error('has_listings', 'Cannot delete category with existing listings. Remove or reassign listings first.');
        }

        return $wpdb->delete($table, ['id' => intval($id)]);
    }

    /**
     * Increment listing count
     */
    public static function incrementListingCount($id) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET listing_count = listing_count + 1 WHERE id = %d",
            intval($id)
        ));
    }

    /**
     * Decrement listing count
     */
    public static function decrementListingCount($id) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET listing_count = GREATEST(listing_count - 1, 0) WHERE id = %d",
            intval($id)
        ));
    }

    /**
     * Check if a category name already exists for a network
     */
    public static function nameExists($name, $network, $exclude_id = 0) {
        global $wpdb;
        $table = self::table_name();

        if ($exclude_id > 0) {
            return (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE name = %s AND network = %s AND id != %d",
                $name, $network, $exclude_id
            ));
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE name = %s AND network = %s",
            $name, $network
        ));
    }
}
