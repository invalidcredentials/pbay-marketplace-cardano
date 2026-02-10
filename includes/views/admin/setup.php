<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap pbay-admin">
    <h1>PBay - Store Setup</h1>

    <?php if (!empty($message)): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('pbay_settings_nonce'); ?>

        <!-- Store Settings -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-store pbay-card-icon"></span>
                <div>
                    <h2>Store Settings</h2>
                    <p class="pbay-card-desc">Basic store identity shown on NFT metadata and buyer receipts.</p>
                </div>
            </div>
            <div class="pbay-field-group">
                <div class="pbay-field">
                    <label for="pbay-store-name">Store Name</label>
                    <input type="text" id="pbay-store-name" name="pbay_store_name" value="<?php echo esc_attr(get_option('pbay_store_name', get_bloginfo('name'))); ?>" class="regular-text" />
                    <p class="description">Displayed on NFT metadata and receipts</p>
                </div>
            </div>
        </div>

        <!-- Cardano Network -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-networking pbay-card-icon"></span>
                <div>
                    <h2>Cardano Network</h2>
                    <p class="pbay-card-desc">Network selection and payout wallet configuration.</p>
                </div>
            </div>
            <div class="pbay-field-group">
                <div class="pbay-field-row">
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-network">Network</label>
                        <select id="pbay-network" name="pbay_network">
                            <option value="preprod" <?php selected(get_option('pbay_network', 'preprod'), 'preprod'); ?>>Preprod (Testnet)</option>
                            <option value="mainnet" <?php selected(get_option('pbay_network'), 'mainnet'); ?>>Mainnet</option>
                        </select>
                    </div>
                </div>

                <!-- Store Wallet Payout Toggle -->
                <div class="pbay-field">
                    <label class="pbay-toggle-label">
                        <input type="checkbox" name="pbay_use_store_wallet_payout" value="1" id="pbay-use-store-wallet" <?php checked($use_store_wallet_payout, 1); ?> />
                        <strong>Use store wallet as payout wallet</strong>
                    </label>
                    <p class="description">When enabled, buyer payments are sent to your store wallet instead of a separate merchant address.</p>
                </div>

                <!-- Store Wallet Status -->
                <?php if ($store_wallet): ?>
                    <div class="pbay-callout pbay-callout-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <div>
                            <strong>Store wallet active</strong>
                            <code class="pbay-address-inline"><?php echo esc_html(substr($store_wallet['payment_address'], 0, 24) . '...' . substr($store_wallet['payment_address'], -8)); ?></code>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=pbay-policy-wallet')); ?>">Manage wallet &rarr;</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="pbay-callout pbay-callout-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong>No store wallet found</strong>
                            <p>A store wallet is required for minting NFTs and can optionally receive buyer payments.</p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=pbay-policy-wallet')); ?>" class="button button-secondary">Create Store Wallet &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Merchant Address (shown when NOT using store wallet) -->
                <div class="pbay-field" id="pbay-merchant-address-field" style="<?php echo $use_store_wallet_payout ? 'display:none;' : ''; ?>">
                    <label for="pbay-merchant-address">Merchant Wallet Address</label>
                    <input type="text" id="pbay-merchant-address" name="pbay_merchant_address" value="<?php echo esc_attr(get_option('pbay_merchant_address', '')); ?>" class="large-text" placeholder="addr_test1..." />
                    <p class="description">Your external Cardano wallet address to receive payments</p>
                </div>
            </div>
        </div>

        <!-- Anvil API Keys -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-admin-tools pbay-card-icon"></span>
                <div>
                    <h2>Anvil API Keys</h2>
                    <p class="pbay-card-desc">Required for building and submitting Cardano transactions. <a href="https://ada-anvil.io/services/api" target="_blank">Get your free API keys here</a>.</p>
                </div>
            </div>
            <div class="pbay-field-group">
                <div class="pbay-field-row">
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-anvil-preprod">Preprod API Key</label>
                        <input type="password" id="pbay-anvil-preprod" name="pbay_anvil_api_key_preprod" value="<?php echo esc_attr(get_option('pbay_anvil_api_key_preprod', '')); ?>" class="regular-text" autocomplete="off" />
                    </div>
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-anvil-mainnet">Mainnet API Key</label>
                        <input type="password" id="pbay-anvil-mainnet" name="pbay_anvil_api_key_mainnet" value="<?php echo esc_attr(get_option('pbay_anvil_api_key_mainnet', '')); ?>" class="regular-text" autocomplete="off" />
                    </div>
                </div>
                <div class="pbay-field-actions">
                    <button type="button" id="pbay-test-anvil" class="button">Test Connection</button>
                    <span id="pbay-anvil-result" class="pbay-test-result"></span>
                </div>
            </div>
        </div>

        <!-- Blockfrost API Keys -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-database pbay-card-icon"></span>
                <div>
                    <h2>Blockfrost API Keys</h2>
                    <p class="pbay-card-desc">Used for wallet balance and asset lookups. Get your key from <a href="https://blockfrost.io" target="_blank">blockfrost.io</a>.</p>
                </div>
            </div>
            <div class="pbay-field-group">
                <div class="pbay-field-row">
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-bf-preprod">Preprod API Key</label>
                        <input type="password" id="pbay-bf-preprod" name="pbay_blockfrost_api_key_preprod" value="<?php echo esc_attr(get_option('pbay_blockfrost_api_key_preprod', '')); ?>" class="regular-text" autocomplete="off" />
                    </div>
                    <div class="pbay-field pbay-field-half">
                        <label for="pbay-bf-mainnet">Mainnet API Key</label>
                        <input type="password" id="pbay-bf-mainnet" name="pbay_blockfrost_api_key_mainnet" value="<?php echo esc_attr(get_option('pbay_blockfrost_api_key_mainnet', '')); ?>" class="regular-text" autocomplete="off" />
                    </div>
                </div>
                <p class="description">Falls back to umbrella-blog keys if empty.</p>
            </div>
        </div>

        <!-- Pinata IPFS -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <span class="dashicons dashicons-cloud-upload pbay-card-icon"></span>
                <div>
                    <h2>Pinata IPFS</h2>
                    <p class="pbay-card-desc">Optional — pin product images to IPFS for permanent on-chain references.</p>
                </div>
            </div>
            <div class="pbay-field-group">
                <div class="pbay-field">
                    <label class="pbay-toggle-label">
                        <input type="checkbox" name="pbay_pinata_enabled" value="1" <?php checked(get_option('pbay_pinata_enabled', 0), 1); ?> />
                        <strong>Enable Pinata uploads</strong>
                    </label>
                    <p class="description">Upload product images to IPFS via Pinata when creating listings</p>
                </div>
                <div class="pbay-field">
                    <label for="pbay-pinata-jwt">Pinata JWT Token</label>
                    <input type="password" id="pbay-pinata-jwt" name="pbay_pinata_jwt" value="<?php echo esc_attr(get_option('pbay_pinata_jwt', '')); ?>" class="regular-text" autocomplete="off" />
                    <p class="description">Get from <a href="https://app.pinata.cloud/keys" target="_blank">Pinata Dashboard</a></p>
                </div>
                <div class="pbay-field-actions">
                    <button type="button" id="pbay-test-pinata" class="button">Test Connection</button>
                    <span id="pbay-pinata-result" class="pbay-test-result"></span>
                </div>
            </div>
        </div>

        <p class="submit">
            <button type="submit" name="pbay_save_settings" class="button button-primary button-large">Save Settings</button>
        </p>
    </form>
</div>
