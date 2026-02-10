<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap pbay-admin">
    <h1>How It Works</h1>

    <div class="pbay-tab-bar">
        <button type="button" class="pbay-tab active" data-tab="quick-guide">Quick Guide</button>
        <button type="button" class="pbay-tab" data-tab="full-docs">Full Documentation</button>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 1: Quick Guide                                           -->
    <!-- ============================================================ -->
    <div class="pbay-tab-panel active" data-tab="quick-guide">

        <?php $tos_agreed = get_option('pbay_tos_agreed', 0); ?>
        <?php if (!$tos_agreed): ?>
            <?php include PBAY_PLUGIN_DIR . 'includes/views/admin/tos-card.php'; ?>
        <?php endif; ?>

        <h2 style="margin-top: 20px;">Get Your Store Running in 5 Steps</h2>

        <!-- Step 1 -->
        <div class="pbay-step-card">
            <div class="pbay-step-number">1</div>
            <div class="pbay-step-content">
                <h3>Configure Your Store</h3>
                <p>Go to <strong>PBay &gt; Setup</strong>. This is a one-time setup where you connect with the services that make your store work: Ada Anvil (processes your transactions), Blockfrost (reads the blockchain), and optionally Pinata (stores your product images permanently). You'll also pick your network &mdash; use "preprod" to test everything with free test currency before going live &mdash; and set the wallet address where you want to receive payments. The whole page takes about 5 minutes.</p>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="pbay-step-card">
            <div class="pbay-step-number">2</div>
            <div class="pbay-step-content">
                <h3>Create a Product Category</h3>
                <p>Go to <strong>Listing Categories</strong> and add one (e.g., "Electronics," "Clothing"). Think of categories the same way you would on any store &mdash; they organize your products for customers. Behind the scenes, PBay also creates a unique policy for each category. A policy is like a tamper-proof stamp that proves every product in that category came from your store and no one else. You don't need to manage this &mdash; it happens automatically.</p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="pbay-step-card">
            <div class="pbay-step-number">3</div>
            <div class="pbay-step-content">
                <h3>List a Product</h3>
                <p>Use the step-by-step listing wizard &mdash; it works just like creating a product on Shopify or Etsy. Upload photos, enter a title, description, and price in USD (PBay automatically shows the ADA equivalent), add attributes like size or color, and configure shipping details. When you hit Publish, PBay creates a unique digital certificate for your product on the blockchain. This certificate proves the product is authentic and came from your store &mdash; it can never be faked, duplicated, or tampered with.</p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="pbay-step-card">
            <div class="pbay-step-number">4</div>
            <div class="pbay-step-content">
                <h3>Customer Buys</h3>
                <p>A customer visits your storefront and connects their Cardano wallet &mdash; similar to how someone taps Apple Pay or Google Pay, except they use a browser extension like Nami or Eternl. They pick a product, enter their shipping info, and confirm payment. The money (in ADA cryptocurrency) lands directly in your wallet within seconds &mdash; no payment processor in the middle, no 3-5 business day holds, no percentage taken off the top. The customer also gets a digital receipt delivered straight to their wallet, giving them permanent proof of purchase that they own and control.</p>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="pbay-step-card">
            <div class="pbay-step-number">5</div>
            <div class="pbay-step-content">
                <h3>Manage &amp; Ship</h3>
                <p>From here it's exactly like running any other online store. Orders appear in your <strong>PBay &gt; Orders</strong> dashboard with status tracking, shipping fields, and CSV export for your records. Update the order status as you pack and ship &mdash; the same workflow you'd use in WooCommerce or Shopify. The difference: your customers don't need an account or login. They just connect their wallet on your orders page and instantly see their order status, tracking info, and payment confirmations.</p>
            </div>
        </div>

        <!-- Why PBay? -->
        <div class="pbay-card" style="margin-top: 30px;">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-star-filled"></span></div>
                <div>
                    <h2>Why PBay?</h2>
                    <p class="pbay-card-desc">The benefits of a direct-to-customer payment system</p>
                </div>
            </div>
            <div class="pbay-benefits-grid">
                <div class="pbay-benefit-item">
                    <span class="dashicons dashicons-money-alt"></span>
                    <span>No monthly fees, no subscriptions</span>
                </div>
                <div class="pbay-benefit-item">
                    <span class="dashicons dashicons-performance"></span>
                    <span>Payments settle in seconds, not days</span>
                </div>
                <div class="pbay-benefit-item">
                    <span class="dashicons dashicons-shield"></span>
                    <span>No chargebacks</span>
                </div>
                <div class="pbay-benefit-item">
                    <span class="dashicons dashicons-groups"></span>
                    <span>No customer accounts or passwords to manage</span>
                </div>
                <div class="pbay-benefit-item">
                    <span class="dashicons dashicons-lock"></span>
                    <span>Every sale has tamper-proof proof on the blockchain</span>
                </div>
                <div class="pbay-benefit-item">
                    <span class="dashicons dashicons-cloud"></span>
                    <span>Works on any WordPress host &mdash; no special server requirements</span>
                </div>
            </div>
        </div>

        <!-- Requirements -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                <div>
                    <h2>Requirements</h2>
                    <p class="pbay-card-desc">What you need to get started</p>
                </div>
            </div>
            <ul class="pbay-requirements-list">
                <li>A WordPress site (5.0+, PHP 7.2+)</li>
                <li>Free API keys from <a href="https://ada-anvil.io/" target="_blank">Ada Anvil</a> and <a href="https://blockfrost.io/" target="_blank">Blockfrost</a></li>
                <li><a href="https://cardanopress.io/" target="_blank">CardanoPress</a> plugin (free) &mdash; adds wallet connection to your site header</li>
                <li>Your customers will need a Cardano wallet browser extension (Nami, Eternl, Lace, etc.) &mdash; think of it like Apple Pay or Google Pay for crypto</li>
            </ul>
        </div>

        <?php if ($tos_agreed): ?>
            <?php include PBAY_PLUGIN_DIR . 'includes/views/admin/tos-card.php'; ?>
        <?php endif; ?>

    </div>

    <!-- ============================================================ -->
    <!-- TAB 2: Full Documentation                                    -->
    <!-- ============================================================ -->
    <div class="pbay-tab-panel" data-tab="full-docs">

        <!-- 1. Architecture Overview -->
        <div class="pbay-card" style="margin-top: 20px;">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-networking"></span></div>
                <div>
                    <h2>Architecture Overview</h2>
                    <p class="pbay-card-desc">How the system connects sellers, buyers, and the blockchain</p>
                </div>
            </div>
