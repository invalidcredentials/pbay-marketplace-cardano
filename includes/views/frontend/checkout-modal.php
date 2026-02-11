<?php if (!defined('ABSPATH')) exit; ?>
<div id="pbay-checkout-modal" class="pbay-modal" style="display:none;">
    <div class="pbay-modal-overlay"></div>
    <div class="pbay-modal-content">
        <button type="button" class="pbay-modal-close">&times;</button>

        <!-- Step 1: Wallet Check -->
        <div class="pbay-checkout-step" data-checkout-step="1">
            <h2>Wallet</h2>

            <div id="pbay-wallet-detecting">
                <div class="pbay-spinner"></div>
                <p>Detecting your wallet connection...</p>
            </div>

            <div id="pbay-wallet-connected" style="display:none;">
                <div class="pbay-wallet-status-card">
                    <div class="pbay-wallet-status-icon">&#10003;</div>
                    <div class="pbay-wallet-status-info">
                        <strong id="pbay-connected-wallet-name">Wallet</strong>
                        <code id="pbay-connected-address"></code>
                    </div>
                </div>
                <button type="button" class="pbay-btn pbay-btn-primary pbay-checkout-next">Continue to Shipping</button>
            </div>

            <div id="pbay-wallet-not-connected" style="display:none;">
                <div class="pbay-wallet-status-card pbay-wallet-status-warning">
                    <p>No wallet connection detected. Please connect your Cardano wallet using the <strong>Connect Wallet</strong> button in the site header, then try again.</p>
                </div>
                <button type="button" class="pbay-btn pbay-btn-primary" id="pbay-retry-wallet">Retry Detection</button>
            </div>
        </div>

        <!-- Step 2: Shipping Info -->
        <div class="pbay-checkout-step" data-checkout-step="2" style="display:none;">
            <h2>Shipping Information</h2>
            <form id="pbay-shipping-form">
                <div class="pbay-form-row">
                    <input type="text" name="buyer_name" placeholder="Full Name *" required />
                </div>
                <div class="pbay-form-row">
                    <input type="email" name="buyer_email" placeholder="Email Address *" required />
                </div>
                <div class="pbay-form-row">
                    <input type="text" name="shipping_name" placeholder="Ship To Name" />
                </div>
                <div class="pbay-form-row">
                    <input type="text" name="shipping_address_1" placeholder="Address Line 1 *" required />
                </div>
                <div class="pbay-form-row">
                    <input type="text" name="shipping_address_2" placeholder="Address Line 2" />
                </div>
                <div class="pbay-form-row pbay-form-row-half">
                    <input type="text" name="shipping_city" placeholder="City *" required />
                    <input type="text" name="shipping_state" placeholder="State/Province *" required />
                </div>
                <div class="pbay-form-row pbay-form-row-half">
                    <input type="text" name="shipping_postal" placeholder="Postal Code *" required />
                    <input type="text" name="shipping_country" placeholder="Country *" required />
                </div>
                <div class="pbay-form-row">
                    <input type="tel" name="shipping_phone" placeholder="Phone (optional)" />
                </div>
                <div class="pbay-form-actions">
                    <button type="button" class="pbay-btn pbay-checkout-back">Back</button>
                    <button type="button" class="pbay-btn pbay-btn-primary pbay-checkout-next">Review Order</button>
                </div>
            </form>
        </div>

        <!-- Step 3: Review & Pay -->
        <div class="pbay-checkout-step" data-checkout-step="3" style="display:none;">
            <h2>Review & Pay</h2>
            <div class="pbay-order-summary">
                <div class="pbay-summary-item">
                    <span>Product:</span>
                    <strong id="pbay-summary-product"></strong>
                </div>
                <div class="pbay-summary-item">
                    <span>Item Price:</span>
                    <strong id="pbay-summary-item-price"></strong>
                </div>
                <div class="pbay-summary-item">
                    <span>Shipping:</span>
                    <strong id="pbay-summary-shipping-cost"></strong>
                </div>
                <div class="pbay-summary-item">
                    <span>Total:</span>
                    <strong id="pbay-summary-price-usd"></strong>
                </div>
                <div class="pbay-summary-item pbay-summary-ada">
                    <span>Total in ADA:</span>
                    <strong id="pbay-summary-price-ada"></strong>
                </div>
                <div class="pbay-summary-item">
                    <span>Receipt (returned to you):</span>
                    <span>1 ADA</span>
                </div>
                <div class="pbay-summary-item">
                    <span>Ship to:</span>
                    <span id="pbay-summary-shipping"></span>
                </div>
            </div>

            <div class="pbay-form-actions">
                <button type="button" class="pbay-btn pbay-checkout-back">Back</button>
                <button type="button" class="pbay-btn pbay-btn-primary pbay-btn-large" id="pbay-confirm-pay">Confirm & Pay with ADA</button>
            </div>
        </div>

        <!-- Step 4: Processing -->
        <div class="pbay-checkout-step" data-checkout-step="4" style="display:none;">
            <div class="pbay-processing">
                <div class="pbay-spinner"></div>
                <h2 id="pbay-processing-status">Building Transaction...</h2>
                <p id="pbay-processing-message">Please approve the transaction in your wallet when prompted.</p>
            </div>
        </div>

        <!-- Step 5: Confirmation -->
        <div class="pbay-checkout-step" data-checkout-step="5" style="display:none;">
            <div class="pbay-confirmation">
                <div class="pbay-success-icon">&#10003;</div>
                <h2>Payment Successful!</h2>
                <div class="pbay-confirmation-details">
                    <p>Order ID: <strong id="pbay-confirm-order-id"></strong></p>
                    <p>Payment TX: <a id="pbay-confirm-tx-link" href="#" target="_blank"><code id="pbay-confirm-tx-hash"></code></a></p>
                </div>
                <div id="pbay-nft-delivery-info" style="display:none;">
                    <div class="pbay-confirmation-details">
                        <p>NFT Proof of Purchase: <a id="pbay-nft-delivery-tx-link" href="#" target="_blank"><code id="pbay-nft-delivery-tx-hash"></code></a></p>
                    </div>
                </div>
                <p>You'll receive on-chain proof of purchase (NFT + 1 ADA receipt) in your wallet.</p>
                <button type="button" class="pbay-btn pbay-btn-primary pbay-modal-close-btn">Done</button>
            </div>
        </div>
    </div>
</div>
