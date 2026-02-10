<?php if (!defined('ABSPATH')) exit;
$network = get_option('pbay_network', 'preprod');
$explorer = ($network === 'mainnet') ? 'https://cardanoscan.io' : 'https://preprod.cardanoscan.io';
?>
<div class="wrap pbay-admin">
    <h1>
        Order <?php echo esc_html($order['order_id']); ?>
        <a href="<?php echo admin_url('admin.php?page=pbay-orders'); ?>" class="page-title-action">Back to Orders</a>
    </h1>

    <div class="pbay-order-detail-grid">
        <!-- Order Info -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-clipboard pbay-card-icon"></span>
                <div>
                    <h2>Order Information</h2>
                    <p class="pbay-card-desc">
                        <span class="pbay-status-badge pbay-status-<?php echo esc_attr($order['status']); ?>">
                            <?php echo esc_html(ucfirst($order['status'])); ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="pbay-info-row">
                <span class="pbay-info-label">Order ID</span>
                <span class="pbay-info-value"><code><?php echo esc_html($order['order_id']); ?></code></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Status</span>
                <span class="pbay-info-value">
                    <select id="pbay-order-status" data-order-id="<?php echo esc_attr($order['id']); ?>">
                        <?php foreach (['pending','paid','processing','shipped','completed','disputed','refunded'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php selected($order['status'], $s); ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="pbay-update-status" class="button button-small">Update</button>
                </span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Product</span>
                <span class="pbay-info-value"><?php echo esc_html($listing ? $listing['title'] : 'N/A'); ?></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Price</span>
                <span class="pbay-info-value">$<?php echo esc_html(number_format($order['price_usd'], 2)); ?> <small style="color:#666;">(<?php echo esc_html(number_format($order['price_ada'], 6)); ?> ADA)</small></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Exchange Rate</span>
                <span class="pbay-info-value">$<?php echo esc_html(number_format($order['exchange_rate'] ?? 0, 4)); ?>/ADA</span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Created</span>
                <span class="pbay-info-value"><?php echo esc_html($order['created_at']); ?></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Paid</span>
                <span class="pbay-info-value"><?php echo esc_html($order['paid_at'] ?? '-'); ?></span>
            </div>
            <?php if (!empty($order['tx_hash'])): ?>
                <div class="pbay-info-row">
                    <span class="pbay-info-label">TX Hash</span>
                    <span class="pbay-info-value">
                        <a href="<?php echo esc_url($explorer . '/transaction/' . $order['tx_hash']); ?>" target="_blank">
                            <code><?php echo esc_html($order['tx_hash']); ?></code>
                        </a>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Buyer Info -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-admin-users pbay-card-icon"></span>
                <div>
                    <h2>Buyer Information</h2>
                    <p class="pbay-card-desc"><?php echo esc_html($order['buyer_name'] ?? 'Anonymous buyer'); ?></p>
                </div>
            </div>

            <div class="pbay-info-row">
                <span class="pbay-info-label">Wallet Address</span>
                <span class="pbay-info-value"><code><?php echo esc_html($order['buyer_address']); ?></code></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Name</span>
                <span class="pbay-info-value"><?php echo esc_html($order['buyer_name'] ?? '-'); ?></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Email</span>
                <span class="pbay-info-value"><?php echo esc_html($order['buyer_email'] ?? '-'); ?></span>
            </div>
        </div>

        <!-- Shipping Info -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-car pbay-card-icon"></span>
                <div>
                    <h2>Shipping</h2>
                    <p class="pbay-card-desc">Delivery address and tracking details.</p>
                </div>
            </div>

            <div class="pbay-info-row">
                <span class="pbay-info-label">Name</span>
                <span class="pbay-info-value"><?php echo esc_html($order['shipping_name'] ?? '-'); ?></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Address</span>
                <span class="pbay-info-value">
                    <?php
                    $addr_parts = array_filter([
                        $order['shipping_address_1'] ?? '',
                        $order['shipping_address_2'] ?? '',
                        trim(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? '') . ' ' . ($order['shipping_postal'] ?? '')),
                        $order['shipping_country'] ?? '',
                    ]);
                    echo $addr_parts ? implode('<br/>', array_map('esc_html', $addr_parts)) : '-';
                    ?>
                </span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Phone</span>
                <span class="pbay-info-value"><?php echo esc_html($order['shipping_phone'] ?? '-'); ?></span>
            </div>
            <?php if (!empty($order['shipped_at'])): ?>
                <div class="pbay-info-row">
                    <span class="pbay-info-label">Shipped</span>
                    <span class="pbay-info-value"><?php echo esc_html($order['shipped_at']); ?></span>
                </div>
            <?php endif; ?>

            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #eee;">
                <h3 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:#50575e;">Tracking</h3>
                <div class="pbay-tracking-form">
                    <div class="pbay-tracking-field">
                        <label for="pbay-tracking-carrier">Carrier</label>
                        <input type="text" id="pbay-tracking-carrier" value="<?php echo esc_attr($order['tracking_carrier'] ?? ''); ?>" placeholder="USPS, UPS, FedEx..." />
                    </div>
                    <div class="pbay-tracking-field">
                        <label for="pbay-tracking-number">Tracking Number</label>
                        <input type="text" id="pbay-tracking-number" value="<?php echo esc_attr($order['tracking_number'] ?? ''); ?>" />
                    </div>
                    <div>
                        <button type="button" id="pbay-update-tracking" class="button" data-order-id="<?php echo esc_attr($order['id']); ?>">Save Tracking</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- NFT Delivery -->
        <?php if ($listing && !empty($listing['policy_id']) && !empty($listing['asset_name'])): ?>
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-tickets-alt pbay-card-icon"></span>
                <div>
                    <h2>NFT Delivery</h2>
                    <p class="pbay-card-desc">Transfer the minted NFT to the buyer's wallet.</p>
                </div>
            </div>

            <div class="pbay-info-row">
                <span class="pbay-info-label">Policy ID</span>
                <span class="pbay-info-value"><code><?php echo esc_html($listing['policy_id']); ?></code></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Asset Name</span>
                <span class="pbay-info-value"><code><?php echo esc_html($listing['asset_name']); ?></code></span>
            </div>
            <div class="pbay-info-row">
                <span class="pbay-info-label">Delivery Status</span>
                <span class="pbay-info-value">
                    <?php if (!empty($order['nft_delivery_tx_hash'])): ?>
                        <span class="pbay-delivery-delivered">Delivered</span>
                        <br/>
                        <a href="<?php echo esc_url($explorer . '/transaction/' . $order['nft_delivery_tx_hash']); ?>" target="_blank" style="margin-top:4px; display:inline-block;">
                            <code><?php echo esc_html($order['nft_delivery_tx_hash']); ?></code>
                        </a>
                    <?php else: ?>
                        <span class="pbay-delivery-pending">Not Delivered</span>
                        <div style="margin-top:8px;">
                            <button type="button" id="pbay-send-nft" class="button button-primary" data-order-id="<?php echo esc_attr($order['id']); ?>">
                                Send NFT to Buyer
                            </button>
                            <span id="pbay-nft-send-status" style="margin-left:8px;"></span>
                        </div>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