<pre style="background: #f6f7f7; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 12px; line-height: 1.5;">
                SELLER (WordPress Admin)
                        |
      +-----------------+-----------------+
      |                 |                 |
 Create Listing    Mint NFT (PHP)    Manage Orders
      |                 |                 |
      v                 v                 v
 [WP Database]    [Anvil API]       [Order Dashboard]
      |           Build TX              |
      |           Sign (Ed25519)        |
      |           Submit                |
      |                 |               |
      |                 v               |
      |          [Cardano Network]      |
      |                 |               |
      +-----------------+---------------+
                        |
                BUYER (Frontend)
                        |
      +-----------------+-----------------+
      |                 |                 |
 Browse Catalog    Pay with ADA     View Orders
 (Shortcodes)     (CIP-30 Wallet)  (Wallet Connect)
</pre>
            <table class="widefat striped" style="margin-top: 16px;">
                <thead>
                    <tr><th>Component</th><th>Purpose</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>CardanoWalletPHP</code></td><td>BIP39 mnemonic generation, CIP-1852 key derivation, Bech32 address encoding</td></tr>
                    <tr><td><code>CardanoTransactionSignerPHP</code></td><td>CBOR transaction parsing, Ed25519 signing, witness set construction</td></tr>
                    <tr><td><code>Ed25519Compat / Ed25519Pure</code></td><td>Pure PHP Ed25519 with triple fallback (native sodium / FFI / BCMath)</td></tr>
                    <tr><td><code>AnvilAPI</code></td><td>Transaction building, policy generation, address conversion, TX submission</td></tr>
                    <tr><td><code>PinataAPI</code></td><td>IPFS image uploads via Pinata (CIDv0, auto-pin on mint)</td></tr>
                    <tr><td><code>MetadataHelper</code></td><td>CIP-25 metadata construction with 64-char chunking for on-chain compliance</td></tr>
                    <tr><td><code>EncryptionHelper</code></td><td>AES-256-CBC encryption of wallet keys using WordPress security salts</td></tr>
                    <tr><td><code>PriceHelper</code></td><td>CoinGecko ADA/USD price feed with transient caching</td></tr>
                    <tr><td><code>BlockfrostAPI</code></td><td>On-chain queries: address balances, asset metadata, UTxO lookups</td></tr>
                </tbody>
            </table>
        </div>

        <!-- 2. How It Works (Technical) -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-controls-repeat"></span></div>
                <div>
                    <h2>How It Works (Technical)</h2>
                    <p class="pbay-card-desc">The detailed flow from listing to purchase to delivery</p>
                </div>
            </div>

            <h3>1. Seller Creates a Listing</h3>
            <p>Six-step wizard: upload images, set title/description/category, set USD price (ADA auto-calculated), add custom attributes, configure shipping, review NFT metadata preview.</p>

            <h3>2. Seller Publishes (Mints the NFT)</h3>
            <p>One click triggers the full pipeline:</p>
            <ol>
                <li>Auto-pin images to IPFS via Pinata</li>
                <li>Build CIP-25 metadata (name, image, description with 64-char chunking, price, category, custom attributes, files array)</li>
                <li>Resolve policy from listing category</li>
                <li>Build mint TX via Anvil API (2 ADA min UTxO + NFT to policy wallet)</li>
                <li>Decrypt policy wallet signing key (AES-256-CBC)</li>
                <li>Sign TX server-side with Ed25519 extended key</li>
                <li>Submit via Anvil &mdash; listing status changes from draft to active</li>
            </ol>

            <h3>3. Buyer Purchases</h3>
            <ol>
                <li>Connect wallet via CIP-30 (CardanoPress header button)</li>
                <li>Enter shipping info, review order with live ADA price</li>
                <li>Server builds payment TX via Anvil:
                    <ul>
                        <li>Output 1: merchant address + payment in ADA</li>
                        <li>Output 2: buyer address + 1 ADA receipt</li>
                    </ul>
                </li>
                <li>Buyer signs in their wallet (private key never touches server)</li>
                <li>Server submits signed TX, creates order record (status: paid)</li>
                <li>NFT delivery attempted automatically (policy wallet &rarr; buyer)</li>
            </ol>

            <h3>4. Buyer Views Orders</h3>
            <p>No login needed. Connect wallet on the orders page &mdash; all orders for that address appear as rich cards showing product image, status badge, price, tracking number, and on-chain TX links.</p>
        </div>

        <!-- 3. Shortcodes -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-shortcode"></span></div>
                <div>
                    <h2>Shortcodes</h2>
                    <p class="pbay-card-desc">Embed your store on any WordPress page</p>
                </div>
            </div>

            <h3><code>[pbay-catalog]</code></h3>
            <p>Renders a responsive product grid with category filtering. Click a product to see the full detail page with gallery and checkout modal.</p>
            <table class="widefat striped" style="max-width: 600px;">
                <thead><tr><th>Attribute</th><th>Default</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>category</code></td><td>all</td><td>Filter by category name</td></tr>
                    <tr><td><code>limit</code></td><td>24</td><td>Products per page</td></tr>
                    <tr><td><code>columns</code></td><td>4</td><td>Grid columns</td></tr>
                </tbody>
            </table>
            <p style="margin-top: 8px;">Supports URL parameters: <code>?pbay_cat=Electronics</code> for category filtering, <code>?pbay_product=123</code> for direct product links.</p>

            <h3 style="margin-top: 20px;"><code>[pbay-product id="X"]</code></h3>
            <p>Renders a single product detail page. Supports <code>id</code> or <code>slug</code> attribute.</p>

            <h3 style="margin-top: 20px;"><code>[pbay-orders]</code></h3>
            <p>Buyer order history. Auto-detects CardanoPress wallet connection and displays all orders for that address.</p>
        </div>

        <!-- 4. Database Schema -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-database"></span></div>
                <div>
                    <h2>Database Schema</h2>
                    <p class="pbay-card-desc">5 custom tables that power the marketplace</p>
                </div>
            </div>
            <table class="widefat striped">
                <thead><tr><th>Table</th><th>Purpose</th></tr></thead>
                <tbody>
                    <tr><td><code>pbay_policy_wallets</code></td><td>Encrypted Cardano wallets (mnemonic + signing key in AES-256-CBC)</td></tr>
                    <tr><td><code>pbay_listing_categories</code></td><td>Product categories with auto-generated Cardano policies</td></tr>
                    <tr><td><code>pbay_listings</code></td><td>Product listings with NFT metadata, IPFS CIDs, pricing, stock</td></tr>
                    <tr><td><code>pbay_listing_meta</code></td><td>Dynamic key-value attributes per listing (embedded in NFT metadata)</td></tr>
                    <tr><td><code>pbay_orders</code></td><td>Purchase records with payment TX, NFT delivery TX, shipping, tracking</td></tr>
                </tbody>
            </table>
        </div>

        <!-- 5. CIP-25 NFT Metadata -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-media-code"></span></div>
                <div>
                    <h2>CIP-25 NFT Metadata</h2>
                    <p class="pbay-card-desc">Every minted listing produces compliant on-chain metadata</p>
                </div>
            </div>
