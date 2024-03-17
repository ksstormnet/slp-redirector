<?php
/**
 * Plugin Name: SLP Redirector
 * Description: Allows editors and admins to set specific redirection rules for missing posts of each post type to a relative URL or the post type's archive page. Use case: a site which produces posts which are only fresh for a short time and routinely deletes old posts when they are no longer relevant.
 * Version: 1.1
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
        __('SLP Redirector', 'slp-redirector'), 
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
            wp_nonce_field('slpr_save_settings', 'slpr_nonce_field');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'slpr_register_settings');
function slpr_register_settings() {
    register_setting('slpr_options_group', 'slpr_options', 'slpr_options_validate');
    add_settings_section('slpr_main', __('Main Settings', 'slp-redirector'), 'slpr_section_text', 'slpr_settings');

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
    $disabled = empty($checked) ? 'disabled' : '';

    echo "<tr><td><input type='checkbox' class='slpr-enable-redirect' name='slpr_options[post_types][$post_type]' $checked /></td>";
    echo "<td><input type='text' class='slpr-url-input' name='slpr_options[urls][$post_type]' value='$url_value' placeholder='/your-relative-path' $disabled /></td>";
    echo "<td><button class='button slpr-save-row' data-post-type='$post_type' $disabled>Save</button></td></tr>";
}

add_action('admin_enqueue_scripts', 'slpr_enqueue_scripts');
function slpr_enqueue_scripts($hook) {
    if ('settings_page_slpr_settings' !== $hook) {
        return;
    }

    wp_enqueue_script('slpr-ajax-script', plugins_url('/js/slpr-ajax.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('slpr-ajax-script', 'slpr_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('slpr_ajax_nonce')
    ));

    // Enqueue the CSS file
    wp_enqueue_style('slpr-style', plugins_url('/css/slpr-style.css', __FILE__));
}

add_action('wp_ajax_slpr_save_redirect', 'slpr_save_redirect');
function slpr_save_redirect() {
    check_ajax_referer('slpr_ajax_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have sufficient permissions to access this page.');
    }

    $options = get_option('slpr_options');
    $post_type = sanitize_text_field($_POST['post_type']);
    $url = sanitize_text_field($_POST['url']);

    $options['post_types'][$post_type] = true;
    $options['urls'][$post_type] = $url;

    update_option('slpr_options', $options);

    wp_send_json_success('Redirect saved');
}

register_uninstall_hook(__FILE__, 'slpr_uninstall');
function slpr_uninstall() {
    delete_option('slpr_options');
}
