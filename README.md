# PBay

**Free, lightweight ecommerce for WordPress — powered by Cardano under the hood.** Instant payments, instant payouts, cryptographic proof of every sale. No WooCommerce, no Stripe, no payment processor, no middleman.

PBay is a full marketplace plugin built on an ultra-light stack: WordPress + PHP + the Cardano blockchain. Sellers list products, buyers pay in ADA straight from their wallet, and the money lands in the seller's wallet in seconds — not days. Every product is backed by an on-chain NFT. Every purchase generates a tamper-proof receipt delivered directly to the buyer's wallet. No accounts, no passwords, no chargebacks.

---

## What Makes This Different

Traditional ecommerce plugins sit on top of payment processors, subscription fees, and processing delays. PBay cuts all of that out:

- **Instant settlement.** Payments confirm on the Cardano network in seconds. No 3-5 business day holds, no rolling reserves, no processor taking a cut.
- **Zero platform fees.** No monthly subscription, no per-transaction percentage, no premium tiers. The plugin is free. Network fees are fractions of a cent.
- **Tamper-proof receipts.** Every purchase mints a cryptographic receipt delivered to the buyer's wallet — proof of purchase that can't be faked, altered, or lost.
- **No accounts needed.** Buyers connect a Cardano wallet to browse, buy, and track orders. No signups, no passwords, no email harvesting.
- **Products backed by NFTs.** Each listing is a CIP-25 NFT with metadata, images pinned to IPFS, and custom attributes — all verifiable on-chain.

The entire crypto stack runs in pure PHP. No Node.js, no Python, no external binaries. If your server runs WordPress, it runs PBay.

---

## Key Features

- **Server-side NFT minting** - Ed25519 signing, CBOR encoding, and transaction submission happen entirely in PHP via encrypted policy wallets
- **CIP-25 metadata** with 64-character chunking, IPFS image pinning, and dynamic product attributes embedded on-chain
- **Dual-output payments** - merchant receives ADA payment + buyer receives 1 ADA receipt in a single atomic transaction
- **Automatic NFT delivery** - after payment confirmation, the product NFT transfers from the policy wallet to the buyer's wallet server-side
- **CIP-30 wallet integration** - uses CardanoPress for site-wide wallet connection (Nami, Eternl, Typhon, Lace, etc.)
- **Gallery images on IPFS** - up to 4 product images (1 main + 3 gallery) auto-pinned to IPFS via Pinata during minting
- **Category-based policies** - each product category auto-generates a Cardano native script policy with time-lock expiration
- **Real-time USD/ADA conversion** - CoinGecko price feed with 5-minute caching, exchange rate locked at purchase time
- **Wallet-based order tracking** - buyers connect their wallet on the orders page to see rich order cards with status, tracking numbers, and on-chain TX links
- **Admin order management** - status updates, shipping tracking, CSV export, and manual NFT delivery fallback button
- **Frontend theme customizer** - 4 presets (Glass Dark, Clean Light, Warm Dark, Midnight), custom color dropdowns for cards/text/accent/buttons, live preview, CSS variable injection
- **Store wallet as payout** - optionally route payments directly to the policy wallet, eliminating the need for a separate merchant address
- **Drag-and-drop image upload** - drop images directly onto the upload area in the listing wizard with visual feedback and instant upload
- **Blockfrost integration** - on-chain address lookups, wallet balance queries, and NFT asset discovery via Blockfrost API
- **Zero external dependencies** for crypto - Ed25519, BIP39, CBOR, Bech32 all implemented in pure PHP

---

## Architecture Overview

```
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
          |                 |                 |
          v                 v                 v
    [Product Page]   [Checkout Modal]  [Order History]
                     Build TX (Anvil)   NFT Delivery TX
                     Sign (Wallet)      Tracking Info
                     Submit             Explorer Links
                     Create Order
                     Deliver NFT
```

### Core Components

