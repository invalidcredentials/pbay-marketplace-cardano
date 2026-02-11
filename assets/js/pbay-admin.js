/**
 * PBay Admin JavaScript
 * Handles: Wizard navigation, image upload, IPFS pinning, dynamic meta attributes,
 * policy generation, NFT minting, inventory actions, order management, setup tests.
 */
(function ($) {
    'use strict';

    // ========================================
    // Wizard Navigation
    // ========================================

    var currentStep = 1;
    var totalSteps = 6;

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

        currentStep = step;

        // Update step indicators
        $('.pbay-step').each(function () {
            var s = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            if (s === currentStep) {
                $(this).addClass('active');
            } else if (s < currentStep) {
                $(this).addClass('completed');
            }
        });

        // Show correct panel
        $('.pbay-wizard-panel').removeClass('active');
        $('.pbay-wizard-panel[data-step="' + currentStep + '"]').addClass('active');

        // Update nav buttons
        $('#pbay-prev-step').toggle(currentStep > 1);
        $('#pbay-next-step').toggle(currentStep < totalSteps);
        $('#pbay-save-draft').toggle(currentStep < totalSteps);
        $('#pbay-publish').toggle(currentStep === totalSteps);

        // Build review on last step
        if (currentStep === totalSteps) {
            buildReview();
        }
    }

    $(document).on('click', '#pbay-next-step', function () {
        if (currentStep === 2 && !$('#pbay-title').val().trim()) {
            alert('Title is required.');
            return;
        }
        if (currentStep === 3 && (!$('#pbay-price').val() || parseFloat($('#pbay-price').val()) <= 0)) {
            alert('Please enter a valid price.');
            return;
        }

        // Auto-save as draft when leaving step 5 (Shipping) → step 6 (Review)
        if (currentStep === 5) {
            autoSaveDraft(function () {
                goToStep(6);
            });
            return;
        }

        goToStep(currentStep + 1);
    });

    $(document).on('click', '#pbay-prev-step', function () {
        goToStep(currentStep - 1);
    });

    $(document).on('click', '.pbay-step', function () {
        var step = parseInt($(this).data('step'));
        if (step <= currentStep || $(this).hasClass('completed')) {
            goToStep(step);
        }
    });

    // ========================================
    // Image Upload (WP Media Library)
    // ========================================

    var mediaFrame;

    $(document).on('click', '#pbay-upload-image, .pbay-placeholder', function (e) {
        e.preventDefault();

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: 'Select Product Image',
            button: { text: 'Use This Image' },
            multiple: false,
        });

        mediaFrame.on('select', function () {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            $('#pbay-image-id').val(attachment.id);
            $('#pbay-image-preview').html('<img src="' + attachment.url + '" style="max-width:300px;border-radius:8px;" />');
            $('#pbay-remove-image').show();
            $('#pbay-pin-ipfs').prop('disabled', false);
        });

        mediaFrame.open();
    });

    $(document).on('click', '#pbay-remove-image', function () {
        $('#pbay-image-id').val('');
        $('#pbay-image-preview').html('<div class="pbay-placeholder">Click or drag image here</div>');
        $(this).hide();
        $('#pbay-pin-ipfs').prop('disabled', true);
    });

    // ========================================
    // Drag & Drop Image Upload
    // ========================================

    $(document).on('dragover dragenter', '#pbay-image-preview', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).find('.pbay-placeholder').addClass('pbay-drag-over');
    });

    $(document).on('dragleave', '#pbay-image-preview', function (e) {
        e.preventDefault();
        $(this).find('.pbay-placeholder').removeClass('pbay-drag-over');
    });

    $(document).on('drop', '#pbay-image-preview', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).find('.pbay-placeholder').removeClass('pbay-drag-over');

        var files = e.originalEvent.dataTransfer.files;
        if (!files.length) return;

        var file = files[0];
        if (!file.type.match(/^image\//)) {
            alert('Please drop an image file.');
            return;
        }

        var $preview = $('#pbay-image-preview');
        $preview.html('<div class="pbay-placeholder">Uploading...</div>');

        // Get WP media nonce (set by wp_enqueue_media)
        var wpNonce = '';
        if (typeof _wpPluploadSettings !== 'undefined') {
            wpNonce = _wpPluploadSettings.defaults.multipart_params._wpnonce;
        }

        var formData = new FormData();
        formData.append('action', 'upload-attachment');
        formData.append('_wpnonce', wpNonce);
        formData.append('async-upload', file);
        formData.append('name', file.name);

        $.ajax({
            url: pbayAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success && response.data) {
                    var att = response.data;
                    $('#pbay-image-id').val(att.id);
                    var imgUrl = att.url || '';
                    if (att.sizes && att.sizes.medium) {
                        imgUrl = att.sizes.medium.url;
                    } else if (att.sizes && att.sizes.full) {
                        imgUrl = att.sizes.full.url;
                    }
                    $preview.html('<img src="' + imgUrl + '" style="max-width:300px;border-radius:8px;" />');
                    $('#pbay-remove-image').show();
                    $('#pbay-pin-ipfs').prop('disabled', false);
                } else {
                    $preview.html('<div class="pbay-placeholder">Upload failed. Click to try again.</div>');
                }
            },
            error: function () {
                $preview.html('<div class="pbay-placeholder">Upload failed. Click to try again.</div>');
            }
        });
    });

    // ========================================
    // Gallery Images (up to 3)
    // ========================================

    var galleryFrame;

    function getGalleryIds() {
        return ($('#pbay-gallery-ids').val() || '').split(',').filter(function (id) { return id.trim() !== ''; });
    }

    function updateGalleryIds(ids) {
        $('#pbay-gallery-ids').val(ids.join(','));
        // Show/hide add button based on count
        if (ids.length >= 3) {
            $('#pbay-add-gallery').hide();
        } else {
            $('#pbay-add-gallery').show();
        }
    }

    $(document).on('click', '#pbay-add-gallery', function (e) {
        e.preventDefault();

        var currentIds = getGalleryIds();
        if (currentIds.length >= 3) {
            alert('Maximum 3 additional images.');
            return;
        }

        if (galleryFrame) {
            galleryFrame.open();
            return;
        }

        galleryFrame = wp.media({
            title: 'Select Additional Image',
            button: { text: 'Add Image' },
            multiple: false,
        });

        galleryFrame.on('select', function () {
            var attachment = galleryFrame.state().get('selection').first().toJSON();
            var ids = getGalleryIds();

            if (ids.length >= 3) {
                alert('Maximum 3 additional images.');
                return;
            }

            if (ids.indexOf(String(attachment.id)) !== -1) return; // duplicate

            ids.push(String(attachment.id));
            updateGalleryIds(ids);

            var thumbUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            var html = '<div class="pbay-gallery-item" data-id="' + attachment.id + '">';
            html += '<img src="' + thumbUrl + '" />';
            html += '<button type="button" class="pbay-gallery-remove">&times;</button>';
            html += '</div>';
            $('#pbay-gallery-preview').append(html);
        });

        galleryFrame.open();
    });

    $(document).on('click', '.pbay-gallery-remove', function () {
        var $item = $(this).closest('.pbay-gallery-item');
        var removeId = String($item.data('id'));
        $item.remove();

        var ids = getGalleryIds().filter(function (id) { return id !== removeId; });
        updateGalleryIds(ids);

        // Reset frame so next open creates fresh
        galleryFrame = null;
    });

    // ========================================
    // IPFS Pinning
    // ========================================

    $(document).on('click', '#pbay-pin-ipfs', function () {
        var imageId = $('#pbay-image-id').val();
        if (!imageId) {
            alert('Upload an image first.');
            return;
        }

        var $btn = $(this);
        var $status = $('#pbay-ipfs-status');

        $btn.prop('disabled', true);
        $status.text('Pinning to IPFS...');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_pin_image_ipfs',
            nonce: pbayAdmin.nonce,
            image_id: imageId,
            name: $('#pbay-title').val() || 'product-image',
        }, function (response) {
            if (response.success) {
                $('#pbay-ipfs-cid').val(response.data.cid);
                $status.html('Pinned! CID: <code>' + response.data.cid + '</code>');
            } else {
                $status.text('Error: ' + (response.data.message || 'Unknown error'));
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            $status.text('Network error. Try again.');
            $btn.prop('disabled', false);
        });
    });

    // ========================================
    // Dynamic Meta Attributes
    // ========================================

    $(document).on('click', '#pbay-add-meta', function () {
        var html = '<div class="pbay-meta-row">' +
            '<input type="text" name="meta_keys[]" placeholder="Attribute name" />' +
            '<input type="text" name="meta_values[]" placeholder="Value" />' +
            '<button type="button" class="button pbay-remove-meta">X</button>' +
            '</div>';
        $('#pbay-meta-rows').append(html);
    });

    $(document).on('click', '.pbay-remove-meta', function () {
        $(this).closest('.pbay-meta-row').remove();
    });

    // ========================================
    // ADA Price Conversion
    // ========================================

    var adaPrice = null;

    function fetchAdaPrice() {
        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_get_ada_price',
            nonce: pbayAdmin.nonce,
        }, function (response) {
            if (response.success && response.data.ada_usd) {
                adaPrice = parseFloat(response.data.ada_usd);
                updateAdaEquiv();
            }
        });
    }

    function updateAdaEquiv() {
        if (!adaPrice) return;
        var usd = parseFloat($('#pbay-price').val()) || 0;
        if (usd > 0) {
            var ada = (usd / adaPrice).toFixed(2);
            $('#pbay-ada-equiv').text('≈ ' + ada + ' ADA @ $' + adaPrice.toFixed(4) + '/ADA');
        } else {
            $('#pbay-ada-equiv').text('');
        }
    }

    $(document).on('input', '#pbay-price', updateAdaEquiv);

    // ========================================
    // Build Review Panel
    // ========================================

    function buildReview() {
        var categoryText = $('#pbay-category option:selected').text() || '-';
        if ($('#pbay-category').val() === '') categoryText = '-';

        var html = '<table class="form-table">';
        html += '<tr><th>Title</th><td>' + escHtml($('#pbay-title').val()) + '</td></tr>';
        html += '<tr><th>Category</th><td>' + escHtml(categoryText) + '</td></tr>';
        html += '<tr><th>Condition</th><td>' + escHtml($('#pbay-condition').val() || '-') + '</td></tr>';
        html += '<tr><th>Price</th><td>$' + escHtml($('#pbay-price').val()) + '</td></tr>';
        html += '<tr><th>Quantity</th><td>' + escHtml($('#pbay-quantity').val()) + '</td></tr>';

        if ($('#pbay-image-id').val()) {
            html += '<tr><th>Image</th><td>Selected (ID: ' + escHtml($('#pbay-image-id').val()) + ')</td></tr>';
        }

        var ipfsCid = $('#pbay-ipfs-cid').val() || $('#pbay-ipfs-cid-manual').val();
        if (ipfsCid) {
            html += '<tr><th>IPFS CID</th><td><code>' + escHtml(ipfsCid) + '</code></td></tr>';
        }

        html += '</table>';

        // Meta attributes
        var metaRows = [];
        $('.pbay-meta-row').each(function () {
            var key = $(this).find('input[name="meta_keys[]"]').val();
            var val = $(this).find('input[name="meta_values[]"]').val();
            if (key) metaRows.push({ key: key, value: val });
        });

        if (metaRows.length > 0) {
            html += '<h3>Custom Attributes</h3><table class="form-table">';
            metaRows.forEach(function (m) {
                html += '<tr><th>' + escHtml(m.key) + '</th><td>' + escHtml(m.value) + '</td></tr>';
            });
            html += '</table>';
        }

        $('#pbay-review-content').html(html);

        // Build metadata JSON preview
        var metadata = buildMetadataPreview(metaRows, ipfsCid);
        $('#pbay-metadata-json').text(JSON.stringify(metadata, null, 2));
    }

    function buildMetadataPreview(metaRows, ipfsCid) {
        var title = $('#pbay-title').val() || 'Untitled';
        var desc = $('#pbay-description').val() || '';
        var image = ipfsCid ? ('ipfs://' + ipfsCid) : 'pending';
        var categoryText = $('#pbay-category option:selected').text() || '';
        if ($('#pbay-category').val() === '') categoryText = '';

        var nft = {
            name: title.substring(0, 64),
            image: image,
            mediaType: 'image/jpeg',
            description: desc.length > 64 ? chunkText(desc) : desc,
            priceUSD: $('#pbay-price').val() || '0',
            category: categoryText,
            condition: $('#pbay-condition').val() || '',
            quantity: $('#pbay-quantity').val() || '1',
        };

        metaRows.forEach(function (m) {
            var key = 'attr_' + m.key.replace(/[^a-zA-Z0-9_]/g, '_');
            nft[key] = m.value.substring(0, 64);
        });

        return { '721': { '<policy_id>': { '<asset_name>': nft } } };
    }

    function chunkText(text) {
        var chunks = [];
        for (var i = 0; i < text.length; i += 64) {
            chunks.push(text.substring(i, i + 64));
        }
        return chunks;
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ========================================
    // Save Draft
    // ========================================

    $(document).on('click', '#pbay-save-draft', function () {
        saveListing('draft');
    });

    /**
     * Auto-save as draft with callback on success.
     * Shows a brief inline status, then fires the callback.
     */
    function autoSaveDraft(callback) {
        var $messages = $('#pbay-listing-messages');
        $messages.html('<p>Auto-saving draft...</p>');

        var $form = $('#pbay-listing-form');
        var data = $form.serializeArray();
        data.push({ name: 'action', value: 'pbay_save_listing' });
        data.push({ name: 'nonce', value: pbayAdmin.nonce });
        data.push({ name: 'status', value: 'draft' });

        $.post(pbayAdmin.ajaxurl, $.param(data), function (response) {
            if (response.success) {
                $('input[name="listing_id"]').val(response.data.listing_id);
                $messages.html('<div class="notice notice-success"><p>Draft saved.</p></div>');
                if (callback) callback();
            } else {
                $messages.html('<div class="notice notice-error"><p>' + (response.data.message || 'Auto-save failed') + '</p></div>');
            }
        }).fail(function () {
            $messages.html('<div class="notice notice-error"><p>Network error during auto-save.</p></div>');
        });
    }

    function saveListing(status) {
        var $form = $('#pbay-listing-form');
        var data = $form.serializeArray();
        data.push({ name: 'action', value: 'pbay_save_listing' });
        data.push({ name: 'nonce', value: pbayAdmin.nonce });
        data.push({ name: 'status', value: status || 'draft' });

        $('#pbay-listing-messages').html('<p>Saving...</p>');

        $.post(pbayAdmin.ajaxurl, $.param(data), function (response) {
            if (response.success) {
                $('#pbay-listing-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                // Update hidden listing_id for subsequent saves
                $('input[name="listing_id"]').val(response.data.listing_id);
            } else {
                $('#pbay-listing-messages').html('<div class="notice notice-error"><p>' + (response.data.message || 'Save failed') + '</p></div>');
            }
        }).fail(function () {
            $('#pbay-listing-messages').html('<div class="notice notice-error"><p>Network error. Try again.</p></div>');
        });
    }

    // ========================================
    // Publish (Mint NFT)
    // ========================================

    $(document).on('click', '#pbay-publish', function () {
        var listingId = $('input[name="listing_id"]').val();

        if (!listingId || listingId === '0') {
            alert('Save as draft first before publishing.');
            return;
        }

        // Validate category is selected
        var $catSelect = $('[name="category_id"]');
        if ($catSelect.length && !$catSelect.val()) {
            alert('Please select a category before publishing.');
            return;
        }

        if (!confirm('This will:\n1. Save the listing\n2. Mint the NFT on-chain\n\nEnsure your policy wallet has at least 5 ADA.\n\nContinue?')) {
            return;
        }

        var $btn = $(this);
        var $messages = $('#pbay-listing-messages');
        $btn.prop('disabled', true);

        // Step 1: Save listing
        $messages.html('<p>Step 1/2: Saving listing...</p>');

        var formData = $('#pbay-listing-form').serializeArray();
        formData.push({ name: 'action', value: 'pbay_save_listing' });
        formData.push({ name: 'nonce', value: pbayAdmin.nonce });
        formData.push({ name: 'status', value: 'draft' });

        $.post(pbayAdmin.ajaxurl, $.param(formData), function (saveResp) {
            if (!saveResp.success) {
                $messages.html('<div class="notice notice-error"><p>Save failed: ' + (saveResp.data.message || 'Unknown') + '</p></div>');
                $btn.prop('disabled', false);
                return;
            }

            var lid = saveResp.data.listing_id;
            $('input[name="listing_id"]').val(lid);

            // Step 2: Mint NFT (policy sourced from category server-side)
            $messages.html('<p>Step 2/2: Minting NFT on-chain...</p>');

            $.post(pbayAdmin.ajaxurl, {
                action: 'pbay_mint_listing_nft',
                nonce: pbayAdmin.nonce,
                listing_id: lid,
            }, function (mintResp) {
                if (mintResp.success) {
                    $messages.html(
                        '<div class="notice notice-success"><p>' + mintResp.data.message + '</p>' +
                        '<p>TX Hash: <a href="' + mintResp.data.explorer_url + '" target="_blank"><code>' + mintResp.data.tx_hash + '</code></a></p></div>'
                    );
                } else {
                    $messages.html('<div class="notice notice-error"><p>Mint failed: ' + (mintResp.data.message || 'Unknown') + '</p></div>');
                }
                $btn.prop('disabled', false);
            }).fail(function () {
                $messages.html('<div class="notice notice-error"><p>Network error during minting.</p></div>');
                $btn.prop('disabled', false);
            });
        }).fail(function () {
            $messages.html('<div class="notice notice-error"><p>Network error during save.</p></div>');
            $btn.prop('disabled', false);
        });
    });

    // ========================================
    // Inventory Page Actions
    // ========================================

    // Publish from inventory (policy sourced from category server-side)
    $(document).on('click', '.pbay-mint-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Publish and mint NFT for listing #' + id + '?\nEnsure policy wallet has at least 5 ADA.')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text('Minting...');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_mint_listing_nft',
            nonce: pbayAdmin.nonce,
            listing_id: id,
        }, function (mintResp) {
            if (mintResp.success) {
                alert(mintResp.data.message + '\nTX: ' + mintResp.data.tx_hash);
                location.reload();
            } else {
                alert('Mint failed: ' + (mintResp.data.message || 'Unknown'));
                $btn.prop('disabled', false).text('Publish');
            }
        }).fail(function () {
            alert('Network error during minting.');
            $btn.prop('disabled', false).text('Publish');
        });
    });

    // Archive from inventory
    $(document).on('click', '.pbay-archive-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Archive listing #' + id + '?')) return;

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_archive_listing',
            nonce: pbayAdmin.nonce,
            listing_id: id,
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Failed to archive');
            }
        });
    });

    // Delete from inventory
    $(document).on('click', '.pbay-delete-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Permanently delete listing #' + id + '? This cannot be undone.')) return;

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_delete_listing',
            nonce: pbayAdmin.nonce,
            listing_id: id,
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Failed to delete');
            }
        });
    });

    // ========================================
    // Order Detail Page
    // ========================================

    // Update order status
    $(document).on('click', '#pbay-update-status', function () {
        var orderId = $('#pbay-order-status').data('order-id');
        var status = $('#pbay-order-status').val();

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_update_order_status',
            nonce: pbayAdmin.nonce,
            order_id: orderId,
            status: status,
        }, function (response) {
            if (response.success) {
                alert('Status updated to: ' + status);
            } else {
                alert(response.data.message || 'Failed to update status');
            }
        });
    });

    // Update tracking
    $(document).on('click', '#pbay-update-tracking', function () {
        var orderId = $(this).data('order-id');
        var carrier = $('#pbay-tracking-carrier').val();
        var number = $('#pbay-tracking-number').val();

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_update_tracking',
            nonce: pbayAdmin.nonce,
            order_id: orderId,
            tracking_carrier: carrier,
            tracking_number: number,
        }, function (response) {
            if (response.success) {
                alert('Tracking info saved.');
            } else {
                alert(response.data.message || 'Failed to save tracking');
            }
        });
    });

    // Send NFT to buyer (manual fallback)
    $(document).on('click', '#pbay-send-nft', function () {
        var orderId = $(this).data('order-id');
        if (!confirm('Send the NFT to the buyer\'s wallet?\n\nThis will transfer the NFT from the policy wallet and requires ~2 ADA in the policy wallet.')) {
            return;
        }

        var $btn = $(this);
        var $status = $('#pbay-nft-send-status');
        $btn.prop('disabled', true);
        $status.text('Building & submitting transfer TX...');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_send_nft',
            nonce: pbayAdmin.nonce,
            order_id: orderId,
        }, function (response) {
            if (response.success) {
                $status.html('<span style="color:#00a32a;">Delivered!</span> TX: <a href="' + response.data.explorer_url + '" target="_blank"><code>' + response.data.tx_hash.substring(0, 24) + '...</code></a>');
                $btn.hide();
            } else {
                $status.html('<span style="color:#d63638;">' + (response.data.message || 'Failed') + '</span>');
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            $status.html('<span style="color:#d63638;">Network error</span>');
            $btn.prop('disabled', false);
        });
    });

    // Export CSV
    $(document).on('click', '#pbay-export-csv', function () {
        window.location.href = pbayAdmin.ajaxurl + '?action=pbay_export_orders_csv&nonce=' + pbayAdmin.nonce;
    });

    // ========================================
    // Setup Page: Test Connections
    // ========================================

    $(document).on('click', '#pbay-test-anvil', function () {
        var $result = $('#pbay-anvil-result');
        $result.text('Testing...');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_test_anvil',
            nonce: pbayAdmin.nonce,
        }, function (response) {
            if (response.success) {
                $result.css('color', '#00a32a').text('Connected!');
            } else {
                $result.css('color', '#d63638').text('Failed: ' + (response.data.message || 'Connection error'));
            }
        }).fail(function () {
            $result.css('color', '#d63638').text('Network error');
        });
    });

    $(document).on('click', '#pbay-test-pinata', function () {
        var $result = $('#pbay-pinata-result');
        $result.text('Testing...');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_test_pinata',
            nonce: pbayAdmin.nonce,
        }, function (response) {
            if (response.success) {
                $result.css('color', '#00a32a').text('Connected!');
            } else {
                $result.css('color', '#d63638').text('Failed: ' + (response.data.message || 'Connection error'));
            }
        }).fail(function () {
            $result.css('color', '#d63638').text('Network error');
        });
    });

    // ========================================
    // Listing Categories Page
    // ========================================

    $(document).on('click', '#pbay-create-category', function () {
        var name = $('#pbay-cat-name').val().trim();
        if (!name) {
            alert('Category name is required.');
            return;
        }

        var $btn = $(this);
        var $spinner = $('#pbay-cat-spinner');
        var $messages = $('#pbay-category-messages');

        $btn.prop('disabled', true);
        $spinner.show();
        $messages.html('');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_create_category',
            nonce: pbayAdmin.nonce,
            name: name,
            description: $('#pbay-cat-description').val().trim(),
        }, function (response) {
            if (response.success) {
                $messages.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                // Reload to show new category in table
                setTimeout(function () { location.reload(); }, 800);
            } else {
                $messages.html('<div class="notice notice-error"><p>' + (response.data.message || 'Failed to create category') + '</p></div>');
                $btn.prop('disabled', false);
                $spinner.hide();
            }
        }).fail(function () {
            $messages.html('<div class="notice notice-error"><p>Network error. Try again.</p></div>');
            $btn.prop('disabled', false);
            $spinner.hide();
        });
    });

    $(document).on('click', '.pbay-delete-category', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Delete category "' + name + '"? This cannot be undone.')) return;

        var $btn = $(this);
        var $messages = $('#pbay-category-messages');
        $btn.prop('disabled', true);

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_delete_category',
            nonce: pbayAdmin.nonce,
            category_id: id,
        }, function (response) {
            if (response.success) {
                $messages.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                location.reload();
            } else {
                $messages.html('<div class="notice notice-error"><p>' + (response.data.message || 'Failed to delete') + '</p></div>');
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            $messages.html('<div class="notice notice-error"><p>Network error. Try again.</p></div>');
            $btn.prop('disabled', false);
        });
    });

    // ========================================
    // Wallet Page: Balance
    // ========================================

    function fetchWalletBalance() {
        var $container = $('#pbay-wallet-balance');
        if (!$container.length) return;

        // Don't fetch if no-key message is showing
        if ($container.find('.pbay-balance-no-key').length) return;

        $container.html('<div class="pbay-balance-loading">Loading balance...</div>');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_get_wallet_balance',
            nonce: pbayAdmin.nonce,
        }, function (response) {
            if (response.success) {
                renderWalletBalance(response.data);
            } else {
                if (response.data && response.data.message === 'no_blockfrost_key') {
                    $container.html('<div class="pbay-balance-no-key">Configure a <a href="' + pbayAdmin.ajaxurl.replace('admin-ajax.php', 'admin.php?page=pbay-setup') + '">Blockfrost API key</a> in Setup to view balance.</div>');
                } else {
                    $container.html('<div class="pbay-balance-error">Unable to fetch balance. <a href="#" id="pbay-retry-balance">Retry</a></div>');
                }
            }
        }).fail(function () {
            $container.html('<div class="pbay-balance-error">Unable to fetch balance. <a href="#" id="pbay-retry-balance">Retry</a></div>');
        });
    }

    function renderWalletBalance(data) {
        var $container = $('#pbay-wallet-balance');
        var lovelace = parseInt(data.lovelace) || 0;
        var ada = (lovelace / 1000000).toFixed(6);
        var assets = data.assets || [];
        _walletAssets = assets;

        var html = '<div class="pbay-balance-ada">' + ada + ' ADA</div>';

        if (assets.length > 0) {
            html += '<div class="pbay-balance-assets-summary">' + assets.length + ' token' + (assets.length > 1 ? 's' : '') + ' / NFT' + (assets.length > 1 ? 's' : '') + ' ';
            html += '<a href="#" class="pbay-toggle-assets">Show</a></div>';
            html += '<div class="pbay-balance-assets-list" style="display:none;">';
            html += '<div class="pbay-assets-grid">';
            for (var i = 0; i < assets.length; i++) {
                var a = assets[i];
                var displayName = a.asset_name || a.asset_name_hex.substring(0, 16) + '...';
                var shortPolicy = a.policy_id.substring(0, 12) + '...';
                var imgHtml = a.image
                    ? '<img src="' + escHtml(a.image) + '" alt="' + escHtml(displayName) + '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" /><span class="pbay-asset-no-img" style="display:none;">NFT</span>'
                    : '<span class="pbay-asset-no-img">NFT</span>';
                html += '<div class="pbay-asset-tile" data-asset-idx="' + i + '">';
                html += '<div class="pbay-asset-thumb">' + imgHtml + '</div>';
                html += '<div class="pbay-asset-info">';
                html += '<div class="pbay-asset-name" title="' + escHtml(displayName) + '">' + escHtml(displayName) + '</div>';
                html += '<div class="pbay-asset-policy" title="' + escHtml(a.policy_id) + '"><code>' + escHtml(shortPolicy) + '</code></div>';
                if (parseInt(a.quantity) > 1) {
                    html += '<div class="pbay-asset-qty">x' + escHtml(a.quantity) + '</div>';
                }
                html += '</div></div>';
            }
            html += '</div>';

            // Hidden detail card (shared, repositioned on click)
            html += '<div class="pbay-asset-detail" id="pbay-asset-detail" style="display:none;"></div>';

            html += '</div>';
        }

        $container.html(html);
    }

    // Store asset data globally for detail card rendering
    var _walletAssets = [];

    $(document).on('click', '.pbay-toggle-assets', function (e) {
        e.preventDefault();
        var $list = $('.pbay-balance-assets-list');
        $list.toggle();
        $(this).text($list.is(':visible') ? 'Hide' : 'Show');
        // Collapse detail when hiding list
        if (!$list.is(':visible')) {
            $('#pbay-asset-detail').slideUp(150);
            $('.pbay-asset-tile').removeClass('active');
        }
    });

    // Click tile to expand/collapse detail card
    $(document).on('click', '.pbay-asset-tile', function () {
        var idx = parseInt($(this).data('asset-idx'));
        var a = _walletAssets[idx];
        if (!a) return;

        var $detail = $('#pbay-asset-detail');
        var $tile = $(this);
        var wasActive = $tile.hasClass('active');

        // Deselect all tiles
        $('.pbay-asset-tile').removeClass('active');

        if (wasActive) {
            $detail.slideUp(150);
            return;
        }

        $tile.addClass('active');

        // Position detail card right after the clicked tile's row
        $tile.after(''); // no-op, detail is at end of grid

        var html = '<div class="pbay-detail-inner">';

        // Header with larger image + name
        html += '<div class="pbay-detail-header">';
        if (a.image) {
            html += '<div class="pbay-detail-image"><img src="' + escHtml(a.image) + '" onerror="this.parentElement.style.display=\'none\'" /></div>';
        }
        html += '<div class="pbay-detail-title">';
        var detailName = a.asset_name || a.asset_name_hex;
        html += '<h3>' + escHtml(detailName) + '</h3>';
        if (a.fingerprint) {
            html += '<div class="pbay-detail-fingerprint"><code>' + escHtml(a.fingerprint) + '</code></div>';
        }
        html += '<div class="pbay-detail-ids">';
        html += '<div>Policy: <code>' + escHtml(a.policy_id) + '</code></div>';
        html += '<div>Asset (hex): <code>' + escHtml(a.asset_name_hex) + '</code></div>';
        html += '<div>Quantity held: <strong>' + escHtml(a.quantity) + '</strong></div>';
        if (a.mint_quantity) {
            html += '<div>Total minted: ' + escHtml(a.mint_quantity) + '</div>';
        }
        html += '</div></div></div>';

        // Metadata table
        var meta = a.metadata || {};
        var metaKeys = Object.keys(meta);
        if (metaKeys.length > 0) {
            html += '<div class="pbay-detail-meta">';
            html += '<h4>On-chain Metadata</h4>';
            html += '<table class="pbay-detail-meta-table">';
            for (var i = 0; i < metaKeys.length; i++) {
                var key = metaKeys[i];
                var val = meta[key];
                // Skip image field (already shown above)
                if (key === 'image') continue;

                html += '<tr>';
                html += '<td class="pbay-meta-key">' + escHtml(key) + '</td>';
                html += '<td class="pbay-meta-val">' + formatMetaValue(key, val) + '</td>';
                html += '</tr>';
            }
            html += '</table></div>';
        }

        html += '<a href="#" class="pbay-detail-close">Close</a>';
        html += '</div>';

        $detail.html(html);

        if ($detail.is(':visible')) {
            // Already visible, just swap content
            $detail.hide().slideDown(150);
        } else {
            $detail.slideDown(150);
        }
    });

    function formatMetaValue(key, val) {
        if (!val) return '<span class="pbay-meta-empty">-</span>';
        var s = escHtml(String(val));
        // Render URLs/IPFS links as clickable
        if (s.match(/^https?:\/\//)) {
            return '<a href="' + s + '" target="_blank" class="pbay-meta-link">' + s + '</a>';
        }
        // Long values get a scrollable box
        if (s.length > 80) {
            return '<div class="pbay-meta-long">' + s + '</div>';
        }
        return s;
    }

    $(document).on('click', '.pbay-detail-close', function (e) {
        e.preventDefault();
        $('#pbay-asset-detail').slideUp(150);
        $('.pbay-asset-tile').removeClass('active');
    });

    $(document).on('click', '#pbay-refresh-balance, #pbay-retry-balance', function (e) {
        e.preventDefault();
        fetchWalletBalance();
    });

    // ========================================
    // Wallet Page: Send ADA
    // ========================================

    $(document).on('click', '#pbay-send-ada-toggle', function () {
        $('#pbay-send-form').slideToggle(200);
        $('#pbay-send-status').html('');
    });

    $(document).on('click', '#pbay-send-ada-cancel', function (e) {
        e.preventDefault();
        $('#pbay-send-form').slideUp(200);
        $('#pbay-send-status').html('');
        $('#pbay-send-recipient').val('');
        $('#pbay-send-amount').val('');
    });

    $(document).on('click', '#pbay-send-ada-confirm', function () {
        var recipient = $('#pbay-send-recipient').val().trim();
        var amount = parseFloat($('#pbay-send-amount').val());

        if (!recipient) {
            alert('Please enter a recipient address.');
            return;
        }

        if (!amount || amount < 1) {
            alert('Please enter a valid ADA amount (minimum 1 ADA).');
            return;
        }

        var shortAddr = recipient.substring(0, 16) + '...' + recipient.substring(recipient.length - 8);
        if (!confirm('Send ' + amount + ' ADA to ' + shortAddr + '?\n\nThis cannot be undone.')) {
            return;
        }

        var $btn = $(this);
        var $status = $('#pbay-send-status');
        $btn.prop('disabled', true).text('Sending...');
        $status.html('<span style="color:#666;">Building & submitting transaction...</span>');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_send_ada',
            nonce: pbayAdmin.nonce,
            recipient_address: recipient,
            ada_amount: amount,
        }, function (response) {
            if (response.success) {
                $status.html(
                    '<div class="notice notice-success inline"><p>' + escHtml(response.data.message) + '</p>' +
                    '<p>TX: <a href="' + response.data.explorer_url + '" target="_blank"><code>' + response.data.tx_hash.substring(0, 24) + '...</code></a></p></div>'
                );
                $('#pbay-send-recipient').val('');
                $('#pbay-send-amount').val('');
                // Refresh balance after a short delay
                setTimeout(fetchWalletBalance, 3000);
            } else {
                $status.html('<div class="notice notice-error inline"><p>' + escHtml(response.data.message || 'Send failed') + '</p></div>');
            }
            $btn.prop('disabled', false).text('Send');
        }).fail(function () {
            $status.html('<div class="notice notice-error inline"><p>Network error. Try again.</p></div>');
            $btn.prop('disabled', false).text('Send');
        });
    });

    // ========================================
    // Wallet Page: Archive / Restore / Delete
    // ========================================

    $(document).on('click', '.pbay-archive-wallet', function () {
        var id = $(this).data('id');
        if (!confirm('Archive this wallet? It will be moved to the archived wallets section and you can restore it later.')) return;

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_archive_wallet',
            nonce: pbayAdmin.nonce,
            wallet_id: id,
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Failed to archive wallet');
            }
        });
    });

    $(document).on('click', '.pbay-unarchive-wallet', function () {
        var id = $(this).data('id');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_unarchive_wallet',
            nonce: pbayAdmin.nonce,
            wallet_id: id,
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Failed to restore wallet');
            }
        });
    });

    $(document).on('click', '.pbay-delete-active-wallet', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!confirm('PERMANENTLY delete this wallet? This cannot be undone.\n\nConsider archiving instead if you may need it later.')) return;

        // Use the form-based delete for active wallet
        var $form = $('<form method="post">' +
            '<input type="hidden" name="_wpnonce" value="' + pbayAdmin.nonce + '" />' +
            '<input type="hidden" name="pbay_wallet_action" value="delete" />' +
            '<input type="hidden" name="wallet_id" value="' + id + '" />' +
            '</form>');

        // Need proper nonce for form-based delete
        // Redirect to a JS-triggered approach instead
        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_archive_wallet',
            nonce: pbayAdmin.nonce,
            wallet_id: id,
        }, function () {
            // Then delete the archived wallet
            $.post(pbayAdmin.ajaxurl, {
                action: 'pbay_delete_archived_wallet',
                nonce: pbayAdmin.nonce,
                wallet_id: id,
            }, function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to delete wallet');
                }
            });
        });
    });

    $(document).on('click', '.pbay-delete-archived', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Permanently delete "' + name + '"? This cannot be undone.')) return;

        var $link = $(this);
        $link.text('Deleting...');

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_delete_archived_wallet',
            nonce: pbayAdmin.nonce,
            wallet_id: id,
        }, function (response) {
            if (response.success) {
                $link.closest('.pbay-archived-card').fadeOut(300, function () { $(this).remove(); });
            } else {
                alert(response.data.message || 'Failed to delete wallet');
                $link.text('Delete Permanently');
            }
        }).fail(function () {
            alert('Network error');
            $link.text('Delete Permanently');
        });
    });

    // Archived wallets section toggle
    $(document).on('click', '#pbay-archived-toggle', function () {
        var $list = $('#pbay-archived-list');
        var $icon = $(this).find('.dashicons');
        $list.slideToggle(200);
        $icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    // ========================================
    // Appearance Page
    // ========================================

    // Preset color maps for live preview
    var presetColorMap = {
        'glass-dark':  { bg: '#0a0e27', cardBg: 'rgba(255,255,255,0.08)', cardBorder: 'rgba(255,255,255,0.12)', text: '#f8f9fa', accent: '#00d4ff', btnBg: 'transparent', btnColor: '#00d4ff' },
        'clean-light': { bg: '#f0f0f1', cardBg: 'rgba(255,255,255,0.95)', cardBorder: 'rgba(0,0,0,0.1)',        text: '#1d2327', accent: '#2271b1', btnBg: '#2271b1',     btnColor: '#f0f0f1' },
        'warm-dark':   { bg: '#1a1a2e', cardBg: 'rgba(255,255,255,0.06)', cardBorder: 'rgba(240,184,73,0.12)',   text: '#f0e6d3', accent: '#f0b849', btnBg: 'transparent', btnColor: '#f0b849' },
        'midnight':    { bg: '#000000', cardBg: 'rgba(255,255,255,0.03)', cardBorder: 'rgba(140,95,199,0.15)',   text: '#e2e2e2', accent: '#a78bfa', btnBg: 'transparent', btnColor: '#a78bfa' }
    };

    // Dropdown value maps for preview
    var accentMap = {
        'cyan': '#00d4ff', 'blue': '#2271b1', 'purple': '#a78bfa',
        'green': '#22c55e', 'amber': '#f0b849', 'red': '#ef4444', 'pink': '#ec4899'
    };
    var accentBgMap = {
        'cyan': '#0a0e27', 'blue': '#f0f0f1', 'purple': '#000000',
        'green': '#0a0e27', 'amber': '#1a1a2e', 'red': '#0a0e27', 'pink': '#0a0e27'
    };
    var textMap = {
        'white': '#f8f9fa', 'light-gray': '#e2e2e2', 'dark-gray': '#50575e', 'black': '#1d2327'
    };
    var cardBgMap = {
        'glass': 'rgba(255,255,255,0.08)', 'white': 'rgba(255,255,255,0.95)',
        'light-gray': 'rgba(245,245,245,0.9)', 'dark': 'rgba(255,255,255,0.06)',
        'charcoal': 'rgba(255,255,255,0.04)', 'black': 'rgba(255,255,255,0.03)'
    };
    var cardBgBgMap = {
        'glass': '#0a0e27', 'white': '#f0f0f1', 'light-gray': '#e8e8e8',
        'dark': '#1a1a2e', 'charcoal': '#111111', 'black': '#000000'
    };

    function updateAppearancePreview() {
        var $preview = $('#pbay-theme-preview');
        if (!$preview.length) return;

        var accent = accentMap[$('#pbay-accent-color').val()] || '#00d4ff';
        var text = textMap[$('#pbay-text-color').val()] || '#f8f9fa';
        var cardBg = cardBgMap[$('#pbay-card-bg').val()] || 'rgba(255,255,255,0.08)';
        var bgColor = cardBgBgMap[$('#pbay-card-bg').val()] || '#0a0e27';
        var btnStyle = $('#pbay-button-style').val() || 'outline';

        var btnBg, btnColor;
        if (btnStyle === 'filled') {
            btnBg = accent;
            btnColor = bgColor;
        } else if (btnStyle === 'soft') {
            // approximate soft with reduced opacity
            btnBg = accent;
            btnColor = bgColor;
        } else {
            btnBg = 'transparent';
            btnColor = accent;
        }

        $preview.css('background', bgColor);
        $('#pbay-preview-card').css({
            'background': cardBg,
            'border-color': accent
        });
        $('#pbay-preview-title').css('color', text);
        $('#pbay-preview-price').css('color', text);
        $('#pbay-preview-category').css({
            'color': accent,
            'border-color': accent,
            'background': 'transparent'
        });
        $('#pbay-preview-btn').css({
            'background': btnBg,
            'color': btnColor,
            'border-color': accent
        });
    }

    // Preset card click
    $(document).on('click', '.pbay-preset-card', function () {
        var $card = $(this);
        var preset = $card.data('preset');

        // Update UI
        $('.pbay-preset-card').removeClass('active');
        $('.pbay-preset-check').text('');
        $card.addClass('active');
        $card.find('.pbay-preset-check').html('&#10003;');
        $('#pbay-theme-preset').val(preset);

        // Set dropdowns to preset values
        $('#pbay-card-bg').val($card.data('card-bg'));
        $('#pbay-card-border').val($card.data('card-border'));
        $('#pbay-text-color').val($card.data('text-color'));
        $('#pbay-accent-color').val($card.data('accent-color'));
        $('#pbay-button-style').val($card.data('button-style'));

        updateAppearancePreview();
    });

    // Any dropdown change marks preset as "custom"
    $(document).on('change', '.pbay-theme-select', function () {
        // Check if current dropdown values still match any preset
        var currentVals = {
            card_bg: $('#pbay-card-bg').val(),
            card_border: $('#pbay-card-border').val(),
            text_color: $('#pbay-text-color').val(),
            accent_color: $('#pbay-accent-color').val(),
            button_style: $('#pbay-button-style').val()
        };

        var matched = false;
        $('.pbay-preset-card').each(function () {
            var $p = $(this);
            if ($p.data('card-bg') === currentVals.card_bg &&
                $p.data('card-border') === currentVals.card_border &&
                $p.data('text-color') === currentVals.text_color &&
                $p.data('accent-color') === currentVals.accent_color &&
                $p.data('button-style') === currentVals.button_style) {
                matched = true;
                $('.pbay-preset-card').removeClass('active');
                $('.pbay-preset-check').text('');
                $p.addClass('active');
                $p.find('.pbay-preset-check').html('&#10003;');
                $('#pbay-theme-preset').val($p.data('preset'));
            }
        });

        if (!matched) {
            $('.pbay-preset-card').removeClass('active');
            $('.pbay-preset-check').text('');
            $('#pbay-theme-preset').val('custom');
        }

        updateAppearancePreview();
    });

    // Copy shortcode button
    $(document).on('click', '.pbay-copy-shortcode', function () {
        var $btn = $(this);
        var text = $btn.data('shortcode');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                $btn.text('Copied!').addClass('copied');
                setTimeout(function () { $btn.text('Copy').removeClass('copied'); }, 1500);
            });
        } else {
            // Fallback
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            $btn.text('Copied!').addClass('copied');
            setTimeout(function () { $btn.text('Copy').removeClass('copied'); }, 1500);
        }
    });

    // ========================================
    // Setup Page: Store Wallet Payout Toggle
    // ========================================

    $(document).on('change', '#pbay-use-store-wallet', function () {
        if ($(this).is(':checked')) {
            $('#pbay-merchant-address-field').slideUp(200);
        } else {
            $('#pbay-merchant-address-field').slideDown(200);
        }
    });

    // ========================================
    // How It Works: Terms of Service
    // ========================================

    $(document).on('click', '#pbay-read-tos', function (e) {
        e.preventDefault();
        var $text = $('#pbay-tos-text');
        $text.slideToggle(200);
        // Enable the checkbox once the ToS has been expanded
        $('#pbay-agree-tos').prop('disabled', false);
    });

    $(document).on('change', '#pbay-agree-tos', function () {
        var agreed = $(this).is(':checked') ? 1 : 0;

        $.post(pbayAdmin.ajaxurl, {
            action: 'pbay_accept_tos',
            nonce: pbayAdmin.nonce,
            agreed: agreed,
        }, function (response) {
            if (!response.success) return;

            var $callout = $('#pbay-tos-callout');
            if (agreed) {
                $callout.removeClass('pbay-callout-warning').addClass('pbay-callout-success');
                $callout.html(
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    '<div><strong>Terms accepted</strong> You have agreed to the PBay Terms of Service.</div>'
                );
            } else {
                $callout.removeClass('pbay-callout-success').addClass('pbay-callout-warning');
                $callout.html(
                    '<span class="dashicons dashicons-warning"></span>' +
                    '<div><strong>Acceptance required</strong> You must read and accept the Terms of Service before using PBay. Other pages are locked until you agree.</div>'
                );
            }
        });
    });

    // ========================================
    // How It Works: Tab Switching
    // ========================================

    $(document).on('click', '.pbay-tab', function () {
        var tab = $(this).data('tab');
        $('.pbay-tab').removeClass('active');
        $(this).addClass('active');
        $('.pbay-tab-panel').removeClass('active');
        $('.pbay-tab-panel[data-tab="' + tab + '"]').addClass('active');
    });

    // ========================================
    // Init
    // ========================================

    $(document).ready(function () {
        // Fetch ADA price if on create listing page
        if ($('#pbay-wizard').length) {
            fetchAdaPrice();

            // If editing an existing listing, detect current step
            if ($('input[name="listing_id"]').val() && $('input[name="listing_id"]').val() !== '0') {
                // Start on step 1 but enable navigation to all completed steps
            }
        }

        // Fetch wallet balance if on wallet page
        if ($('.pbay-wallet-dashboard').length) {
            fetchWalletBalance();
        }

        // Init appearance preview if on appearance page
        if ($('#pbay-theme-preview').length) {
            updateAppearancePreview();
        }
    });

})(jQuery);
