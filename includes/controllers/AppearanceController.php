<?php
namespace PBay\Controllers;

class AppearanceController {

    /**
     * Preset theme definitions
     */
    private static $presets = [
        'glass-dark' => [
            'label'       => 'Glass Dark',
            'card_bg'     => 'glass',
            'card_border' => 'subtle',
            'text_color'  => 'white',
            'accent_color'=> 'cyan',
            'button_style'=> 'outline',
        ],
        'clean-light' => [
            'label'       => 'Clean Light',
            'card_bg'     => 'white',
            'card_border' => 'medium',
            'text_color'  => 'black',
            'accent_color'=> 'blue',
            'button_style'=> 'filled',
        ],
        'warm-dark' => [
            'label'       => 'Warm Dark',
            'card_bg'     => 'dark',
            'card_border' => 'accent-tinted',
            'text_color'  => 'light-gray',
            'accent_color'=> 'amber',
            'button_style'=> 'outline',
        ],
        'midnight' => [
            'label'       => 'Midnight',
            'card_bg'     => 'black',
            'card_border' => 'subtle',
            'text_color'  => 'light-gray',
            'accent_color'=> 'purple',
            'button_style'=> 'outline',
        ],
    ];

    /**
     * Dropdown option maps: option key => [label, css value(s)]
     */
    private static $card_bg_options = [
        'glass'      => ['Glass',      'rgba(255,255,255,0.08)', '16px'],
        'white'      => ['White',      'rgba(255,255,255,0.95)', '0px'],
        'light-gray' => ['Light Gray', 'rgba(245,245,245,0.9)',  '0px'],
        'dark'       => ['Dark',       'rgba(255,255,255,0.06)', '12px'],
        'charcoal'   => ['Charcoal',   'rgba(255,255,255,0.04)', '14px'],
        'black'      => ['Black',      'rgba(255,255,255,0.03)', '20px'],
    ];

    private static $card_border_options = [
        'subtle'        => ['Subtle',        'rgba(255,255,255,0.12)'],
        'medium'        => ['Medium',        'rgba(0,0,0,0.1)'],
        'none'          => ['None',          'transparent'],
        'accent-tinted' => ['Accent Tinted', '__accent_border__'],
    ];

    private static $text_color_options = [
        'white'      => ['White',      '#f8f9fa', 'rgba(255,255,255,0.7)'],
        'light-gray' => ['Light Gray', '#e2e2e2', 'rgba(226,226,226,0.6)'],
        'dark-gray'  => ['Dark Gray',  '#50575e', 'rgba(80,87,94,0.7)'],
        'black'      => ['Black',      '#1d2327', 'rgba(0,0,0,0.6)'],
    ];

    private static $accent_color_options = [
        'cyan'   => ['Cyan',   '#00d4ff', 'rgba(0,212,255,__A__)',   '#0a0e27'],
        'blue'   => ['Blue',   '#2271b1', 'rgba(34,113,177,__A__)',  '#f0f0f1'],
        'purple' => ['Purple', '#a78bfa', 'rgba(167,139,250,__A__)', '#000000'],
        'green'  => ['Green',  '#22c55e', 'rgba(34,197,94,__A__)',   '#0a0e27'],
        'amber'  => ['Amber',  '#f0b849', 'rgba(240,184,73,__A__)', '#1a1a2e'],
        'red'    => ['Red',    '#ef4444', 'rgba(239,68,68,__A__)',   '#0a0e27'],
        'pink'   => ['Pink',   '#ec4899', 'rgba(236,72,153,__A__)', '#0a0e27'],
    ];

    private static $button_style_options = [
        'outline' => ['Outline'],
        'filled'  => ['Filled'],
        'soft'    => ['Soft'],
    ];

    public static function register() {
        add_action('admin_init', [self::class, 'handleSave']);
    }

    public static function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $preset       = get_option('pbay_theme_preset', 'glass-dark');
        $card_bg      = get_option('pbay_theme_card_bg', 'glass');
        $card_border  = get_option('pbay_theme_card_border', 'subtle');
        $text_color   = get_option('pbay_theme_text_color', 'white');
        $accent_color = get_option('pbay_theme_accent_color', 'cyan');
        $button_style = get_option('pbay_theme_button_style', 'outline');

        $presets              = self::$presets;
        $card_bg_options      = self::$card_bg_options;
        $card_border_options  = self::$card_border_options;
        $text_color_options   = self::$text_color_options;
        $accent_color_options = self::$accent_color_options;
        $button_style_options = self::$button_style_options;

        $message = '';
        if (isset($_GET['pbay_saved'])) {
            $message = 'Appearance settings saved.';
        }

