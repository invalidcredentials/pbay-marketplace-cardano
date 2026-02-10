<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap pbay-admin">
    <h1>PBay - Wallet</h1>

    <?php
    $error = get_transient('pbay_wallet_error');
    $success = get_transient('pbay_wallet_success');
    if ($error) { delete_transient('pbay_wallet_error'); ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php } ?>
    <?php if ($success) { delete_transient('pbay_wallet_success'); ?>
        <div class="notice notice-success"><p><?php echo esc_html($success); ?></p></div>
    <?php } ?>

    <?php if ($show_mnemonic): ?>
        <div class="pbay-card pbay-mnemonic-warning" style="border-left: 4px solid #d63638; background: #fff3f3;">
            <h2 style="color: #d63638;">SAVE YOUR SEED PHRASE NOW</h2>
            <p><strong>This will only be shown ONCE.</strong> Write it down and store it securely offline.</p>
            <div class="pbay-mnemonic-display" style="background: #1d2327; color: #50c878; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 16px; line-height: 2; word-spacing: 8px; user-select: all;">
                <?php echo esc_html($show_mnemonic); ?>
            </div>
            <p>
                <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($show_mnemonic); ?>').then(()=>this.textContent='Copied!')">Copy to Clipboard</button>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($active_wallet): ?>
        <!-- Active Wallet Dashboard -->
        <div class="pbay-card pbay-wallet-dashboard" data-wallet-id="<?php echo esc_attr($active_wallet['id']); ?>">
            <div class="pbay-wallet-header">
                <h2>
                    <?php echo esc_html($active_wallet['wallet_name']); ?>
                    <span class="pbay-network-badge pbay-network-<?php echo esc_attr($active_wallet['network']); ?>">
                        <?php echo esc_html($active_wallet['network']); ?>
                    </span>
                </h2>
            </div>

            <!-- Balance Display -->
            <div class="pbay-balance-display" id="pbay-wallet-balance">
                <?php if ($has_blockfrost_key): ?>
                    <div class="pbay-balance-loading">Loading balance...</div>
                <?php else: ?>
                    <div class="pbay-balance-no-key">
                        Configure a <a href="<?php echo admin_url('admin.php?page=pbay-setup'); ?>">Blockfrost API key</a> in Setup to view balance.
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($has_blockfrost_key): ?>
                <button type="button" class="button button-small" id="pbay-refresh-balance" style="margin-top: 8px;">Refresh Balance</button>
            <?php endif; ?>

            <!-- Wallet Details -->
            <table class="form-table pbay-wallet-details">
                <tr>
                    <th>Payment Address</th>
                    <td>
                        <code class="pbay-address-code"><?php echo esc_html($active_wallet['payment_address']); ?></code>
                        <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($active_wallet['payment_address']); ?>').then(()=>this.textContent='Copied!')">Copy</button>
                    </td>
                </tr>
                <tr>
                    <th>Key Hash</th>
                    <td>
                        <code><?php echo esc_html($active_wallet['payment_keyhash']); ?></code>
                        <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($active_wallet['payment_keyhash']); ?>').then(()=>this.textContent='Copied!')">Copy</button>
                    </td>
                </tr>
                <tr>
                    <th>Created</th>
                    <td><?php echo esc_html($active_wallet['created_at']); ?></td>
                </tr>
            </table>

            <!-- Actions Row -->
            <div class="pbay-wallet-actions">
                <button type="button" class="button button-primary" id="pbay-send-ada-toggle">Send ADA</button>
                <button type="button" class="button pbay-archive-wallet" data-id="<?php echo esc_attr($active_wallet['id']); ?>">Archive Wallet</button>
                <span class="pbay-wallet-delete-link">
                    <a href="#" class="pbay-delete-active-wallet" data-id="<?php echo esc_attr($active_wallet['id']); ?>">Delete Wallet</a>
                </span>
            </div>

            <!-- Send ADA Form (hidden by default) -->
            <div class="pbay-send-form" id="pbay-send-form" style="display: none;">
                <h3>Send ADA</h3>
                <div class="pbay-send-form-fields">
                    <div class="pbay-send-field">
                        <label for="pbay-send-recipient">Recipient Address</label>
                        <input type="text" id="pbay-send-recipient" class="large-text"
                               placeholder="<?php echo ($network === 'mainnet') ? 'addr1...' : 'addr_test1...'; ?>" />
                    </div>
                    <div class="pbay-send-field">
                        <label for="pbay-send-amount">Amount (ADA)</label>
                        <input type="number" id="pbay-send-amount" step="0.1" min="1" placeholder="ADA amount" style="width: 200px;" />
                    </div>
                    <div class="pbay-send-buttons">
                        <button type="button" class="button button-primary" id="pbay-send-ada-confirm">Send</button>
                        <a href="#" id="pbay-send-ada-cancel">Cancel</a>
                    </div>
                </div>
                <div id="pbay-send-status" class="pbay-send-status"></div>
            </div>

            <!-- Funding Reminder -->
            <div class="pbay-funding-reminder">
                <strong>Reminder:</strong> Keep this wallet funded with 3-5 ADA to cover NFT minting and transfer fees.
            </div>
        </div>

    <?php else: ?>
        <!-- Generate Wallet Form -->
        <div class="pbay-card">
            <h2>Create Wallet (<?php echo esc_html($network); ?>)</h2>
            <p>No active wallet for <strong><?php echo esc_html($network); ?></strong>.</p>

            <form method="post">
                <?php wp_nonce_field('pbay_generate_wallet'); ?>
                <input type="hidden" name="pbay_wallet_action" value="generate" />
                <table class="form-table">
                    <tr>
                        <th>Wallet Name</th>
                        <td><input type="text" name="wallet_name" value="PBay Policy Wallet" class="regular-text" /></td>
                    </tr>
                </table>
                <p>
                    <button type="submit" class="button button-primary">Generate Wallet</button>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <!-- Archived Wallets -->
    <?php $archived_count = count($archived_wallets ?? []); ?>
    <?php if ($archived_count > 0): ?>
        <div class="pbay-card">
            <h2 class="pbay-archived-header" id="pbay-archived-toggle" style="cursor: pointer;">
                Archived Wallets (<?php echo $archived_count; ?>)
                <span class="dashicons dashicons-arrow-down-alt2" style="vertical-align: middle;"></span>
            </h2>
            <div class="pbay-archived-wallets" id="pbay-archived-list">
                <?php foreach ($archived_wallets as $aw): ?>
                    <div class="pbay-archived-card">
                        <div class="pbay-archived-card-header">
                            <strong><?php echo esc_html($aw['wallet_name']); ?></strong>
                            <span class="pbay-network-badge pbay-network-<?php echo esc_attr($aw['network']); ?>">
                                <?php echo esc_html($aw['network']); ?>
                            </span>
                        </div>
                        <div class="pbay-archived-card-details">
                            <div class="pbay-archived-address">
                                <code><?php echo esc_html(substr($aw['payment_address'], 0, 24) . '...' . substr($aw['payment_address'], -8)); ?></code>
                            </div>
                            <div class="pbay-archived-meta">
                                Archived: <?php echo esc_html($aw['archived_at']); ?>
                            </div>
                        </div>
                        <div class="pbay-archived-card-actions">
                            <button type="button" class="button button-small pbay-unarchive-wallet" data-id="<?php echo esc_attr($aw['id']); ?>">Restore</button>
                            <a href="#" class="pbay-delete-archived" data-id="<?php echo esc_attr($aw['id']); ?>" data-name="<?php echo esc_attr($aw['wallet_name']); ?>">Delete Permanently</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
