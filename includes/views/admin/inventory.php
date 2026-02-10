<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap pbay-admin">
    <h1>
        PBay - Inventory
        <a href="<?php echo admin_url('admin.php?page=pbay-create-listing'); ?>" class="page-title-action">Create Listing</a>
    </h1>

    <!-- Status Filter Tabs -->
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url('admin.php?page=pbay-inventory'); ?>" <?php echo empty($status_filter) ? 'class="current"' : ''; ?>>All <span class="count">(<?php echo $counts['all']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-inventory&status=draft'); ?>" <?php echo $status_filter === 'draft' ? 'class="current"' : ''; ?>>Draft <span class="count">(<?php echo $counts['draft']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-inventory&status=minting'); ?>" <?php echo $status_filter === 'minting' ? 'class="current"' : ''; ?>>Minting <span class="count">(<?php echo $counts['minting']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-inventory&status=active'); ?>" <?php echo $status_filter === 'active' ? 'class="current"' : ''; ?>>Active <span class="count">(<?php echo $counts['active']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-inventory&status=sold'); ?>" <?php echo $status_filter === 'sold' ? 'class="current"' : ''; ?>>Sold <span class="count">(<?php echo $counts['sold']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-inventory&status=archived'); ?>" <?php echo $status_filter === 'archived' ? 'class="current"' : ''; ?>>Archived <span class="count">(<?php echo $counts['archived']; ?>)</span></a></li>
    </ul>
    <br class="clear" />

    <?php if (empty($listings)): ?>
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-clipboard pbay-card-icon"></span>
                <div>
                    <h2>No Listings Yet</h2>
                    <p class="pbay-card-desc">Create your first listing to start selling.</p>
                </div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=pbay-create-listing'); ?>" class="button button-primary">Create Listing &rarr;</a>
        </div>
    <?php else: ?>
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-clipboard pbay-card-icon"></span>
                <div>
                    <h2>Listings</h2>
                    <p class="pbay-card-desc">
                        <?php echo count($listings); ?> listing<?php echo count($listings) !== 1 ? 's' : ''; ?>
                        <?php echo $status_filter ? '&mdash; filtered by <strong>' . esc_html($status_filter) . '</strong>' : ''; ?>
                    </p>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:60px;">Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Policy ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image_id'])): ?>
                                    <img src="<?php echo esc_url(wp_get_attachment_image_url($item['image_id'], 'thumbnail')); ?>" class="pbay-inventory-thumb" />
                                <?php else: ?>
                                    <div class="pbay-inventory-thumb-placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><a href="<?php echo admin_url('admin.php?page=pbay-create-listing&edit=' . $item['id']); ?>"><?php echo esc_html($item['title']); ?></a></strong>
                            </td>
                            <td>$<?php echo esc_html(number_format($item['price_usd'], 2)); ?></td>
                            <td><?php echo esc_html(($item['quantity'] - $item['quantity_sold']) . '/' . $item['quantity']); ?></td>
                            <td><?php echo esc_html($item['category'] ?? '-'); ?></td>
                            <td>
                                <span class="pbay-status-badge pbay-status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucfirst($item['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($item['policy_id'])): ?>
                                    <code class="pbay-policy-code" title="<?php echo esc_attr($item['policy_id']); ?>"><?php echo esc_html(substr($item['policy_id'], 0, 12) . '...'); ?></code>
                                <?php else: ?>
                                    <span style="color:#c3c4c7;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=pbay-create-listing&edit=' . $item['id']); ?>" class="button button-small">Edit</a>

                                <?php if ($item['status'] === 'draft'): ?>
                                    <button type="button" class="button button-small pbay-mint-btn" data-id="<?php echo esc_attr($item['id']); ?>">Publish</button>
                                <?php endif; ?>

                                <?php if (in_array($item['status'], ['active', 'draft'])): ?>
                                    <button type="button" class="button button-small pbay-archive-btn" data-id="<?php echo esc_attr($item['id']); ?>">Archive</button>
                                <?php endif; ?>

                                <button type="button" class="button button-small pbay-delete-btn" data-id="<?php echo esc_attr($item['id']); ?>" style="color:#d63638;">Delete</button>

                                <?php if (!empty($item['mint_tx_hash'])): ?>
                                    <?php
                                    $network = get_option('pbay_network', 'preprod');
                                    $explorer = ($network === 'mainnet') ? 'https://cardanoscan.io' : 'https://preprod.cardanoscan.io';
                                    ?>
                                    <a href="<?php echo esc_url($explorer . '/transaction/' . $item['mint_tx_hash']); ?>" target="_blank" class="button button-small" title="View on explorer">TX</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
