<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap pbay-admin">
    <h1>PBay - Appearance</h1>

    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <form method="post" id="pbay-appearance-form">
        <?php wp_nonce_field('pbay_appearance_nonce'); ?>
        <input type="hidden" name="pbay_save_appearance" value="1" />
        <input type="hidden" name="pbay_theme_preset" id="pbay-theme-preset" value="<?php echo esc_attr($preset); ?>" />

        <!-- ===== Section 1: Theme Presets ===== -->
        <div class="pbay-card">
            <h2>Theme Presets</h2>
            <p>Choose a preset theme, then customize individual colors below.</p>

            <div class="pbay-preset-grid">
                <?php foreach ($presets as $key => $p): ?>
                    <?php
                        $accent_opt = $accent_color_options[$p['accent_color']] ?? $accent_color_options['cyan'];
                        $bg_opt = $card_bg_options[$p['card_bg']] ?? $card_bg_options['glass'];
                        $text_opt = $text_color_options[$p['text_color']] ?? $text_color_options['white'];
                    ?>
                    <div class="pbay-preset-card <?php echo $preset === $key ? 'active' : ''; ?>"
                         data-preset="<?php echo esc_attr($key); ?>"
                         data-card-bg="<?php echo esc_attr($p['card_bg']); ?>"
                         data-card-border="<?php echo esc_attr($p['card_border']); ?>"
                         data-text-color="<?php echo esc_attr($p['text_color']); ?>"
                         data-accent-color="<?php echo esc_attr($p['accent_color']); ?>"
                         data-button-style="<?php echo esc_attr($p['button_style']); ?>">
                        <div class="pbay-preset-swatch" style="background: <?php echo esc_attr($accent_opt[3]); ?>;">
                            <span class="pbay-swatch-card" style="background: <?php echo esc_attr($bg_opt[1]); ?>; border-color: <?php echo esc_attr($accent_opt[1]); ?>;"></span>
                            <span class="pbay-swatch-accent" style="background: <?php echo esc_attr($accent_opt[1]); ?>;"></span>
                            <span class="pbay-swatch-text" style="background: <?php echo esc_attr($text_opt[1]); ?>;"></span>
                        </div>
                        <div class="pbay-preset-label"><?php echo esc_html($p['label']); ?></div>
                        <div class="pbay-preset-check"><?php echo $preset === $key ? '&#10003;' : ''; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ===== Section 2: Custom Colors ===== -->
        <div class="pbay-card">
            <h2>Custom Colors</h2>
            <p>Override individual settings. Changing any value will set the preset to "Custom".</p>

            <div class="pbay-color-grid">
                <!-- Card Background -->
                <div class="pbay-color-field">
                    <label for="pbay-card-bg">Card Background</label>
                    <select name="pbay_theme_card_bg" id="pbay-card-bg" class="pbay-theme-select">
                        <?php foreach ($card_bg_options as $key => $opt): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($card_bg, $key); ?>
                                data-swatch="<?php echo esc_attr($opt[1]); ?>">
                                <?php echo esc_html($opt[0]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Card Border -->
                <div class="pbay-color-field">
                    <label for="pbay-card-border">Card Border</label>
                    <select name="pbay_theme_card_border" id="pbay-card-border" class="pbay-theme-select">
                        <?php foreach ($card_border_options as $key => $opt): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($card_border, $key); ?>>
                                <?php echo esc_html($opt[0]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Text Color -->
                <div class="pbay-color-field">
                    <label for="pbay-text-color">Text Color</label>
                    <select name="pbay_theme_text_color" id="pbay-text-color" class="pbay-theme-select">
                        <?php foreach ($text_color_options as $key => $opt): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($text_color, $key); ?>
                                data-swatch="<?php echo esc_attr($opt[1]); ?>">
                                <?php echo esc_html($opt[0]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Accent Color -->
                <div class="pbay-color-field">
                    <label for="pbay-accent-color">Accent Color</label>
                    <select name="pbay_theme_accent_color" id="pbay-accent-color" class="pbay-theme-select">
                        <?php foreach ($accent_color_options as $key => $opt): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($accent_color, $key); ?>
                                data-swatch="<?php echo esc_attr($opt[1]); ?>">
                                <?php echo esc_html($opt[0]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Button Style -->
                <div class="pbay-color-field">
                    <label for="pbay-button-style">Button Style</label>
                    <select name="pbay_theme_button_style" id="pbay-button-style" class="pbay-theme-select">
                        <?php foreach ($button_style_options as $key => $opt): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($button_style, $key); ?>>
                                <?php echo esc_html($opt[0]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ===== Section 3: Preview ===== -->
        <div class="pbay-card">
            <h2>Preview</h2>
            <div class="pbay-theme-preview" id="pbay-theme-preview">
                <div class="pbay-preview-card" id="pbay-preview-card">
                    <div class="pbay-preview-image"></div>
                    <div class="pbay-preview-body">
                        <div class="pbay-preview-category" id="pbay-preview-category">Electronics</div>
                        <div class="pbay-preview-title" id="pbay-preview-title">Sample Product</div>
                        <div class="pbay-preview-price" id="pbay-preview-price">$49.99</div>
                        <button type="button" class="pbay-preview-btn" id="pbay-preview-btn">View Details</button>
                    </div>
                </div>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">Save Appearance Settings</button>
        </p>
    </form>

    <!-- ===== Section 4: Shortcodes Reference ===== -->
    <div class="pbay-card">
        <h2>Shortcodes Reference</h2>
        <p>Create a WordPress page, paste a shortcode below, and publish. That's it!</p>

        <table class="widefat pbay-shortcode-table">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                    <th>Attributes</th>
                    <th style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[pbay-catalog]</code></td>
                    <td>Product grid — displays all published listings with category filtering, search, and pagination.</td>
                    <td>
                        <code>category</code> — filter by category slug<br>
                        <code>limit</code> — products per page (default: 24)<br>
                        <code>columns</code> — grid columns (default: 4)
                    </td>
                    <td>
                        <button type="button" class="button pbay-copy-shortcode" data-shortcode="[pbay-catalog]">Copy</button>
                    </td>
                </tr>
                <tr>
                    <td><code>[pbay-product id="X"]</code></td>
                    <td>Single product page — shows full details, gallery, NFT info, and Buy Now button.</td>
                    <td>
                        <code>id</code> — listing ID (required)<br>
                        <code>slug</code> — listing slug (alternative to id)
                    </td>
                    <td>
                        <button type="button" class="button pbay-copy-shortcode" data-shortcode='[pbay-product id=""]'>Copy</button>
                    </td>
                </tr>
                <tr>
                    <td><code>[pbay-orders]</code></td>
                    <td>Buyer order history — shows past orders for the connected wallet. Also includes order lookup by ID.</td>
                    <td><em>No attributes</em></td>
                    <td>
                        <button type="button" class="button pbay-copy-shortcode" data-shortcode="[pbay-orders]">Copy</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="pbay-shortcode-tips">
            <h3>Quick Setup</h3>
            <ol>
                <li>Go to <strong>Pages &rarr; Add New</strong> in WordPress.</li>
                <li>Give the page a title (e.g. "Shop", "Product", or "My Orders").</li>
                <li>Paste the appropriate shortcode into the page content.</li>
                <li>Publish the page.</li>
            </ol>
            <p><strong>Tip:</strong> For the catalog, the <code>[pbay-catalog]</code> shortcode automatically handles category filtering via URL parameters — no extra setup needed.</p>
        </div>
    </div>
</div>