        include PBAY_PLUGIN_DIR . 'includes/views/admin/appearance.php';
    }

    public static function handleSave() {
        if (!isset($_POST['pbay_save_appearance'])) {
            return;
        }

        check_admin_referer('pbay_appearance_nonce');

        if (!current_user_can('manage_options')) {
            return;
        }

        $preset       = sanitize_text_field($_POST['pbay_theme_preset'] ?? 'glass-dark');
        $card_bg      = sanitize_text_field($_POST['pbay_theme_card_bg'] ?? 'glass');
        $card_border  = sanitize_text_field($_POST['pbay_theme_card_border'] ?? 'subtle');
        $text_color   = sanitize_text_field($_POST['pbay_theme_text_color'] ?? 'white');
        $accent_color = sanitize_text_field($_POST['pbay_theme_accent_color'] ?? 'cyan');
        $button_style = sanitize_text_field($_POST['pbay_theme_button_style'] ?? 'outline');

        // Validate values exist in our option maps
        if (!isset(self::$presets[$preset]) && $preset !== 'custom') {
            $preset = 'glass-dark';
        }
        if (!isset(self::$card_bg_options[$card_bg])) $card_bg = 'glass';
        if (!isset(self::$card_border_options[$card_border])) $card_border = 'subtle';
        if (!isset(self::$text_color_options[$text_color])) $text_color = 'white';
        if (!isset(self::$accent_color_options[$accent_color])) $accent_color = 'cyan';
        if (!isset(self::$button_style_options[$button_style])) $button_style = 'outline';

        update_option('pbay_theme_preset', $preset);
        update_option('pbay_theme_card_bg', $card_bg);
        update_option('pbay_theme_card_border', $card_border);
        update_option('pbay_theme_text_color', $text_color);
        update_option('pbay_theme_accent_color', $accent_color);
        update_option('pbay_theme_button_style', $button_style);

        wp_safe_redirect(admin_url('admin.php?page=pbay-appearance&pbay_saved=1'));
        exit;
    }

    /**
     * Generate CSS variable overrides from saved theme options.
     * Returns empty string for the default (glass-dark) preset with no overrides.
     */
    public static function getThemeCSS() {
        $card_bg      = get_option('pbay_theme_card_bg', 'glass');
        $card_border  = get_option('pbay_theme_card_border', 'subtle');
        $text_color   = get_option('pbay_theme_text_color', 'white');
        $accent_color = get_option('pbay_theme_accent_color', 'cyan');
        $button_style = get_option('pbay_theme_button_style', 'outline');

        // If all defaults, return nothing — the CSS fallbacks handle it
        if ($card_bg === 'glass' && $card_border === 'subtle' && $text_color === 'white'
            && $accent_color === 'cyan' && $button_style === 'outline') {
            return '';
        }

        $vars = [];

        // Accent first — needed for border tinting
        $accent = self::$accent_color_options[$accent_color] ?? self::$accent_color_options['cyan'];
        $accent_hex = $accent[1];
        $accent_rgba_tpl = $accent[2];
        $accent_bg = $accent[3];

        $vars[] = '--pbay-accent: ' . $accent_hex;
        $vars[] = '--pbay-accent-glow: ' . str_replace('__A__', '0.2', $accent_rgba_tpl);
        $vars[] = '--pbay-accent-hover-glow: ' . str_replace('__A__', '0.4', $accent_rgba_tpl);
        $vars[] = '--pbay-accent-subtle: ' . str_replace('__A__', '0.1', $accent_rgba_tpl);
        $vars[] = '--pbay-bg: ' . $accent_bg;

        // Card BG
        $bg = self::$card_bg_options[$card_bg] ?? self::$card_bg_options['glass'];
        $vars[] = '--pbay-card-bg: ' . $bg[1];
        $vars[] = '--pbay-blur: ' . $bg[2];

        // Card border
        $border = self::$card_border_options[$card_border] ?? self::$card_border_options['subtle'];
        $border_val = $border[1];
        if ($border_val === '__accent_border__') {
            $border_val = str_replace('__A__', '0.12', $accent_rgba_tpl);
        }
        $vars[] = '--pbay-card-border: ' . $border_val;

        // Text
        $text = self::$text_color_options[$text_color] ?? self::$text_color_options['white'];
        $vars[] = '--pbay-text: ' . $text[1];
        $vars[] = '--pbay-text-secondary: ' . $text[2];

        // Button style
        if ($button_style === 'filled') {
            $vars[] = '--pbay-btn-bg: ' . $accent_hex;
            $vars[] = '--pbay-btn-color: ' . $accent_bg;
        } elseif ($button_style === 'soft') {
            $vars[] = '--pbay-btn-bg: ' . str_replace('__A__', '0.15', $accent_rgba_tpl);
            $vars[] = '--pbay-btn-color: ' . $accent_hex;
        } else {
            $vars[] = '--pbay-btn-bg: transparent';
            $vars[] = '--pbay-btn-color: ' . $accent_hex;
        }

        $css = ":root {\n";
        foreach ($vars as $v) {
            $css .= "    {$v};\n";
        }
        $css .= "}\n";

        return $css;
    }

    /**
     * Hooked to wp_enqueue_scripts — outputs inline CSS after the frontend stylesheet.
     */
    public static function outputFrontendCSS() {
        $css = self::getThemeCSS();
        if (empty($css)) {
            return;
        }

        wp_add_inline_style('pbay-frontend-css', $css);
    }

    /**
     * Get presets for JS
     */
    public static function getPresetsForJs() {
        return self::$presets;
    }
}
