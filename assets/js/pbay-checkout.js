/**
 * PBay Checkout JavaScript
 * Uses CardanoPress for wallet connection (already connected via site header).
 * Handles: CIP-30 interaction, payment TX building/signing/submission,
 * order lookup, buyer order history.
 */
(function ($) {
    'use strict';

    var walletApi = null;
    var connectedAddress = '';
    var currentCheckoutStep = 1;
    var currentListingId = null;
    var currentPriceUsd = null;
    var unsignedTx = null;

    // ========================================
    // CardanoPress Wallet Detection
    // ========================================

    /**
     * Get the CIP-30 wallet API from CardanoPress connection.
     * Returns a Promise that resolves to the wallet API or null.
     */
    function getCardanoPressWallet() {
        return new Promise(function (resolve) {
            var connected = localStorage.getItem('_x_connectedExtension');
            if (!connected) {
                resolve(null);
                return;
            }

            var walletKey = connected.toLowerCase();
            var cardanoKey = walletKey === 'typhon' ? 'typhoncip30' : walletKey;

            if (!window.cardano || !window.cardano[cardanoKey]) {
                resolve(null);
                return;
            }

            window.cardano[cardanoKey].enable().then(function (api) {
                resolve(api);
            }).catch(function (err) {
                console.error('Failed to enable CardanoPress wallet:', err);
                resolve(null);
            });
        });
    }

    /**
     * Get the buyer's address from the wallet API (hex-encoded).
     */
    function getWalletAddress(api) {
        return api.getUsedAddresses().then(function (addresses) {
            if (addresses && addresses.length > 0) {
                return addresses[0];
            }
            return api.getChangeAddress();
        });
    }

    // ========================================
    // ADA Price Display on Product Page
    // ========================================

    function fetchAndDisplayAdaPrice() {
        var $priceEl = $('#pbay-product-ada-price');
        if (!$priceEl.length) return;

        var $buyBtn = $('#pbay-buy-now');
        var priceUsd = $buyBtn.length ? parseFloat($buyBtn.data('price-usd')) : 0;
        if (priceUsd <= 0) return;

        $.post(pbayCheckout.ajaxurl, {
            action: 'pbay_get_ada_price',
            nonce: pbayCheckout.nonce,
        }, function (response) {
            if (response.success && response.data.ada_usd) {
                var adaPrice = parseFloat(response.data.ada_usd);
                var adaAmount = (priceUsd / adaPrice).toFixed(2);
                $priceEl.text('(\u2248 ' + adaAmount + ' ADA)');
            }
        });
    }

    // ========================================
    // Product Gallery Thumbnail Switching
    // ========================================

    $(document).on('click', '.pbay-gallery-thumb', function () {
        var largeUrl = $(this).data('large');
        if (largeUrl) {
            $('#pbay-main-image').attr('src', largeUrl);
            $('.pbay-gallery-thumb').removeClass('active');
            $(this).addClass('active');
        }
    });

    // ========================================
    // Buy Now -> Open Modal
    // ========================================

    $(document).on('click', '#pbay-buy-now', function () {
        currentListingId = $(this).data('listing-id');
        currentPriceUsd = $(this).data('price-usd');

        showCheckoutModal();
        goToCheckoutStep(1);
        detectWalletConnection();
    });

    function showCheckoutModal() {
        $('#pbay-checkout-modal').show();
        $('body').css('overflow', 'hidden');
    }

    function hideCheckoutModal() {
        $('#pbay-checkout-modal').hide();
        $('body').css('overflow', '');
        resetCheckout();
    }

    function resetCheckout() {
        walletApi = null;
        connectedAddress = '';
        currentCheckoutStep = 1;
        unsignedTx = null;
        $('#pbay-wallet-connected').hide();
        $('#pbay-wallet-not-connected').hide();
        $('#pbay-wallet-detecting').show();
        var form = document.getElementById('pbay-shipping-form');
        if (form) form.reset();
    }

    // Close modal
    $(document).on('click', '.pbay-modal-close, .pbay-modal-close-btn', function () {
        hideCheckoutModal();
    });

    $(document).on('click', '.pbay-modal-overlay', function () {
        if (currentCheckoutStep < 4) {
            hideCheckoutModal();
        }
    });

    // ========================================
    // Wallet Detection (CardanoPress)
    // ========================================

    function detectWalletConnection() {
        $('#pbay-wallet-detecting').show();
        $('#pbay-wallet-connected').hide();
        $('#pbay-wallet-not-connected').hide();

        getCardanoPressWallet().then(function (api) {
            if (api) {
                walletApi = api;
                return getWalletAddress(api).then(function (address) {
                    connectedAddress = address;

                    // Show connected state
                    var displayAddr = address.substring(0, 16) + '...' + address.substring(address.length - 8);
                    $('#pbay-connected-address').text(displayAddr);

                    var walletName = localStorage.getItem('_x_connectedExtension') || 'Wallet';
                    $('#pbay-connected-wallet-name').text(walletName);

                    $('#pbay-wallet-detecting').hide();
                    $('#pbay-wallet-connected').show();
                });
            } else {
                // Not connected
                $('#pbay-wallet-detecting').hide();
                $('#pbay-wallet-not-connected').show();
            }
        });
    }

    // Retry button if wallet wasn't detected
    $(document).on('click', '#pbay-retry-wallet', function () {
        detectWalletConnection();
    });

    // ========================================
    // Checkout Step Navigation
    // ========================================

    function goToCheckoutStep(step) {
        currentCheckoutStep = step;
        $('.pbay-checkout-step').hide();
        $('.pbay-checkout-step[data-checkout-step="' + step + '"]').show();
    }

    $(document).on('click', '.pbay-checkout-next', function () {
        if (currentCheckoutStep === 1 && !connectedAddress) {
            alert('Please connect your wallet first using the button in the site header.');
            return;
        }

        if (currentCheckoutStep === 2) {
            var form = document.getElementById('pbay-shipping-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            populateOrderSummary();
        }

        goToCheckoutStep(currentCheckoutStep + 1);
    });

    $(document).on('click', '.pbay-checkout-back', function () {
        goToCheckoutStep(currentCheckoutStep - 1);
    });

    // ========================================
    // Order Summary
    // ========================================

    function populateOrderSummary() {
        var formData = $('#pbay-shipping-form').serializeArray();
        var shippingParts = [];

        formData.forEach(function (f) {
            if (f.name === 'shipping_address_1' && f.value) shippingParts.push(f.value);
            if (f.name === 'shipping_city' && f.value) shippingParts.push(f.value);
            if (f.name === 'shipping_state' && f.value) shippingParts.push(f.value);
            if (f.name === 'shipping_postal' && f.value) shippingParts.push(f.value);
            if (f.name === 'shipping_country' && f.value) shippingParts.push(f.value);
        });

        var $productDetail = $('.pbay-product-detail');
        var productTitle = $productDetail.find('.pbay-product-title').text() || 'Product';

        $('#pbay-summary-product').text(productTitle);
        $('#pbay-summary-price-usd').text('$' + parseFloat(currentPriceUsd).toFixed(2));
        $('#pbay-summary-shipping').text(shippingParts.join(', ') || 'N/A');

        // Fetch live ADA price for summary
        $.post(pbayCheckout.ajaxurl, {
            action: 'pbay_get_ada_price',
            nonce: pbayCheckout.nonce,
        }, function (response) {
            if (response.success && response.data.ada_usd) {
                var adaPrice = parseFloat(response.data.ada_usd);
                var adaAmount = (currentPriceUsd / adaPrice).toFixed(2);
                $('#pbay-summary-price-ada').text(adaAmount + ' ADA');
            }
        });
    }

    // ========================================
    // Confirm & Pay
    // ========================================

    $(document).on('click', '#pbay-confirm-pay', function () {
        if (!walletApi || !connectedAddress) {
            alert('Wallet not connected');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        // Move to processing step
        goToCheckoutStep(4);
        $('#pbay-processing-status').text('Building Transaction...');
        $('#pbay-processing-message').text('Calculating payment amount and building the transaction.');

        // Collect shipping data
        var formData = {};
        $('#pbay-shipping-form').serializeArray().forEach(function (f) {
            formData[f.name] = f.value;
        });

        // Step 1: Build TX on server (Anvil looks up UTXOs on-chain)
        $.post(pbayCheckout.ajaxurl, {
            action: 'pbay_build_payment_tx',
            nonce: pbayCheckout.nonce,
            listing_id: currentListingId,
            buyer_address: connectedAddress,
        }, function (buildResp) {
                if (!buildResp.success) {
                    showCheckoutError('Failed to build transaction: ' + (buildResp.data.message || 'Unknown error'));
                    return;
                }

                unsignedTx = buildResp.data.transaction;

                if (!unsignedTx) {
                    showCheckoutError('No transaction returned from server.');
                    return;
                }

                // Step 2: Sign in wallet via CIP-30 (partial sign)
                $('#pbay-processing-status').text('Awaiting Wallet Signature...');
                $('#pbay-processing-message').text('Please approve the transaction in your wallet popup.');

                walletApi.signTx(unsignedTx, true).then(function (witnessSet) {
                    // Step 3: Submit
                    $('#pbay-processing-status').text('Submitting Transaction...');
                    $('#pbay-processing-message').text('Broadcasting your payment to the Cardano network.');

                    var submitData = {
                        action: 'pbay_submit_payment',
                        nonce: pbayCheckout.nonce,
                        listing_id: currentListingId,
                        transaction: unsignedTx,
                        signature: witnessSet,
                        buyer_address: connectedAddress,
                    };

                    // Add shipping fields
                    Object.keys(formData).forEach(function (key) {
                        submitData[key] = formData[key];
                    });

                    $.post(pbayCheckout.ajaxurl, submitData, function (submitResp) {
                        if (submitResp.success) {
                            showCheckoutSuccess(submitResp.data);
                        } else {
                            showCheckoutError('Transaction submission failed: ' + (submitResp.data.message || 'Unknown'));
                        }
                    }).fail(function () {
                        showCheckoutError('Network error during transaction submission.');
                    });

                }).catch(function (err) {
                    var msg = err.message || err.info || 'Wallet signing declined or failed';
                    showCheckoutError(msg);
                });

            }).fail(function () {
                showCheckoutError('Network error while building transaction.');
            });
    });

    function showCheckoutError(message) {
        $('#pbay-processing-status').text('Transaction Failed');
        $('#pbay-processing-message').html(
            '<p style="color:#ff4d6a;">' + message + '</p>' +
            '<button type="button" class="pbay-btn pbay-btn-primary" onclick="jQuery(\'.pbay-checkout-step[data-checkout-step=3]\').show();jQuery(\'.pbay-checkout-step[data-checkout-step=4]\').hide();">Try Again</button>'
        );
    }

    function showCheckoutSuccess(data) {
        goToCheckoutStep(5);

        $('#pbay-confirm-order-id').text(data.order_id || 'N/A');

        var txHash = data.tx_hash || '';
        $('#pbay-confirm-tx-hash').text(txHash.substring(0, 20) + '...' + txHash.substring(txHash.length - 8));
        $('#pbay-confirm-tx-link').attr('href', data.explorer_url || '#');

        // Show NFT delivery info if available
        if (data.nft_delivery_tx_hash) {
            var nftHash = data.nft_delivery_tx_hash;
            $('#pbay-nft-delivery-tx-hash').text(nftHash.substring(0, 20) + '...' + nftHash.substring(nftHash.length - 8));
            $('#pbay-nft-delivery-tx-link').attr('href', data.nft_delivery_explorer_url || '#');
            $('#pbay-nft-delivery-info').show();
        }
    }

    // ========================================
    // Order Lookup (order-history page)
    // ========================================

    $(document).on('click', '#pbay-lookup-btn', function () {
        var query = $('#pbay-lookup-query').val().trim();
        if (!query) {
            alert('Please enter an Order ID or TX Hash');
            return;
        }

        var $result = $('#pbay-lookup-result');
        $result.show().html('<p>Looking up...</p>');

        $.post(pbayCheckout.ajaxurl, {
            action: 'pbay_lookup_order',
            nonce: pbayCheckout.nonce,
            query: query,
        }, function (response) {
            if (response.success) {
                var o = response.data.order;
                var html = '<h4>Order Found</h4>';
                html += '<table style="width:100%;">';
                html += '<tr><td><strong>Order ID:</strong></td><td>' + (o.order_id || '') + '</td></tr>';
                html += '<tr><td><strong>Product:</strong></td><td>' + (response.data.listing_title || '') + '</td></tr>';
                html += '<tr><td><strong>Status:</strong></td><td>' + (o.status || '') + '</td></tr>';
                html += '<tr><td><strong>Amount:</strong></td><td>$' + parseFloat(o.price_usd).toFixed(2) + ' (' + parseFloat(o.price_ada).toFixed(2) + ' ADA)</td></tr>';
                if (o.tx_hash) {
                    html += '<tr><td><strong>TX:</strong></td><td><a href="' + pbayCheckout.explorer_url + '/transaction/' + o.tx_hash + '" target="_blank">' + o.tx_hash.substring(0, 24) + '...</a></td></tr>';
                }
                if (o.tracking_number) {
                    html += '<tr><td><strong>Tracking:</strong></td><td>' + (o.tracking_carrier || '') + ' ' + o.tracking_number + '</td></tr>';
                }
                html += '<tr><td><strong>Date:</strong></td><td>' + (o.created_at || '') + '</td></tr>';
                html += '</table>';
                $result.html(html);
            } else {
                $result.html('<p style="color:#ff4d6a;">' + (response.data.message || 'Not found') + '</p>');
            }
        }).fail(function () {
            $result.html('<p style="color:#ff4d6a;">Network error. Try again.</p>');
        });
    });

    // ========================================
    // Wallet-Connected Order History
    // ========================================

    $(document).on('click', '#pbay-connect-for-orders', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Connecting...');

        getCardanoPressWallet().then(function (api) {
            if (!api) {
                alert('No wallet connected. Please connect your wallet using the button in the site header.');
                $btn.prop('disabled', false).text('Connect Wallet to View Orders');
                return;
            }

            return getWalletAddress(api).then(function (address) {
                fetchBuyerOrders(address);
                $btn.text('Connected').prop('disabled', true);
                $('#pbay-wallet-orders').show();
            });
        }).catch(function (err) {
            alert('Failed to connect: ' + (err.message || 'Unknown'));
            $btn.prop('disabled', false).text('Connect Wallet to View Orders');
        });
    });

    function fetchBuyerOrders(address) {
        var $list = $('#pbay-orders-list');
        $list.html('<div class="pbay-spinner" style="margin:2rem auto;"></div>');

        $.post(pbayCheckout.ajaxurl, {
            action: 'pbay_get_buyer_orders',
            nonce: pbayCheckout.nonce,
            buyer_address: address,
        }, function (response) {
            if (response.success && response.data.orders.length > 0) {
                var html = '';
                response.data.orders.forEach(function (o) {
                    var statusClass = 'pbay-status-' + o.status;
                    var statusLabel = o.status.charAt(0).toUpperCase() + o.status.slice(1);
                    var date = (o.created_at || '').substring(0, 10);

                    html += '<div class="pbay-order-card">';
                    html += '<div class="pbay-order-card-header">';

                    // Product image
                    if (o.listing_image_url) {
                        html += '<img src="' + escHtml(o.listing_image_url) + '" alt="" class="pbay-order-card-img" />';
                    } else {
                        html += '<div class="pbay-order-card-img pbay-order-card-img-placeholder"></div>';
                    }

                    html += '<div class="pbay-order-card-title-block">';
                    html += '<h4>' + escHtml(o.listing_title || 'Product') + '</h4>';
                    html += '<span class="pbay-order-card-id">' + escHtml(o.order_id) + '</span>';
                    html += '</div>';
                    html += '<span class="pbay-order-card-status ' + statusClass + '">' + statusLabel + '</span>';
                    html += '</div>'; // header

                    html += '<div class="pbay-order-card-body">';
                    html += '<div class="pbay-order-card-row"><span>Price</span><strong>$' + parseFloat(o.price_usd).toFixed(2) + ' (' + parseFloat(o.price_ada).toFixed(2) + ' ADA)</strong></div>';
                    html += '<div class="pbay-order-card-row"><span>Date</span><span>' + date + '</span></div>';

                    // Tracking
                    if (o.tracking_number) {
                        html += '<div class="pbay-order-card-row"><span>Tracking</span><span>' + escHtml(o.tracking_carrier || '') + ' ' + escHtml(o.tracking_number) + '</span></div>';
                    }

                    // Payment TX
                    if (o.explorer_url) {
                        var shortTx = o.tx_hash ? o.tx_hash.substring(0, 16) + '...' : '';
                        html += '<div class="pbay-order-card-row"><span>Payment TX</span><a href="' + escHtml(o.explorer_url) + '" target="_blank">' + shortTx + '</a></div>';
                    }

                    // NFT Delivery TX
                    if (o.nft_delivery_explorer_url) {
                        var shortNft = o.nft_delivery_tx_hash ? o.nft_delivery_tx_hash.substring(0, 16) + '...' : '';
                        html += '<div class="pbay-order-card-row"><span>NFT Delivery</span><a href="' + escHtml(o.nft_delivery_explorer_url) + '" target="_blank">' + shortNft + '</a></div>';
                    } else if (o.listing_policy_id) {
                        html += '<div class="pbay-order-card-row"><span>NFT Delivery</span><span style="color:rgba(255,255,255,0.4);">Pending</span></div>';
                    }

                    html += '</div>'; // body
                    html += '</div>'; // card
                });
                $list.html(html);
            } else {
                $list.html('<p style="text-align:center;color:rgba(255,255,255,0.5);">No orders found for this wallet.</p>');
            }
        }).fail(function () {
            $list.html('<p style="color:#ff4d6a;">Failed to load orders.</p>');
        });
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ========================================
    // Init
    // ========================================

    $(document).ready(function () {
        fetchAndDisplayAdaPrice();

        // Auto-connect wallet on orders page if CardanoPress wallet is detected
        if ($('#pbay-wallet-orders').length) {
            var connected = localStorage.getItem('_x_connectedExtension');
            if (connected) {
                $('#pbay-connect-for-orders').prop('disabled', true).text('Connecting...');
                getCardanoPressWallet().then(function (api) {
                    if (api) {
                        return getWalletAddress(api).then(function (address) {
                            fetchBuyerOrders(address);
                            $('#pbay-connect-for-orders').text('Wallet Connected').prop('disabled', true);
                        });
                    } else {
                        $('#pbay-connect-for-orders').prop('disabled', false).text('Connect Wallet to View Orders');
                    }
                }).catch(function () {
                    $('#pbay-connect-for-orders').prop('disabled', false).text('Connect Wallet to View Orders');
                });
            }
        }
    });

})(jQuery);