<pre style="background: #f6f7f7; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 12px; line-height: 1.5;">{
  "721": {
    "&lt;policy_id&gt;": {
      "PBAY_42_1706000000": {
        "name": "Vintage Fender Stratocaster 1962",
        "image": "ipfs://QmXyz...",
        "mediaType": "image/png",
        "description": ["A beautiful vintage guitar in excellent c",
                        "ondition. Original pickups and hardware."],
        "priceUSD": "2500.00",
        "category": "Musical Instruments",
        "condition": "Very Good",
        "quantity": "1",
        "seller": "Guitar Palace",
        "attr_year": "1962",
        "attr_color": "Sunburst",
        "files": [
          {"name": "Main Image", "mediaType": "image/png", "src": "ipfs://QmXyz..."},
          {"name": "Gallery 2", "mediaType": "image/png", "src": "ipfs://QmAbc..."}
        ]
      }
    }
  }
}</pre>
            <p style="margin-top: 8px;">All string values are automatically chunked to 64-character arrays when they exceed Cardano's metadata field limit. Gallery images are included in the <code>files</code> array for explorer compatibility.</p>
        </div>

        <!-- 6. Security -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-lock"></span></div>
                <div>
                    <h2>Security</h2>
                    <p class="pbay-card-desc">How wallet keys, transactions, and data are protected</p>
                </div>
            </div>

            <h3>Wallet Key Storage</h3>
            <p>Policy wallet private keys are encrypted at rest using AES-256-CBC. The encryption key is derived from WordPress security salts (<code>AUTH_KEY + SECURE_AUTH_KEY + LOGGED_IN_KEY + NONCE_KEY</code>). Keys are only decrypted in memory during signing operations and never logged or displayed after initial generation.</p>

            <h3 style="margin-top: 16px;">Transaction Security</h3>
            <ul>
                <li>All admin AJAX endpoints verify <code>pbay_admin_nonce</code> + <code>manage_options</code> capability</li>
                <li>All frontend AJAX endpoints verify <code>pbay_checkout_nonce</code> (works for logged-out users)</li>
                <li>Buyer signs payment TX in their own wallet via CIP-30 (private key never touches the server)</li>
                <li>Policy wallet signs mint/delivery TXs server-side (key decrypted only for signing)</li>
                <li>All user inputs sanitized with <code>wp_unslash()</code> + <code>sanitize_text_field()</code></li>
            </ul>

            <h3 style="margin-top: 16px;">What the Server Never Sees</h3>
            <ul>
                <li>Buyer's private key (CIP-30 signing happens in the browser wallet extension)</li>
                <li>Buyer's mnemonic phrase</li>
                <li>Unencrypted policy wallet keys (encrypted at rest, decrypted only in memory)</li>
            </ul>
        </div>

        <!-- 7. API Integrations -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-rest-api"></span></div>
                <div>
                    <h2>API Integrations</h2>
                    <p class="pbay-card-desc">External services and the endpoints PBay uses</p>
                </div>
            </div>

            <h3>Ada Anvil</h3>
            <table class="widefat striped" style="margin-bottom: 20px;">
                <thead><tr><th>Endpoint</th><th>Usage</th></tr></thead>
                <tbody>
                    <tr><td><code>health</code></td><td>Connection test from setup page</td></tr>
                    <tr><td><code>utils/addresses/parse</code></td><td>Hex-to-Bech32 address conversion</td></tr>
                    <tr><td><code>utils/network/time-to-slot</code></td><td>Policy expiration date to Cardano slot</td></tr>
                    <tr><td><code>utils/native-scripts/serialize</code></td><td>Policy schema to policy ID</td></tr>
                    <tr><td><code>transactions/build</code></td><td>Build unsigned TX (payments, mints, transfers)</td></tr>
                    <tr><td><code>transactions/submit</code></td><td>Submit signed TX with witness sets</td></tr>
                </tbody>
            </table>

            <h3>Blockfrost</h3>
            <table class="widefat striped" style="margin-bottom: 20px;">
                <thead><tr><th>Endpoint</th><th>Usage</th></tr></thead>
                <tbody>
                    <tr><td><code>addresses/{address}</code></td><td>Address balance and stake info</td></tr>
                    <tr><td><code>addresses/{address}/utxos</code></td><td>UTxO set for transaction building</td></tr>
                    <tr><td><code>assets/{asset}</code></td><td>NFT metadata and mint info</td></tr>
                </tbody>
            </table>

            <h3>CoinGecko</h3>
            <table class="widefat striped" style="margin-bottom: 20px;">
                <thead><tr><th>Endpoint</th><th>Usage</th></tr></thead>
                <tbody>
                    <tr><td><code>simple/price?ids=cardano&amp;vs_currencies=usd</code></td><td>Live ADA/USD price (5-min cache)</td></tr>
                </tbody>
            </table>

            <h3>Pinata (IPFS)</h3>
            <table class="widefat striped">
                <thead><tr><th>Endpoint</th><th>Usage</th></tr></thead>
                <tbody>
                    <tr><td><code>pinning/pinFileToIPFS</code></td><td>Upload product images, returns CIDv0</td></tr>
                    <tr><td><code>data/pinList</code></td><td>Connection test</td></tr>
                </tbody>
            </table>
        </div>

        <!-- 8. Cardano Standards -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-book-alt"></span></div>
                <div>
                    <h2>Cardano Standards</h2>
                    <p class="pbay-card-desc">Blockchain standards implemented in this plugin</p>
                </div>
            </div>
            <table class="widefat striped">
                <thead><tr><th>Standard</th><th>Implementation</th></tr></thead>
                <tbody>
                    <tr><td><strong>CIP-25</strong></td><td>NFT metadata with 64-char chunking, <code>files</code> array for multi-image</td></tr>
                    <tr><td><strong>CIP-30</strong></td><td>Wallet connection via CardanoPress (getUsedAddresses, signTx)</td></tr>
                    <tr><td><strong>CIP-1852</strong></td><td>HD wallet derivation (<code>m/1852'/1815'/0'/0/0</code>)</td></tr>
                    <tr><td><strong>BIP39</strong></td><td>Mnemonic generation and entropy derivation</td></tr>
                    <tr><td><strong>Ed25519-BIP32</strong></td><td>Khovratovich/Law hierarchical key derivation</td></tr>
                    <tr><td><strong>Icarus</strong></td><td>PBKDF2-HMAC-SHA512 root key generation with clamping</td></tr>
                    <tr><td><strong>RFC 8949</strong></td><td>CBOR encoding/decoding for transaction structures</td></tr>
                    <tr><td><strong>Bech32</strong></td><td>Address encoding for mainnet (<code>addr1</code>) and testnet (<code>addr_test1</code>)</td></tr>
                    <tr><td><strong>Blake2b-224/256</strong></td><td>Key hashes and transaction hashes</td></tr>
                </tbody>
            </table>
        </div>

        <!-- 9. Troubleshooting -->
        <div class="pbay-card">
            <div class="pbay-card-header">
                <div class="pbay-card-icon"><span class="dashicons dashicons-sos"></span></div>
                <div>
                    <h2>Troubleshooting</h2>
                    <p class="pbay-card-desc">Common issues and how to fix them</p>
                </div>
            </div>

            <h3>NFT delivered but showing "Not Delivered"</h3>
            <p>The <code>nft_delivery_tx_hash</code> column may be missing from the orders table. Visit any admin page to trigger the v1.4.0 migration that adds this column automatically.</p>

            <h3 style="margin-top: 16px;">NFT didn't deliver after purchase</h3>
            <p>Check <code>debug.log</code> for <code>[PBay][NFT_DELIVERY]</code> entries. Common causes: policy wallet out of ADA (needs ~2 ADA per transfer) or UTxO contention from simultaneous purchases. Use the "Send NFT to Buyer" button on the order detail page to retry.</p>

            <h3 style="margin-top: 16px;">Images showing local URLs instead of IPFS</h3>
            <p>Pinata JWT may be missing or invalid. Check <strong>PBay &gt; Setup</strong> and test the Pinata connection. Images auto-pin during minting &mdash; if Pinata fails, the fallback is the WordPress media URL.</p>

            <h3 style="margin-top: 16px;">"No active policy wallet" error</h3>
            <p>Navigate to <strong>PBay &gt; Wallet</strong> and either generate a new wallet or unarchive an existing one for the current network.</p>

            <h3 style="margin-top: 16px;">Transaction build fails</h3>
            <ul>
                <li>Ensure the Anvil API key matches the selected network (preprod key for preprod, mainnet key for mainnet)</li>
                <li>Ensure the policy wallet has sufficient ADA (~5 ADA for minting, ~2 ADA for transfers)</li>
                <li>Check <code>debug.log</code> for <code>[PBay] API error</code> entries</li>
            </ul>

            <h3 style="margin-top: 16px;">Wallet not detected on checkout</h3>
            <p>PBay uses CardanoPress for wallet connection. The buyer must connect their wallet via the site header button before opening the checkout modal. Supported wallets: Nami, Eternl, Typhon, Lace, Flint, and others that implement CIP-30.</p>

            <h3 style="margin-top: 16px;">Database columns missing after update</h3>
            <p>PBay's migration system uses explicit <code>ALTER TABLE</code> checks instead of relying solely on <code>dbDelta()</code>. If you suspect a missing column, deactivate and reactivate the plugin, or manually bump the <code>pbay_db_version</code> option to force the migration.</p>
        </div>

        <!-- 10. Footer link -->
        <div class="pbay-card" style="text-align: center; padding: 16px;">
            <p style="margin: 0;">
                <span class="dashicons dashicons-book" style="vertical-align: middle; margin-right: 4px;"></span>
                <a href="https://github.com/pbay-plugin/pbay" target="_blank">Full documentation on GitHub</a>
            </p>
        </div>

    </div>
</div>
