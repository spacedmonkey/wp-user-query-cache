=== WP User Query Cache ===
Contributors: spacedmonkey
Donate link: https://github.com/sponsors/spacedmonkey
Tags: comments, spam
Requires at least: 5.2
Tested up to: 5.2
Requires PHP: 5.6.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cache the results of query in WP_User_Query to save SQL queries

== Description ==

This plugin is just a feature plugin and should be considered a proof of concept for user query caching. For full information call at the WordPress core ticket [#40613](https://core.trac.wordpress.org/ticket/40613).

WP User Query Cache requires WordPress 5.2. Is designed to work with multisite. It is required it should be installed as an mu-plugin.

For this interested in user query performance, you may wish to also install the [WP User Roles](https://github.com/spacedmonkey/wp-user-roles) plugin.

== Installation ==

### Using The WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'wp-user-query-cache'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

### Uploading in WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `wp-user-query-cache.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

### Using FTP
1. Download `wp-user-query-cache.zip`
2. Extract the `wp-user-query-cache` directory to your computer
3. Upload the `wp-user-query-cache` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


## GitHub Updater

The WP User Query Cache includes native support for the [GitHub Updater](https://github.com/afragen/github-updater) which allows you to provide updates to your WordPress plugin from GitHub.

== Frequently Asked Questions ==

= This plugin doesn't have a UI, is the correct =
Yes, this it is not needed

== Changelog ==

= 1.0 =
* First release.
