<?php
namespace PBay\Controllers;

use PBay\Models\ListingModel;

class CatalogController {

    public static function register() {
        add_shortcode('pbay-catalog', [self::class, 'renderCatalog']);
        add_shortcode('pbay-product', [self::class, 'renderProduct']);
        add_shortcode('pbay-orders', [self::class, 'renderOrderHistory']);
    }

    /**
     * [pbay-catalog] shortcode - product grid (or single product via ?pbay_product=X)
     */
    public static function renderCatalog($atts) {
        // If ?pbay_product=X is in the URL, show the product detail page instead
        if (!empty($_GET['pbay_product'])) {
            return self::renderProduct(['id' => intval($_GET['pbay_product'])]);
        }

        $atts = shortcode_atts([
            'category' => '',
            'limit' => 24,
            'columns' => 4,
        ], $atts);

        // Read category from query string (?pbay_cat=X) or shortcode attribute
        $category = !empty($_GET['pbay_cat'])
            ? sanitize_text_field($_GET['pbay_cat'])
            : (!empty($atts['category']) ? sanitize_text_field($atts['category']) : null);

        $listings = ListingModel::getActiveListings($category, intval($atts['limit']));
        $categories = ListingModel::getCategories();
        $columns = intval($atts['columns']);

        ob_start();
        include PBAY_PLUGIN_DIR . 'includes/views/frontend/catalog.php';
        return ob_get_clean();
    }

    /**
     * [pbay-product id="X"] shortcode - single product page
     * Also reads ?pbay_product=X from URL as fallback
     */
    public static function renderProduct($atts) {
        $atts = shortcode_atts(['id' => 0, 'slug' => ''], $atts);

        // Fallback to query param if no shortcode attribute
        $product_id = intval($atts['id']);
        if (!$product_id && !empty($_GET['pbay_product'])) {
            $product_id = intval($_GET['pbay_product']);
        }

        $listing = null;
        if ($product_id) {
            $listing = ListingModel::getById($product_id);
        } elseif (!empty($atts['slug'])) {
            $listing = ListingModel::getBySlug(sanitize_text_field($atts['slug']));
        }

        if (!$listing || $listing['status'] !== 'active') {
            return '<div class="pbay-error">Product not found or no longer available.</div>';
        }

        $meta = ListingModel::getMeta($listing['id']);
        $stock = $listing['quantity'] - $listing['quantity_sold'];

        ob_start();
        include PBAY_PLUGIN_DIR . 'includes/views/frontend/product-detail.php';
        include PBAY_PLUGIN_DIR . 'includes/views/frontend/checkout-modal.php';
        return ob_get_clean();
    }

    /**
     * [pbay-orders] shortcode - buyer order history
     */
    public static function renderOrderHistory($atts) {
        ob_start();
        include PBAY_PLUGIN_DIR . 'includes/views/frontend/order-history.php';
        return ob_get_clean();
    }
}
