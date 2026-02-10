<?php if (!defined('ABSPATH')) exit; ?>
<div class="pbay-order-confirmation">
    <div class="pbay-success-icon">&#10003;</div>
    <h2>Thank You For Your Purchase!</h2>
    <p>Your order has been placed successfully.</p>

    <div class="pbay-confirmation-card">
        <div class="pbay-confirm-row">
            <span>Order ID:</span>
            <strong id="pbay-oc-order-id"></strong>
        </div>
        <div class="pbay-confirm-row">
            <span>Transaction:</span>
            <a id="pbay-oc-tx-link" href="#" target="_blank"><code id="pbay-oc-tx-hash"></code></a>
        </div>
        <div class="pbay-confirm-row">
            <span>Amount Paid:</span>
            <strong id="pbay-oc-amount"></strong>
        </div>
    </div>

    <p class="pbay-receipt-note">A 1 ADA receipt has been sent to your wallet as on-chain proof of purchase.</p>
</div>
