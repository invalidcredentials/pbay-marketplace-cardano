<?php if (!defined('ABSPATH')) exit; ?>
<div class="pbay-order-history">
    <h2>My Orders</h2>

    <!-- Wallet-Connected Orders (Primary) -->
    <div id="pbay-wallet-orders">
        <div class="pbay-wallet-connect-card">
            <p>Connect your Cardano wallet to view your order history, tracking info, and NFT delivery status.</p>
            <button type="button" id="pbay-connect-for-orders" class="pbay-btn pbay-btn-primary">Connect Wallet to View Orders</button>
        </div>
        <div id="pbay-orders-list"></div>
    </div>

    <!-- Order ID / TX Hash Lookup (Secondary) -->
    <div class="pbay-order-lookup">
        <h3>Look Up a Specific Order</h3>
        <p>Don't have your wallet handy? Search by Order ID or TX Hash.</p>
        <div class="pbay-lookup-form">
            <input type="text" id="pbay-lookup-query" placeholder="Order ID (PBAY-XXXX-XXXX) or TX Hash" class="pbay-input-large" />
            <button type="button" id="pbay-lookup-btn" class="pbay-btn pbay-btn-primary">Look Up</button>
        </div>
        <div id="pbay-lookup-result" style="display:none;"></div>
    </div>
</div>
