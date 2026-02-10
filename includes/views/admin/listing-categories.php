<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap pbay-admin">
    <h1>PBay - Listing Categories</h1>

    <div id="pbay-category-messages"></div>

    <!-- Create Category Form -->
    <div class="pbay-card">
        <div class="pbay-card-header">
            <span class="dashicons dashicons-category pbay-card-icon"></span>
            <div>
                <h2>Create Category</h2>
                <p class="pbay-card-desc">Group similar products together so buyers can find what they're looking for.</p>
            </div>
        </div>

        <?php if (!$active_wallet): ?>
            <div class="pbay-callout pbay-callout-warning">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong>No active policy wallet</strong>
                    <p>A policy wallet is required before adding categories.</p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbay-policy-wallet')); ?>" class="button button-secondary">Create Policy Wallet &rarr;</a>
                </div>
            </div>
        <?php else: ?>
            <p class="description" style="margin-bottom:16px;">Examples: <em>Vintage Electronics, Pokemon Cards 1st Gen, Handmade Jewelry, 70s Antiques</em></p>

            <div class="pbay-field-group">
                <div class="pbay-field-row">
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-cat-name">Category Name <span style="color:#d63638;">*</span></label>
                        <input type="text" id="pbay-cat-name" class="regular-text" placeholder="e.g., Vintage Electronics" required />
                    </div>
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-cat-description">Description</label>
                        <textarea id="pbay-cat-description" rows="2" class="large-text" placeholder="Optional description for this category"></textarea>
                    </div>
                </div>
                <div class="pbay-field-actions">
                    <button type="button" id="pbay-create-category" class="button button-primary">Create Category</button>
                    <span id="pbay-cat-spinner" style="display:none;"> Setting up category...</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Categories Table -->
    <div class="pbay-card">
        <div class="pbay-card-header">
            <span class="dashicons dashicons-list-view pbay-card-icon"></span>
            <div>
                <h2>Categories</h2>
                <p class="pbay-card-desc">
                    <?php echo esc_html(ucfirst($network)); ?> network
                    &mdash; <?php echo count($categories); ?> categor<?php echo count($categories) === 1 ? 'y' : 'ies'; ?>
                </p>
            </div>
        </div>

        <?php if (empty($categories)): ?>
            <p style="color:#666;">No categories created yet. Create your first category above to get started.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:20%;">Name</th>
                        <th style="width:25%;">Description</th>
                        <th style="width:10%; text-align:center;">Listings</th>
                        <th style="width:15%;">Created</th>
                        <th style="width:15%;">Expires</th>
                        <th style="width:8%; text-align:center;">Status</th>
                        <th style="width:7%; text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat):
                        $is_expired = !empty($cat['expiration_date']) && strtotime($cat['expiration_date']) < time();
                        $has_listings = (int) $cat['listing_count'] > 0;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($cat['name']); ?></strong></td>
                            <td><?php echo esc_html($cat['description'] ?: '-'); ?></td>
                            <td style="text-align:center;"><?php echo intval($cat['listing_count']); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($cat['created_at']))); ?></td>
                            <td>
                                <?php if (!empty($cat['expiration_date'])): ?>
                                    <?php echo esc_html(date('M j, Y', strtotime($cat['expiration_date']))); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($is_expired): ?>
                                    <span class="pbay-status-badge pbay-status-expired">Expired</span>
                                <?php else: ?>
                                    <span class="pbay-status-badge pbay-status-active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($has_listings): ?>
                                    <button class="button button-small" disabled title="Cannot delete: <?php echo intval($cat['listing_count']); ?> listing(s) use this category">Delete</button>
                                <?php else: ?>
                                    <button class="button button-small pbay-delete-category" data-id="<?php echo intval($cat['id']); ?>" data-name="<?php echo esc_attr($cat['name']); ?>">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
