<?php if (!defined('ABSPATH')) exit; ?>
<div class="pbay-catalog">
    <?php if (!empty($categories)): ?>
        <div class="pbay-category-filter">
            <a href="?" class="pbay-cat-link <?php echo empty($category) ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?pbay_cat=<?php echo esc_attr(urlencode($cat)); ?>" class="pbay-cat-link <?php echo ($category === $cat) ? 'active' : ''; ?>"><?php echo esc_html($cat); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($listings)): ?>
        <div class="pbay-empty">
            <p>No products available at this time.</p>
        </div>
    <?php else: ?>
        <div class="pbay-product-grid pbay-cols-<?php echo esc_attr($columns); ?>">
            <?php foreach ($listings as $item): ?>
                <div class="pbay-product-card">
                    <div class="pbay-product-image">
                        <?php if (!empty($item['image_id'])): ?>
                            <img src="<?php echo esc_url(wp_get_attachment_image_url($item['image_id'], 'medium')); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                        <?php else: ?>
                            <div class="pbay-no-image">No Image</div>
                        <?php endif; ?>

                        <?php
                        $stock = $item['quantity'] - $item['quantity_sold'];
                        if ($stock <= 3 && $stock > 0): ?>
                            <span class="pbay-badge pbay-badge-low">Only <?php echo $stock; ?> left</span>
                        <?php elseif ($stock <= 0): ?>
                            <span class="pbay-badge pbay-badge-sold">Sold Out</span>
                        <?php endif; ?>
                    </div>

                    <div class="pbay-product-info">
                        <h3 class="pbay-product-title"><?php echo esc_html($item['title']); ?></h3>

                        <?php if (!empty($item['category'])): ?>
                            <span class="pbay-product-category"><?php echo esc_html($item['category']); ?></span>
                        <?php endif; ?>

                        <div class="pbay-product-price">
                            $<?php echo esc_html(number_format($item['price_usd'], 2)); ?>
                        </div>

                        <?php if (!empty($item['condition_type'])): ?>
                            <span class="pbay-product-condition"><?php echo esc_html($item['condition_type']); ?></span>
                        <?php endif; ?>

                        <?php if ($stock > 0): ?>
                            <a href="?pbay_product=<?php echo esc_attr($item['id']); ?>" class="pbay-btn pbay-btn-primary">View Details</a>
                        <?php else: ?>
                            <span class="pbay-btn pbay-btn-disabled">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
