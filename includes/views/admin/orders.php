<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap pbay-admin">
    <h1>PBay - Orders</h1>

    <!-- Stats Row -->
    <div class="pbay-stats-row">
        <div class="pbay-stat-card">
            <div class="pbay-stat-number"><?php echo $stats['total']; ?></div>
            <div class="pbay-stat-label">Total Orders</div>
        </div>
        <div class="pbay-stat-card">
            <div class="pbay-stat-number"><?php echo $stats['paid'] + $stats['processing']; ?></div>
            <div class="pbay-stat-label">Needs Action</div>
        </div>
        <div class="pbay-stat-card">
            <div class="pbay-stat-number">$<?php echo number_format($stats['total_revenue_usd'], 2); ?></div>
            <div class="pbay-stat-label">Revenue (USD)</div>
        </div>
        <div class="pbay-stat-card">
            <div class="pbay-stat-number"><?php echo number_format($stats['total_revenue_ada'], 2); ?></div>
            <div class="pbay-stat-label">Revenue (ADA)</div>
        </div>
    </div>

    <!-- Status Filter -->
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url('admin.php?page=pbay-orders'); ?>" <?php echo empty($status_filter) ? 'class="current"' : ''; ?>>All <span class="count">(<?php echo $stats['total']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-orders&status=pending'); ?>" <?php echo $status_filter === 'pending' ? 'class="current"' : ''; ?>>Pending <span class="count">(<?php echo $stats['pending']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-orders&status=paid'); ?>" <?php echo $status_filter === 'paid' ? 'class="current"' : ''; ?>>Paid <span class="count">(<?php echo $stats['paid']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-orders&status=shipped'); ?>" <?php echo $status_filter === 'shipped' ? 'class="current"' : ''; ?>>Shipped <span class="count">(<?php echo $stats['shipped']; ?>)</span></a> | </li>
        <li><a href="<?php echo admin_url('admin.php?page=pbay-orders&status=completed'); ?>" <?php echo $status_filter === 'completed' ? 'class="current"' : ''; ?>>Completed <span class="count">(<?php echo $stats['completed']; ?>)</span></a></li>
    </ul>
    <br class="clear" />

    <?php if (empty($orders)): ?>
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-cart pbay-card-icon"></span>
                <div>
                    <h2>No Orders Yet</h2>
                    <p class="pbay-card-desc">Orders will appear here once buyers complete checkout.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-cart pbay-card-icon"></span>
                <div>
                    <h2>Orders</h2>
                    <p class="pbay-card-desc">
                        <?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?>
                        <?php echo $status_filter ? '&mdash; filtered by <strong>' . esc_html($status_filter) . '</strong>' : ''; ?>
                    </p>
                </div>
            </div>

            <div class="pbay-table-actions">
                <button type="button" id="pbay-export-csv" class="button">Export CSV</button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>TX</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><a href="<?php echo admin_url('admin.php?page=pbay-orders&order_id=' . $order['id']); ?>"><?php echo esc_html($order['order_id']); ?></a></strong></td>
                            <td><?php echo esc_html($order['listing_title'] ?? 'N/A'); ?></td>
                            <td><code class="pbay-policy-code"><?php echo esc_html(substr($order['buyer_address'], 0, 20) . '...'); ?></code></td>
                            <td>
                                $<?php echo esc_html(number_format($order['price_usd'], 2)); ?>
                                <?php $o_ship = floatval($order['shipping_rate'] ?? 0); if ($o_ship > 0): ?>
                                    <br/><small style="color:#666;">(incl. $<?php echo esc_html(number_format($o_ship, 2)); ?> shipping)</small>
                                <?php endif; ?>
                                <br/><small><?php echo esc_html(number_format($order['price_ada'], 2)); ?> ADA</small>
                            </td>
                            <td>
                                <span class="pbay-status-badge pbay-status-<?php echo esc_attr($order['status']); ?>">
                                    <?php echo esc_html(ucfirst($order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($order['tx_hash'])): ?>
                                    <?php
                                    $network = get_option('pbay_network', 'preprod');
                                    $explorer = ($network === 'mainnet') ? 'https://cardanoscan.io' : 'https://preprod.cardanoscan.io';
                                    ?>
                                    <a href="<?php echo esc_url($explorer . '/transaction/' . $order['tx_hash']); ?>" target="_blank" title="<?php echo esc_attr($order['tx_hash']); ?>">View</a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($order['created_at']))); ?></td>
                            <td><a href="<?php echo admin_url('admin.php?page=pbay-orders&order_id=' . $order['id']); ?>" class="button button-small">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
