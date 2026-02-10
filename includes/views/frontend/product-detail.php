<?php if (!defined('ABSPATH')) exit; ?>
<div class="pbay-product-detail" data-listing-id="<?php echo esc_attr($listing['id']); ?>">
    <a href="?" class="pbay-back-link">&larr; Back to catalog</a>

    <div class="pbay-product-layout">
        <!-- Image -->
        <div class="pbay-product-image-large">
            <?php if (!empty($listing['image_id'])): ?>
                <img id="pbay-main-image" src="<?php echo esc_url(wp_get_attachment_image_url($listing['image_id'], 'large')); ?>" alt="<?php echo esc_attr($listing['title']); ?>" />
            <?php else: ?>
                <div class="pbay-no-image">No Image</div>
            <?php endif; ?>

            <?php if (!empty($listing['mint_tx_hash'])): ?>
                <?php
                $network = get_option('pbay_network', 'preprod');
                $explorer = ($network === 'mainnet') ? 'https://cardanoscan.io' : 'https://preprod.cardanoscan.io';
                ?>
                <div class="pbay-nft-badge">
                    NFT Verified
                    <a href="<?php echo esc_url($explorer . '/transaction/' . $listing['mint_tx_hash']); ?>" target="_blank">View on Chain &rarr;</a>
                </div>
            <?php endif; ?>

            <?php
            $all_images = [];
            if (!empty($listing['image_id'])) {
                $all_images[] = intval($listing['image_id']);
            }
            $gallery_ids = array_filter(explode(',', $listing['gallery_ids'] ?? ''));
            foreach ($gallery_ids as $gid) {
                $gid = intval($gid);
                if ($gid > 0) $all_images[] = $gid;
            }
            ?>
            <?php if (count($all_images) > 1): ?>
                <div class="pbay-gallery-thumbs">
                    <?php foreach ($all_images as $idx => $img_id): ?>
                        <?php $thumb_url = wp_get_attachment_image_url($img_id, 'thumbnail'); ?>
                        <?php $large_url = wp_get_attachment_image_url($img_id, 'large'); ?>
                        <?php if ($thumb_url): ?>
                            <img src="<?php echo esc_url($thumb_url); ?>"
                                 data-large="<?php echo esc_url($large_url); ?>"
                                 class="pbay-gallery-thumb <?php echo $idx === 0 ? 'active' : ''; ?>"
                                 alt="Image <?php echo $idx + 1; ?>" />
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="pbay-product-info-detail">
            <h1 class="pbay-product-title"><?php echo esc_html($listing['title']); ?></h1>

            <div class="pbay-product-price-large">
                $<?php echo esc_html(number_format($listing['price_usd'], 2)); ?> USD
                <span class="pbay-ada-price" id="pbay-product-ada-price"></span>
            </div>

            <!-- Product specs card -->
            <div class="pbay-detail-card">
                <?php if (!empty($listing['category'])): ?>
                    <div class="pbay-detail-row">
                        <span class="pbay-detail-label">Category:</span>
                        <span><?php echo esc_html($listing['category']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($listing['condition_type'])): ?>
                    <div class="pbay-detail-row">
                        <span class="pbay-detail-label">Condition:</span>
                        <span><?php echo esc_html($listing['condition_type']); ?></span>
                    </div>
                <?php endif; ?>

                <div class="pbay-detail-row">
                    <span class="pbay-detail-label">Available:</span>
                    <span><?php echo esc_html($stock); ?> of <?php echo esc_html($listing['quantity']); ?></span>
                </div>

                <?php if (!empty($listing['ships_from'])): ?>
                    <div class="pbay-detail-row">
                        <span class="pbay-detail-label">Ships from:</span>
                        <span><?php echo esc_html($listing['ships_from']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Custom Attributes -->
                <?php if (!empty($meta)): ?>
                    <?php foreach ($meta as $m): ?>
                        <div class="pbay-detail-row">
                            <span class="pbay-detail-label"><?php echo esc_html($m['meta_key']); ?>:</span>
                            <span><?php echo esc_html($m['meta_value']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($listing['description'])): ?>
                <div class="pbay-description">
                    <h3>Description</h3>
                    <div><?php echo wp_kses_post(nl2br($listing['description'])); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($listing['shipping_notes']) || !empty($listing['weight_lbs']) || !empty($listing['dimensions'])): ?>
                <div class="pbay-shipping-notes">
                    <h3>Shipping Info</h3>
                    <?php if (!empty($listing['shipping_notes'])): ?>
                        <p><?php echo esc_html($listing['shipping_notes']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($listing['weight_lbs'])): ?>
                        <p>Weight: <?php echo esc_html($listing['weight_lbs']); ?> lbs</p>
                    <?php endif; ?>
                    <?php if (!empty($listing['dimensions'])): ?>
                        <p>Dimensions: <?php echo esc_html($listing['dimensions']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Buy Button -->
            <?php if ($stock > 0): ?>
                <button type="button" class="pbay-btn pbay-btn-primary pbay-btn-large" id="pbay-buy-now" data-listing-id="<?php echo esc_attr($listing['id']); ?>" data-price-usd="<?php echo esc_attr($listing['price_usd']); ?>">
                    Buy Now &mdash; $<?php echo esc_html(number_format($listing['price_usd'], 2)); ?>
                </button>
            <?php else: ?>
                <span class="pbay-btn pbay-btn-disabled pbay-btn-large">Sold Out</span>
            <?php endif; ?>
        </div>
    </div>
</div>
