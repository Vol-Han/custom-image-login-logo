<?php
/**
 * Plugin Name: Custom Image Login Logo
 * Description: Allows you to upload a custom logo for the WordPress login page and customize its link.
 * Version: 1.4.2
 * Author: Volodymyr Hannibal
 * Author URI: https://rulit.site
 * License: GPL2
 * Text Domain: custom-image-login-logo
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// ✅ 1. Add settings to "Settings → General"
function cill_admin_settings_init() {
    add_settings_section(
        'cill_settings_section',
        __('Custom Login Logo Settings', 'custom-image-login-logo'),
        '__return_false',
        'general'
    );

    add_settings_field(
        'cill_logo_url',
        __('Login Logo Image', 'custom-image-login-logo'),
        'cill_logo_upload_callback',
        'general',
        'cill_settings_section'
    );

    add_settings_field(
        'cill_logo_link',
        __('Login Logo Link', 'custom-image-login-logo'),
        'cill_logo_link_callback',
        'general',
        'cill_settings_section'
    );

    // Register settings for the custom login logo URL and link
    // Using proper sanitization functions to ensure safe and valid URLs
    // 
    // Why not use sanitize_text_field()? 
    // - sanitize_text_field() is designed for plain text, not URLs.
    // - It removes special characters that might be necessary for a valid URL.
    // - Instead, we use sanitize_url() (available from WP 5.9) for proper URL sanitization.
    // - If sanitize_url() is unavailable (WP < 5.9), we fall back to esc_url_raw().
    register_setting('general', 'cill_logo_url', array(
        'type'              => 'string',
        'sanitize_callback' => function ($url) {
            return function_exists('sanitize_url') ? sanitize_url($url) : esc_url_raw($url);
        },
    ));
    register_setting('general', 'cill_logo_link', array(
        'type'              => 'string',
        'sanitize_callback' => function ($url) {
            return function_exists('sanitize_url') ? sanitize_url($url) : esc_url_raw($url);
        },
    ));
}
add_action('admin_init', 'cill_admin_settings_init');


// ✅ 2. Changing the login page logo
function cill_enqueue_login_style() {
    $logo_url = get_option('cill_logo_url', '');

    // If the user has not selected a logo, use the theme logo
    if (empty($logo_url)) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        }
    }

    // If the theme logo is not available, check which default logo is accessible
    if (empty($logo_url)) {
        $logo_url = includes_url('images/w-logo-blue.png'); // Modern WP logo
    }

    // Enqueue CSS file
    wp_enqueue_style(
        'cill-login-style',
        plugins_url('css/cill-styles.css', __FILE__),
        array(),
        '1.0'
    );

    // Add inline style with dynamic background-image
    $custom_css = "
        #login h1 a {
            background-image: url('" . esc_url($logo_url) . "') !important;
        }";
    wp_add_inline_style('cill-login-style', $custom_css);
}
add_action('login_enqueue_scripts', 'cill_enqueue_login_style');

// ✅ 3. Changing the login logo link
function cill_custom_login_url() {
    $custom_link = get_option('cill_logo_link', '');

    // If no link is provided → use home_url()
    return $custom_link ? esc_url($custom_link) : home_url();
}
add_filter('login_headerurl', 'cill_custom_login_url');

// Function to change the logo link
function cill_logo_link_callback() {
    $logo_link = get_option('cill_logo_link', '');
    echo '<input type="url" id="cill_logo_link" name="cill_logo_link" value="' . esc_url($logo_link) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('Enter a link where the logo should redirect when clicked.', 'custom-image-login-logo') . '</p>';
}

// ✅ 4. Removing the Hover Text (Title) on the Login Logo
function cill_custom_login_title() {
    return ''; // No hover text
}
add_filter('login_headertext', 'cill_custom_login_title');

// ✅ 5. Add an image selection button with translation support
function cill_logo_upload_callback() {
    $logo = get_option('cill_logo_url', '');
    ?>
    <input type="url" id="cill_logo_url" name="cill_logo_url" value="<?php echo esc_url($logo); ?>" class="regular-text">
    <button type="button" class="button cill-upload-button"><?php esc_html_e('Choose Image', 'custom-image-login-logo'); ?></button>
    <button type="button" class="button cill-remove-button"><?php esc_html_e('Remove', 'custom-image-login-logo'); ?></button>
    <p class="description"><?php esc_html_e('Enter or select an image from the media library.', 'custom-image-login-logo'); ?></p>
    <?php
}

// ✅ 6. Enqueue admin scripts for media uploader and translations
function cill_enqueue_admin_scripts($hook) {
    // Load scripts only on the "Settings → General" page
    if ($hook !== 'options-general.php') {
        return;
    }

    // Load the WordPress media uploader
    wp_enqueue_media();

    // Enqueue the custom admin script
    wp_enqueue_script(
        'cill-admin-script',
        plugins_url('js/cill-admin.js', __FILE__),
        array('jquery'),
        '1.0',
        true
    );

    // Pass translation strings to JavaScript
    wp_localize_script('cill-admin-script', 'cill_admin', array(
        'choose_logo' => __('Choose Logo', 'custom-image-login-logo'),
        'use_image'   => __('Use this image', 'custom-image-login-logo'),
        'remove'      => __('Remove', 'custom-image-login-logo'),
        'enter_image' => __('Enter or select an image from the media library.', 'custom-image-login-logo'),
    ));
}
add_action('admin_enqueue_scripts', 'cill_enqueue_admin_scripts');

// ✅ 7. Ensure translations are available in JavaScript
function cill_localize_admin_script() {
    wp_localize_script('cill-admin-script', 'cill_localize', array(
        'choose_logo'   => __('Choose Logo', 'custom-image-login-logo'),
        'use_this_image' => __('Use this image', 'custom-image-login-logo'),
    ));
}
add_action('admin_enqueue_scripts', 'cill_localize_admin_script');