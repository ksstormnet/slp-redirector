<?php
/**
 * Plugin Name: Short-Lived Post Redirector
 * Description: Redirects missing posts of specified types to a relative URL or the post type's archive.
 * Version: 1.0
 * Author: Scott Roberts, KSStorm <dev@ksstorm.info>
 */

// Security measure
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// register the uninstall hook to clean the redirections from the database on uninstall
register_uninstall_hook(__FILE__, 'slpr_uninstall');
function slpr_uninstall() {
    delete_option('slpr_options');
}

// Register the admin settings page under the Settings menu
add_action('admin_menu', 'slpr_add_admin_page');
function slpr_add_admin_page() {
    add_options_page('Short-Lived Post Redirector Settings', 'SLPR Settings', 'manage_options', 'slpr_settings', 'slpr_settings_page');
}

// Admin page callback
function slpr_settings_page() {
    ?>
    <div class="wrap">
        <h2>Short-Lived Post Redirector Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('slpr_options_group');
            do_settings_sections('slpr_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'slpr_register_settings');
function slpr_register_settings() {
    register_setting('slpr_options_group', 'slpr_options', 'slpr_options_validate');
    add_settings_section('slpr_main', 'Main Settings', 'slpr_section_text', 'slpr_settings');
    add_settings_field('slpr_post_types', 'Post Types to Redirect', 'slpr_post_types_field', 'slpr_settings', 'slpr_main');
    add_settings_field('slpr_custom_url', 'Custom Redirect URL (Relative)', 'slpr_custom_url_field', 'slpr_settings', 'slpr_main');
}

function slpr_section_text() {
    echo '<p>Select the post types for which missing posts should be redirected, and specify a custom redirect URL if desired.</p>';
}

// Display and fill the form field for post types
function slpr_post_types_field() {
    $options = get_option('slpr_options');
    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $post_type) {
        $checked = isset($options['post_types'][$post_type->name]) ? 'checked="checked"' : '';
        echo "<input type='checkbox' name='slpr_options[post_types][{$post_type->name}]' $checked /> {$post_type->labels->name}<br />";
    }
}

// Display and fill the form field for the custom URL, with modification for relative URLs
function slpr_custom_url_field() {
    $options = get_option('slpr_options');
    echo "<input id='slpr_custom_url' name='slpr_options[custom_url]' size='40' type='text' value='{$options['custom_url']}' />";
    echo "<p>Enter a relative URL (e.g., '/path/to/redirect'). The site URL will be automatically prepended.</p>";
}

// Validate user input, with adjustment for relative URLs
function slpr_options_validate($input) {
    $new_input = [];
    foreach ($input['post_types'] as $pt => $val) {
        if (post_type_exists($pt)) {
            $new_input['post_types'][$pt] = $val;
        }
    }
    // Ensure the URL is relative and sanitize
    $relative_url = trim($input['custom_url']);
    if (!preg_match('/^\/[^\/].*/', $relative_url)) {
        add_settings_error('slpr_custom_url', 'slpr_invalid_url', 'The custom URL must be a relative path starting with a "/".');
        $relative_url = ''; // Reset invalid input
    }
    $new_input['custom_url'] = sanitize_text_field($relative_url);
    return $new_input;
}

// Adjusted redirection logic to prepend site URL to the relative path
add_action('template_redirect', 'slpr_redirect_missing_posts');
function slpr_redirect_missing_posts() {
    if (is_404()) {
        $options = get_option('slpr_options');
        if (isset($options['post_types']) && !empty($options['post_types'])) {
            $queried_object = get_queried_object();
            if (isset($queried_object->post_type) && in_array($queried_object->post_type, array_keys($options['post_types']))) {
                $redirect_url = !empty($options['custom_url']) ? home_url($options['custom_url']) : get_post_type_archive_link($queried_object->post_type);
                if ($redirect_url) {
                    wp_redirect($redirect_url, 301);
                    exit;
                }
            }
        }
    }
}
