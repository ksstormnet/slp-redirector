<?php
/**
 * Plugin Name: SLP Redirector
 * Description: Allows editors and admins to set specific redirection rules for missing posts of each post type to a relative URL or the post type's archive page. Use case: a site which produces posts which are only fresh for a short time and routinely deletes old posts when they are no longer relevant
 * Version: 1.0
 * Author: Scott Roberts, KSStorm <dev@ksstorm.net>
 * Text Domain: slp-redirector
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_menu', 'slpr_add_admin_page');
function slpr_add_admin_page() {
    add_options_page(
        __('SLP Redirector Settings', 'slp-redirector'), 
        __('SLPR Settings', 'slp-redirector'), 
        'manage_options', 
        'slpr_settings', 
        'slpr_settings_page'
    );
}

function slpr_settings_page() {
    ?>
    <div class="wrap">
        <h2><?php _e('SLP Redirector Settings', 'slp-redirector'); ?></h2>
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

add_action('admin_init', 'slpr_register_settings');
function slpr_register_settings() {
    register_setting('slpr_options_group', 'slpr_options', 'slpr_options_validate');
    add_settings_section('slpr_main', __('Main Settings', 'short-lived-post-redirector'), 'slpr_section_text', 'slpr_settings');

    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $post_type) {
        add_settings_field(
            'slpr_post_type_' . $post_type->name,
            $post_type->labels->name,
            'slpr_post_type_field',
            'slpr_settings',
            'slpr_main',
            ['post_type' => $post_type->name]
        );
    }
}

function slpr_section_text() {
    echo '<p>' . __('Select the post types for which missing posts should be redirected, and specify a custom redirect URL for each.', 'slp-redirector') . '</p>';
}

function slpr_post_type_field($args) {
    $options = get_option('slpr_options');
    $post_type = $args['post_type'];
    $checked = isset($options['post_types'][$post_type]) ? 'checked="checked"' : '';
    $url_value = isset($options['urls'][$post_type]) ? $options['urls'][$post_type] : '';

    echo "<input type='checkbox' name='slpr_options[post_types][$post_type]' $checked /> " . __('Enable Redirect', 'slp-redirector') . "<br />";
    echo "<input type='text' name='slpr_options[urls][$post_type]' value='$url_value' placeholder='/your-relative-path' />";
    echo "<p>" . __('Enter a relative URL for redirects. Only applicable if redirect is enabled for this post type.', 'slp-redirector') . "</p>";
}

function slpr_options_validate($input) {
    $new_input = ['post_types' => [], 'urls' => []];
    foreach (get_post_types(['public' => true]) as $pt) {
        if (isset($input['post_types'][$pt])) {
            $new_input['post_types'][$pt] = true;
            $relative_url = trim($input['urls'][$pt]);
            if (!preg_match('/^\/[^\/].*/', $relative_url)) {
                add_settings_error('slpr_urls_' . $pt, 'slpr_invalid_url', sprintf(__('Invalid URL for %s. Must be a relative path starting with "/".', 'short-lived-post-redirector'), $pt));
            } else {
                $new_input['urls'][$pt] = sanitize_text_field($relative_url);
            }
        } elseif (!empty($input['urls'][$pt])) {
            add_settings_error('slpr_urls_' . $pt, 'slpr_unselected_post_type', sprintf(__('URL specified for %s, but the post type is not selected.', 'short-lived-post-redirector'), $pt));
        }
    }
    return $new_input;
}

add_action('template_redirect', 'slpr_redirect_missing_posts');
function slpr_redirect_missing_posts() {
    if (is_404()) {
        $options = get_option('slpr_options');
        if (!empty($options['post_types'])) {
            $queried_object = get_queried_object();
            if (isset($queried_object->post_type) && array_key_exists($queried_object->post_type, $options['post_types'])) {
                $redirect_url = !empty($options['urls'][$queried_object->post_type]) ? home_url($options['urls'][$queried_object->post_type]) : get_post_type_archive_link($queried_object->post_type);
                if ($redirect_url) {
                    wp_redirect($redirect_url, 301);
                    exit;
                }
            }
        }
    }
}

register_uninstall_hook(__FILE__, 'slpr_uninstall');
function slpr_uninstall() {
    delete_option('slpr_options');
}
