=== Short-Lived Post Redirector ===
Contributors: ksstormnet
Tags: redirect, posts, admin, settings
Requires at least: 5.0
Tested up to: 5.8
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redirects missing posts of specified types to a relative URL or the post type's archive.

== Description ==

The Short-Lived Post Redirector plugin allows users with the Editor or Admin role to select post types and specify a relative URL for redirection. When a visitor tries to access a non-existent post of a selected type, they will be redirected to the specified URL or the first archive page of that post type if no URL is provided. The settings are accessible via an admin page, where the user can check the post types to redirect and provide a custom URL if desired.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/slp-redirector` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->SLPR Settings screen to configure the plugin.

== Frequently Asked Questions ==

= Can I redirect to an external URL? =

No, this plugin is designed to accept only relative URLs for redirection to avoid off-site redirects.

= What happens if I don't specify a custom URL? =

If no custom URL is provided, the plugin will redirect missing posts of the selected types to their first archive page.

== Changelog ==

= 1.0 =
* Initial release.