| Component | Purpose |
|-----------|---------|
| `CardanoWalletPHP` | BIP39 mnemonic generation, CIP-1852 key derivation, Bech32 address encoding |
| `CardanoTransactionSignerPHP` | CBOR transaction parsing, Ed25519 signing, witness set construction |
| `Ed25519Compat` / `Ed25519Pure` | Pure PHP Ed25519 with triple fallback (native sodium / FFI / BCMath) |
| `AnvilAPI` | Transaction building, policy generation, address conversion, TX submission |
| `PinataAPI` | IPFS image uploads via Pinata (CIDv0, auto-pin on mint) |
| `MetadataHelper` | CIP-25 metadata construction with 64-char chunking for on-chain compliance |
| `EncryptionHelper` | AES-256-CBC encryption of wallet keys using WordPress security salts |
| `PriceHelper` | CoinGecko ADA/USD price feed with transient caching |
| `BlockfrostAPI` | On-chain queries: address balances, asset metadata, UTxO lookups |

---

## How It Works

### 1. Seller Creates a Listing

Six-step wizard: upload images, set title/description/category, set USD price (ADA auto-calculated), add custom attributes, configure shipping, review NFT metadata preview.

### 2. Seller Publishes (Mints the NFT)

One click triggers the full pipeline:

```
Upload Image(s)
    |
Auto-Pin to IPFS (Pinata)
    |
Build CIP-25 Metadata
    |-- name, image (ipfs://), description (chunked)
    |-- priceUSD, category, condition, quantity
    |-- seller, custom attr_* fields
    |-- files[] array (main + gallery images)
    |
Resolve Policy (from listing category)
    |
Build Mint TX (Anvil API)
    |-- changeAddress: policy wallet
    |-- outputs: [{policy wallet, 2 ADA, assets: [{policyId, assetName, qty: 1}]}]
    |-- mint: [{version: 'cip25', metadata: ...}]
    |-- preloadedScripts: [{type: 'simple', script, hash}]
    |
Sign TX Server-Side
    |-- Decrypt policy wallet skey (AES-256-CBC)
    |-- Ed25519 extended key signing (kL||kR)
    |-- Construct witness set (CBOR)
    |
Submit via Anvil
    |
Listing Status: draft -> active
    |-- mint_tx_hash stored
    |-- published_at timestamp set
```

### 3. Buyer Purchases

```
Connect Wallet (CIP-30 via CardanoPress)
    |
Enter Shipping Info
    |
Review Order (live ADA price)
    |
Build Payment TX (Server)
    |-- Anvil resolves buyer UTxOs on-chain
    |-- Output 1: merchant_address + payment in ADA
    |-- Output 2: buyer_address + 1 ADA receipt
    |
Sign in Wallet (CIP-30 signTx, partial)
    |
Submit Payment TX (Server -> Anvil)
    |
Create Order Record (status: 'paid')
    |
Attempt NFT Delivery (non-blocking)
    |-- Build transfer TX: policy wallet -> buyer
    |-- Sign server-side with policy wallet
    |-- Submit via Anvil
    |-- Store nft_delivery_tx_hash on order
    |
Return Confirmation
    |-- Order ID (PBAY-XXXX-XXXX)
    |-- Payment TX hash + Cardanoscan link
    |-- NFT delivery TX hash + link
```

### 4. Buyer Views Orders

No login. Connect wallet on the orders page - all orders for that address appear as rich cards showing product image, status badge, price, tracking number, payment TX link, and NFT delivery TX link.

---

## Installation

### Requirements

