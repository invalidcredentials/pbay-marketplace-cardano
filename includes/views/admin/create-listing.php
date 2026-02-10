<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap pbay-admin">
    <h1><?php echo $listing ? 'Edit Listing' : 'Create Listing'; ?></h1>

    <div id="pbay-wizard">
        <!-- Step Navigation -->
        <div class="pbay-wizard-steps">
            <div class="pbay-step active" data-step="1"><span>1</span> Image</div>
            <div class="pbay-step" data-step="2"><span>2</span> Basic Info</div>
            <div class="pbay-step" data-step="3"><span>3</span> Pricing</div>
            <div class="pbay-step" data-step="4"><span>4</span> Details</div>
            <div class="pbay-step" data-step="5"><span>5</span> Shipping</div>
            <div class="pbay-step" data-step="6"><span>6</span> Review</div>
        </div>

        <form id="pbay-listing-form">
            <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing['id'] ?? 0); ?>" />

            <!-- Step 1: Image -->
            <div class="pbay-wizard-panel active" data-step="1">
                <div class="pbay-card">
                    <div class="pbay-card-header">
                        <span class="dashicons dashicons-format-image pbay-card-icon"></span>
                        <div>
                            <h2>Product Image</h2>
                            <p class="pbay-card-desc">Upload a main product photo and up to 3 gallery images.</p>
                        </div>
                    </div>

                    <div class="pbay-image-upload">
                        <input type="hidden" name="image_id" id="pbay-image-id" value="<?php echo esc_attr($listing['image_id'] ?? ''); ?>" />
                        <div id="pbay-image-preview">
                            <?php if (!empty($listing['image_id'])): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_url($listing['image_id'])); ?>" style="max-width:300px;" />
                            <?php else: ?>
                                <div class="pbay-placeholder">Click or drag image here</div>
                            <?php endif; ?>
                        </div>
                        <p>
                            <button type="button" id="pbay-upload-image" class="button">Select Image</button>
                            <button type="button" id="pbay-remove-image" class="button" <?php echo empty($listing['image_id']) ? 'style="display:none;"' : ''; ?>>Remove</button>
                        </p>
                    </div>

                    <!-- Gallery Images -->
                    <div style="margin-top:24px;">
                        <h3>Additional Images (up to 3)</h3>
                        <input type="hidden" name="gallery_ids" id="pbay-gallery-ids" value="<?php echo esc_attr($listing['gallery_ids'] ?? ''); ?>" />
                        <div id="pbay-gallery-preview" class="pbay-gallery-grid">
                            <?php
                            $gallery_ids = array_filter(explode(',', $listing['gallery_ids'] ?? ''));
                            foreach ($gallery_ids as $gid):
                                $gid = intval($gid);
                                $gurl = wp_get_attachment_image_url($gid, 'medium');
                                if ($gurl):
                            ?>
                                <div class="pbay-gallery-item" data-id="<?php echo $gid; ?>">
                                    <img src="<?php echo esc_url($gurl); ?>" />
                                    <button type="button" class="pbay-gallery-remove">&times;</button>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php if (count($gallery_ids) < 3): ?>
                            <p><button type="button" id="pbay-add-gallery" class="button">+ Add Image</button></p>
                        <?php endif; ?>
                    </div>

                    <?php if (get_option('pbay_pinata_enabled', 0)): ?>
                        <div style="margin-top:24px; padding-top:16px; border-top:1px solid #eee;">
                            <h3>IPFS Pinning</h3>
                            <div class="pbay-field-actions">
                                <button type="button" id="pbay-pin-ipfs" class="button" disabled>Pin to IPFS</button>
                                <span id="pbay-ipfs-status" class="pbay-test-result"></span>
                            </div>
                            <input type="hidden" name="ipfs_cid" id="pbay-ipfs-cid" value="<?php echo esc_attr($listing['ipfs_cid'] ?? ''); ?>" />
                            <?php if (!empty($listing['ipfs_cid'])): ?>
                                <p class="description">Current CID: <code><?php echo esc_html($listing['ipfs_cid']); ?></code></p>
                            <?php endif; ?>
                            <div class="pbay-field" style="margin-top:12px;">
                                <label for="pbay-ipfs-cid-manual">Or paste IPFS CID manually</label>
                                <input type="text" name="ipfs_cid_manual" id="pbay-ipfs-cid-manual" value="<?php echo esc_attr($listing['ipfs_cid_manual'] ?? ''); ?>" class="regular-text" placeholder="QmXXX..." />
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 2: Basic Info -->
            <div class="pbay-wizard-panel" data-step="2">
                <div class="pbay-card">
                    <div class="pbay-card-header">
                        <span class="dashicons dashicons-edit pbay-card-icon"></span>
                        <div>
                            <h2>Basic Information</h2>
                            <p class="pbay-card-desc">Name, description, category, and condition of the product.</p>
                        </div>
                    </div>

                    <div class="pbay-field-group">
                        <div class="pbay-field">
                            <label for="pbay-title">Title <span style="color:#d63638;">*</span></label>
                            <input type="text" name="title" id="pbay-title" value="<?php echo esc_attr($listing['title'] ?? ''); ?>" class="large-text" required />
                        </div>
                        <div class="pbay-field">
                            <label for="pbay-description">Description</label>
                            <textarea name="description" id="pbay-description" rows="5" class="large-text"><?php echo esc_textarea($listing['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="pbay-field-row">
                            <div class="pbay-field pbay-field-half">
                                <label for="pbay-category">Category</label>
                                <?php if (!empty($listing_categories)): ?>
                                    <select name="category_id" id="pbay-category">
                                        <option value="">Select Category</option>
                                        <?php foreach ($listing_categories as $cat): ?>
                                            <option value="<?php echo esc_attr($cat['id']); ?>" <?php selected(intval($listing['category_id'] ?? 0), intval($cat['id'])); ?>><?php echo esc_html($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <p class="description">No categories yet. <a href="<?php echo esc_url(admin_url('admin.php?page=pbay-listing-categories')); ?>">Create one</a> first.</p>
                                <?php endif; ?>
                            </div>
                            <div class="pbay-field pbay-field-half">
                                <label for="pbay-condition">Condition</label>
                                <select name="condition_type" id="pbay-condition">
                                    <option value="">Select Condition</option>
                                    <?php foreach ($conditions as $cond): ?>
                                        <option value="<?php echo esc_attr($cond); ?>" <?php selected(($listing['condition_type'] ?? ''), $cond); ?>><?php echo esc_html($cond); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Pricing -->
            <div class="pbay-wizard-panel" data-step="3">
                <div class="pbay-card">
                    <div class="pbay-card-header">
                        <span class="dashicons dashicons-money-alt pbay-card-icon"></span>
                        <div>
                            <h2>Pricing &amp; Quantity</h2>
                            <p class="pbay-card-desc">Set your price in USD. The ADA equivalent is calculated at checkout.</p>
                        </div>
                    </div>

                    <div class="pbay-field-group">
                        <div class="pbay-field-row">
                            <div class="pbay-field pbay-field-half">
                                <label for="pbay-price">Price (USD) <span style="color:#d63638;">*</span></label>
                                <input type="number" name="price_usd" id="pbay-price" value="<?php echo esc_attr($listing['price_usd'] ?? ''); ?>" step="0.01" min="0.01" class="regular-text" required />
                                <p class="description" id="pbay-ada-equiv"></p>
                            </div>
                            <div class="pbay-field pbay-field-half">
                                <label for="pbay-quantity">Quantity</label>
                                <input type="number" name="quantity" id="pbay-quantity" value="<?php echo esc_attr($listing['quantity'] ?? 1); ?>" min="1" class="small-text" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Details (Custom Attributes) -->
            <div class="pbay-wizard-panel" data-step="4">
                <div class="pbay-card">
                    <div class="pbay-card-header">
                        <span class="dashicons dashicons-tag pbay-card-icon"></span>
                        <div>
                            <h2>Product Details</h2>
                            <p class="pbay-card-desc">Custom attributes embedded in the NFT metadata (e.g., Color, Brand, Size, Year).</p>
                        </div>
                    </div>

                    <div id="pbay-meta-rows">
                        <?php if (!empty($meta)): ?>
                            <?php foreach ($meta as $m): ?>
                                <div class="pbay-meta-row">
                                    <input type="text" name="meta_keys[]" value="<?php echo esc_attr($m['meta_key']); ?>" placeholder="Attribute name" />
                                    <input type="text" name="meta_values[]" value="<?php echo esc_attr($m['meta_value']); ?>" placeholder="Value" />
                                    <button type="button" class="button pbay-remove-meta">X</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p><button type="button" id="pbay-add-meta" class="button">+ Add Attribute</button></p>

                    <div class="pbay-field-group" style="margin-top:20px; padding-top:16px; border-top:1px solid #eee;">
                        <div class="pbay-field-row">
                            <div class="pbay-field pbay-field-half">
                                <label for="pbay-weight">Weight (lbs)</label>
                                <input type="number" name="weight_lbs" id="pbay-weight" value="<?php echo esc_attr($listing['weight_lbs'] ?? ''); ?>" step="0.01" min="0" class="small-text" />
                            </div>
                            <div class="pbay-field pbay-field-half">
                                <label for="pbay-dimensions">Dimensions</label>
                                <input type="text" name="dimensions" id="pbay-dimensions" value="<?php echo esc_attr($listing['dimensions'] ?? ''); ?>" placeholder="L x W x H inches" class="regular-text" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Shipping -->
            <div class="pbay-wizard-panel" data-step="5">
                <div class="pbay-card">
                    <div class="pbay-card-header">
                        <span class="dashicons dashicons-car pbay-card-icon"></span>
                        <div>
                            <h2>Shipping</h2>
                            <p class="pbay-card-desc">Where the item ships from and any notes for the buyer.</p>
                        </div>
                    </div>

                    <div class="pbay-field-group">
                        <div class="pbay-field">
                            <label for="pbay-ships-from">Ships From</label>
                            <input type="text" name="ships_from" id="pbay-ships-from" value="<?php echo esc_attr($listing['ships_from'] ?? ''); ?>" class="regular-text" placeholder="City, State" />
                        </div>
                        <div class="pbay-field">
                            <label for="pbay-shipping-notes">Shipping Notes</label>
                            <textarea name="shipping_notes" id="pbay-shipping-notes" rows="3" class="large-text"><?php echo esc_textarea($listing['shipping_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 6: Review -->
            <div class="pbay-wizard-panel" data-step="6">
                <div class="pbay-card">
                    <div class="pbay-card-header">
                        <span class="dashicons dashicons-visibility pbay-card-icon"></span>
                        <div>
                            <h2>Review &amp; Publish</h2>
                            <p class="pbay-card-desc">Verify your listing details, then save as draft or mint the NFT.</p>
                        </div>
                    </div>

                    <div id="pbay-review-content">
                        <p>Review your listing details before saving.</p>
                    </div>

                    <div id="pbay-nft-metadata-preview" style="margin-top:20px;">
                        <h3>NFT Metadata Preview</h3>
                        <pre id="pbay-metadata-json" style="background:#1d2327; color:#50c878; padding:15px; border-radius:8px; overflow:auto; max-height:400px; font-size:12px;"></pre>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="pbay-wizard-nav">
                <button type="button" id="pbay-prev-step" class="button" style="display:none;">Previous</button>
                <button type="button" id="pbay-next-step" class="button button-primary">Next</button>
                <button type="button" id="pbay-save-draft" class="button">Save Draft</button>
                <button type="button" id="pbay-publish" class="button button-primary" style="display:none;">Publish (Mint NFT)</button>
            </div>
        </form>

        <div id="pbay-listing-messages"></div>
    </div>
</div>