- **WordPress 5.0+** with PHP 7.2+ (PHP 8.0+ recommended)
- **[CardanoPress](https://cardanopress.io/)** plugin for CIP-30 wallet connection in the site header
- **[Ada Anvil](https://ada-anvil.io/) API key** for transaction building and submission
- **[Pinata](https://pinata.cloud/) JWT** (optional but recommended) for IPFS image pinning

### Setup

1. Clone or download into `wp-content/plugins/pbay/`
2. Activate via WordPress admin
3. Navigate to **PBay > Setup**:
   - Select network (preprod for testing, mainnet for production)
   - Enter Anvil API key (preprod and/or mainnet)
   - Enter Blockfrost API key (for wallet balance queries)
   - Enter merchant wallet address (where payments go), or check "Use store wallet as payout wallet" to route payments to the policy wallet
   - Enter Pinata JWT (optional, for IPFS image pinning)
4. Navigate to **PBay > Policy Wallet**:
   - Generate a policy wallet (save the mnemonic!)
   - Fund it with ~10 ADA on the selected network
5. Navigate to **PBay > Listing Categories**:
   - Create a category (auto-generates a Cardano policy)
6. Create a WordPress page with shortcode `[pbay-catalog]` for your storefront
7. Create a WordPress page with slug `pbay-orders` and shortcode `[pbay-orders]` for buyer order history
8. (Optional) Navigate to **PBay > Appearance** to customize the frontend theme

---

## Shortcodes

### `[pbay-catalog]`

Renders a responsive product grid with category filtering. When a product is clicked, renders the full product detail page with gallery, specs, and checkout modal.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `category` | all | Filter by category name |
| `limit` | 24 | Products per page |
| `columns` | 4 | Grid columns |

Supports URL parameters: `?pbay_cat=Electronics` for category filtering, `?pbay_product=123` for direct product links.

### `[pbay-product id="X"]`

Renders a single product detail page. Supports `id` or `slug` attribute.

### `[pbay-orders]`

Buyer order history. Auto-detects CardanoPress wallet connection and displays all orders for that address.

---

## Database Schema

### 5 Tables

| Table | Purpose |
|-------|---------|
| `pbay_policy_wallets` | Encrypted Cardano wallets (mnemonic + signing key in AES-256-CBC) |
| `pbay_listing_categories` | Product categories with auto-generated Cardano policies |
| `pbay_listings` | Product listings with NFT metadata, IPFS CIDs, pricing, stock |
| `pbay_listing_meta` | Dynamic key-value attributes per listing (embedded in NFT metadata) |
| `pbay_orders` | Purchase records with payment TX, NFT delivery TX, shipping, tracking |

---

## CIP-25 NFT Metadata

Every minted listing produces compliant CIP-25 metadata:

```json
{
  "721": {
    "<policy_id>": {
      "PBAY_42_1706000000": {
        "name": "Vintage Fender Stratocaster 1962",
        "image": "ipfs://QmXyz...",
        "mediaType": "image/png",
        "description": ["A beautiful vintage guitar in excellent c", "ondition. Original pickups and hardware."],
        "priceUSD": "2500.00",
        "category": "Musical Instruments",
        "condition": "Very Good",
        "quantity": "1",
        "seller": "Guitar Palace",
        "attr_year": "1962",
        "attr_color": "Sunburst",
        "attr_brand": "Fender",
        "files": [
          {"name": "Vintage Fender Stratocaster 1962", "mediaType": "image/png", "src": "ipfs://QmXyz..."},
          {"name": "Vintage Fender Stratocaster 1962 2", "mediaType": "image/png", "src": "ipfs://QmAbc..."},
          {"name": "Vintage Fender Stratocaster 1962 3", "mediaType": "image/png", "src": "ipfs://QmDef..."}
        ]
      }
    }
  }
}
```

All string values are automatically chunked to 64-character arrays when they exceed Cardano's metadata field limit. Gallery images are included in the `files` array for explorer compatibility (pool.pm, Cardanoscan, jpg.store).

---

## NFT Delivery

### Automatic (On Purchase)

After payment is confirmed, PBay immediately attempts to transfer the product NFT from the policy wallet to the buyer's wallet:

1. Build asset transfer TX via Anvil (policy wallet -> buyer, 2 ADA min UTxO + NFT)
2. Sign server-side with the encrypted policy wallet key
3. Submit to network
4. Store `nft_delivery_tx_hash` on the order

This is wrapped in a try-catch - **if delivery fails, the order still succeeds**. The buyer got their product, the payment went through, and the admin can retry NFT delivery manually.

### Manual Fallback (Admin)

On the order detail page, if NFT delivery hasn't happened yet, an admin button appears: **"Send NFT to Buyer"**. Same build-sign-submit flow, triggered manually. Double-send prevention checks that `nft_delivery_tx_hash` is null before allowing.

### Edge Cases Handled

- **Listing has no NFT** (physical-only product without minting): delivery skipped entirely
- **Policy wallet has no ADA**: Anvil build fails, caught and logged, order still succeeds
- **NFT already transferred**: Anvil can't find asset UTxO, fails gracefully
- **Concurrent purchases**: UTxO contention possible, failed delivery caught, admin can retry

---

## Security

### Wallet Key Storage

Policy wallet private keys are encrypted at rest using AES-256-CBC:

```
Key = SHA256(AUTH_KEY + SECURE_AUTH_KEY + LOGGED_IN_KEY + NONCE_KEY)
IV  = random 16 bytes (prepended to ciphertext)
Stored = base64(IV || AES-256-CBC(plaintext, key, IV))
```

Keys are only decrypted in memory during signing operations and never logged or displayed after initial generation.

### Transaction Security

- All admin AJAX endpoints verify `pbay_admin_nonce` + `manage_options` capability
- All frontend AJAX endpoints verify `pbay_checkout_nonce` (works for logged-out users)
- Buyer signs payment TX in their own wallet via CIP-30 (private key never touches the server)
- Policy wallet signs mint/delivery TXs server-side (key decrypted only for signing)
- All user inputs sanitized with `wp_unslash()` + `sanitize_text_field()` / `wp_kses_post()`

### What the Server Never Sees

- Buyer's private key (CIP-30 signing happens in the browser wallet extension)
- Buyer's mnemonic phrase
- Unencrypted policy wallet keys (encrypted at rest, decrypted only in memory)

---

## Admin Pages

| Page | Location | Purpose |
|------|----------|---------|
| **Setup** | PBay > Setup | Network selection, API keys (Anvil, Blockfrost, Pinata), merchant address, store wallet payout toggle |
| **Listing Categories** | PBay > Listing Categories | Create categories with auto-generated Cardano policies, status badges, listing counts |
| **Create Listing** | PBay > Create Listing | 6-step wizard: image/gallery (drag-and-drop), info, pricing, attributes, shipping, review + mint |
| **Inventory** | PBay > Inventory | Listings table with status filter tabs, badge pills, thumbnail previews, bulk actions |
| **Orders** | PBay > Orders | Stats dashboard, order table with status badges, CSV export, click-through to detail |
| **Appearance** | PBay > Appearance | Frontend theme presets, custom color dropdowns, live preview card, shortcodes reference |
| **Policy Wallet** | PBay > Policy Wallet | Generate/archive wallets, view addresses, keyhashes, balances, and NFT assets |

All admin pages share a consistent design language: icon card headers, field group layouts, status badge pills, callout boxes, and responsive grids.

---

## Project Structure

```
pbay/
+-- pbay.php                              # Bootstrap, activation, menus, enqueue, migrations, ToS gate
+-- uninstall.php                         # Clean uninstall (drops tables + options)
+-- CHANGELOG.md                          # Version history
|
+-- includes/
|   +-- helpers/
|   |   +-- CardanoWalletPHP.php          # BIP39/CIP-1852 wallet generation (pure PHP)
|   |   +-- CardanoTransactionSignerPHP.php # CBOR parsing + Ed25519 TX signing
|   |   +-- Ed25519Compat.php             # Ed25519 compatibility (native/FFI/pure fallback)
|   |   +-- Ed25519Pure.php               # Pure PHP Ed25519 via BCMath
|   |   +-- bip39-wordlist.php            # BIP39 English wordlist (2048 words)
|   |   +-- EncryptionHelper.php          # AES-256-CBC encrypt/decrypt using WP salts
|   |   +-- AnvilAPI.php                  # Anvil API (build TX, submit, policies, addresses)
|   |   +-- PinataAPI.php                 # Pinata IPFS uploads (cURL, CIDv0)
|   |   +-- MetadataHelper.php            # CIP-25 metadata builder + 64-char chunking
|   |   +-- PriceHelper.php               # USD/ADA conversion via CoinGecko
|   |   +-- BlockfrostAPI.php             # On-chain queries (balances, assets, UTxOs)
|   |
|   +-- models/
|   |   +-- PolicyWalletModel.php         # Encrypted wallet CRUD
|   |   +-- ListingCategoryModel.php      # Categories with policy management
|   |   +-- ListingModel.php              # Listings + listing_meta CRUD
|   |   +-- OrderModel.php                # Orders with NFT delivery tracking
|   |
|   +-- controllers/
|   |   +-- AdminController.php           # Setup, orders, tracking, NFT send, CSV export
|   |   +-- ListingCategoryController.php # Category CRUD + auto policy generation
|   |   +-- ListingController.php         # Listing CRUD, IPFS pin, policy gen, NFT mint
|   |   +-- CheckoutController.php        # Payment TX, order creation, NFT delivery
|   |   +-- CatalogController.php         # Shortcode registration + rendering
|   |   +-- PolicyWalletController.php    # Wallet generation, archive, unarchive
|   |   +-- AppearanceController.php      # Theme presets, custom colors, CSS injection
|   |
|   +-- views/
|       +-- admin/
|       |   +-- setup.php                 # Settings form with wallet payout toggle
|       |   +-- listing-categories.php    # Category management with status badges
|       |   +-- create-listing.php        # 6-step wizard with drag-and-drop upload
|       |   +-- edit-listing.php          # Wrapper that includes create-listing.php
|       |   +-- inventory.php             # Listings table with icon headers
|       |   +-- orders.php                # Orders table + stats dashboard
|       |   +-- order-detail.php          # Order detail: info rows, tracking, NFT delivery
|       |   +-- appearance.php            # Theme presets, color dropdowns, preview, shortcodes
|       |   +-- how-it-works.php          # Quick guide + full docs (tabbed)
|       |   +-- tos-card.php             # Terms of Service card partial
|       |   +-- policy-wallet.php         # Wallet generation + management
|       |
|       +-- frontend/
|           +-- catalog.php               # Product grid with category filters
|           +-- product-detail.php        # Product page with gallery + buy button
|           +-- checkout-modal.php        # 5-step checkout (wallet -> shipping -> pay)
|           +-- order-history.php         # Wallet-connected order lookup
|           +-- order-confirmation.php    # Post-purchase confirmation
|
+-- assets/
    +-- js/
    |   +-- pbay-admin.js                 # Wizard, gallery, drag-drop, appearance, order mgmt
    |   +-- pbay-checkout.js              # CIP-30 wallet, checkout flow, order history
    |
    +-- css/
        +-- pbay-admin.css                # Admin styles (cards, badges, info rows, presets)
        +-- pbay-frontend.css             # Frontend styles with CSS variable theming
```

---

## API Integrations

### Ada Anvil

| Endpoint | Usage |
|----------|-------|
| `health` | Connection test from setup page |
| `utils/addresses/parse` | Hex-to-Bech32 address conversion |
| `utils/network/time-to-slot` | Policy expiration date to Cardano slot |
| `utils/native-scripts/serialize` | Policy schema to policy ID |
| `transactions/build` | Build unsigned TX (payments, mints, transfers) |
| `transactions/submit` | Submit signed TX with witness sets |

### Blockfrost

| Endpoint | Usage |
|----------|-------|
| `addresses/{address}` | Address balance and stake info |
| `addresses/{address}/utxos` | UTxO set for transaction building |
| `assets/{asset}` | NFT metadata and mint info |

Supports both preprod and mainnet with separate API keys configured in Setup.

### CoinGecko

| Endpoint | Usage |
|----------|-------|
| `simple/price?ids=cardano&vs_currencies=usd` | Live ADA/USD price (5-min cache) |

### Pinata (IPFS)

| Endpoint | Usage |
|----------|-------|
| `pinning/pinFileToIPFS` | Upload product images, returns CIDv0 |
| `data/pinList` | Connection test |

---

## Frontend Theming

PBay's frontend uses a CSS variable system that allows full theme customization without editing stylesheets. The default theme is a dark glassmorphism look (frosted glass cards, cyan accents, dark navy background).

### How It Works

1. `pbay-frontend.css` declares `:root` variables with sensible defaults:
   ```css
   :root {
       --pbay-card-bg: rgba(255, 255, 255, 0.08);
       --pbay-card-border: rgba(255, 255, 255, 0.12);
       --pbay-text: var(--umbrella-light, #f8f9fa);
       --pbay-accent: var(--umbrella-secondary, #00d4ff);
       --pbay-bg: var(--umbrella-dark, #0a0e27);
       --pbay-blur: 16px;
       --pbay-btn-bg: transparent;
       --pbay-btn-color: var(--umbrella-secondary, #00d4ff);
   }
   ```

2. `AppearanceController::outputFrontendCSS()` injects overrides via `wp_add_inline_style()` based on the saved theme settings.

3. All card backgrounds, borders, text colors, accent colors, and button styles reference these variables throughout the frontend CSS.

### Presets

| Preset | Background | Accent | Style |
|--------|-----------|--------|-------|
| **Glass Dark** (default) | Dark navy, frosted glass | Cyan `#00d4ff` | Glassmorphism with backdrop blur |
| **Clean Light** | White, solid cards | Blue `#2271b1` | Minimal, corporate feel |
| **Warm Dark** | Deep charcoal | Amber `#f0b849` | Warm tones, subtle glass |
| **Midnight** | Pure black | Purple `#a78bfa` | Ultra-minimal, heavy blur |

### Custom Colors

After selecting a preset, individual settings can be overridden:
- **Card Background** — Glass / White / Light Gray / Dark / Charcoal / Black
- **Card Border** — Subtle / Medium / None / Accent-tinted
- **Text Color** — White / Light Gray / Dark Gray / Black
- **Accent Color** — Cyan / Blue / Purple / Green / Amber / Red / Pink
- **Button Style** — Outline (current) / Filled / Soft

---

## Cardano Standards Implemented

| Standard | Implementation |
|----------|---------------|
| **[CIP-25](https://cips.cardano.org/cips/cip25/)** | NFT metadata with 64-char chunking, `files` array for multi-image |
| **[CIP-30](https://cips.cardano.org/cips/cip30/)** | Wallet connection via CardanoPress (getUsedAddresses, signTx) |
| **[CIP-1852](https://cips.cardano.org/cips/cip1852/)** | HD wallet derivation (m/1852'/1815'/0'/0/0) |
| **[BIP39](https://github.com/bitcoin/bips/blob/master/bip-0039.mediawiki)** | Mnemonic generation and entropy derivation |
| **Ed25519-BIP32** | Khovratovich/Law hierarchical key derivation |
| **Icarus** | PBKDF2-HMAC-SHA512 root key generation with clamping |
| **[RFC 8949](https://datatracker.ietf.org/doc/html/rfc8949)** | CBOR encoding/decoding for transaction structures |
| **Bech32** | Address encoding for mainnet (`addr1`) and testnet (`addr_test1`) |
| **Blake2b-224/256** | Key hashes and transaction hashes |

---

## Performance Notes

The crypto stack uses a triple-fallback system:

| Backend | Wallet Gen | TX Signing | Availability |
|---------|-----------|------------|-------------|
| **Native Sodium** | ~50ms | ~5ms | PHP 8.3+ |
| **FFI libsodium** | ~100ms | ~10ms | PHP 7.4+ with FFI enabled |
| **Pure PHP BCMath** | ~2000ms | ~50ms | Everywhere (fallback) |

Transaction building and submission go through the Anvil API (typically 1-3 seconds). IPFS pinning via Pinata takes 2-10 seconds per image. The full mint flow (auto-pin + build + sign + submit) completes in under 15 seconds on a typical server.

---

## Troubleshooting

### NFT delivered but showing "Not Delivered"

If NFTs are arriving at buyer wallets but the order detail page shows "Not Delivered," the `nft_delivery_tx_hash` column may be missing from the orders table. This happens when the table was created before the column was added to the schema — `dbDelta()` doesn't reliably add columns to existing tables. **Fix:** The v1.4.0 migration adds this column automatically via `ALTER TABLE`. Visit any admin page to trigger the migration. Previously delivered orders won't retroactively update — check the explorer for those.

### NFT didn't deliver after purchase

Check `debug.log` for `[PBay][NFT_DELIVERY]` entries. Common causes:
- Policy wallet out of ADA (needs ~2 ADA per transfer)
- UTxO contention if multiple purchases happen simultaneously
- **Fix:** Use the "Send NFT to Buyer" button on the order detail page

### Images showing local URLs instead of IPFS

Pinata JWT may be missing or invalid. Check **PBay > Setup** and test the Pinata connection. Images auto-pin during minting - if Pinata fails, the fallback is the WordPress media URL.

### "No active policy wallet" error

Navigate to **PBay > Policy Wallet** and either generate a new wallet or unarchive an existing one for the current network.

### Transaction build fails

- Ensure the Anvil API key matches the selected network (preprod key for preprod, mainnet key for mainnet)
- Ensure the policy wallet has sufficient ADA (~5 ADA for minting, ~2 ADA for transfers)
- Check `debug.log` for `[PBay] API error` entries with the full response

### Wallet not detected on checkout

PBay uses CardanoPress for wallet connection. The buyer must connect their wallet via the **site header button** before opening the checkout modal. Supported wallets: Nami, Eternl, Typhon, Lace, Flint, and others that implement CIP-30.

### Backslashes in descriptions (`It\'s`)

Fixed in current version. All text inputs use `wp_unslash()` before sanitization to handle WordPress magic quotes.

### Database columns missing after update

PBay's migration system (v1.4.0+) uses explicit `ALTER TABLE` checks instead of relying solely on `dbDelta()`, which doesn't reliably add columns to existing tables. If you suspect a missing column, deactivate and reactivate the plugin to trigger the full table creation, or manually bump the `pbay_db_version` option to force the migration.

---

## Admin UI Design

Every admin page follows a consistent design system built on top of standard WordPress admin styles:

- **Icon card headers** — Each section has a dashicon in a blue rounded box, title, and subtitle description
- **Field group layouts** — Side-by-side fields, labeled inputs, toggle checkboxes, and action rows
- **Status badges** — Color-coded pills for all statuses (14 variants across inventory and orders)
- **Callout boxes** — Success (green) and warning (amber) callouts for wallet status, empty states
- **Info rows** — Clean label/value pairs for order detail views (replaces WordPress `form-table`)
- **Stat cards** — Dashboard summary cards with blue accent top border
- **Responsive** — All layouts collapse gracefully at 768px

The styling is intentionally light — it enhances WordPress admin without fighting it.

---

## Limitations

- **Single merchant** per install (one payment address)
- **Cardano only** - no EVM/Solana/Bitcoin support
- **Requires CardanoPress** for frontend wallet connection
- **Requires Ada Anvil** for transaction building (no local cardano-node)
- **One active policy wallet** per network at a time
- **No fractional quantities** - integer units only
- **Exchange rate volatility** - ADA price cached 5 minutes, locked at purchase time

---

## Source Codebases

PBay builds on top of battle-tested components:

| Component | Origin | What Was Used |
|-----------|--------|---------------|
| **[PHP-Cardano](https://github.com/invalidcredentials/PHP-Cardano)** | `cardano-wallet-test` | Pure PHP wallet generation + Ed25519 TX signing |
| **cardano-mint-pay** | WordPress plugin | AnvilAPI, EncryptionHelper, PinataAPI patterns |
| **anvil-playground** | TypeScript prototype | CIP-25 metadata chunking logic (ported to PHP) |
| **[CardanoPress](https://cardanopress.io/)** | WordPress plugin | CIP-30 wallet connection in site header |
| **[Ada Anvil](https://ada-anvil.io/)** | API service | Transaction building, policy generation, submission |

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

---

## License

This project is open source and available under the terms specified in the LICENSE file.

---

## Acknowledgments

Built on the Cardano blockchain with:
- [Ada Anvil](https://ada-anvil.io/) for transaction infrastructure
- [CardanoPress](https://cardanopress.io/) for CIP-30 wallet integration
- [Pinata](https://pinata.cloud/) for IPFS pinning
- [CoinGecko](https://www.coingecko.com/) for price data
- The Cardano developer community for CIP standards and documentation

---

*Built with pure PHP stubbornness and the belief that ecommerce doesn't need a middleman.*
