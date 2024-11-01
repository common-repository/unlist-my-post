=== Unlist My Post ===
Contributors: danieltj
Tags: post, hide, unlist, filter, archive
Requires at least: 4.6
Tested up to: 5.1
Stable tag: 3.1
License: GNU GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Unlist your posts so you'll need a link to read them.

== Description ==

Unlist My Post lets you easily unlist posts, pages or any other custom post type from listings or archive pages and means only those with a link can read the content.

= Developers =

For information regarding the hooks and integrations in this plugin, refer to the wiki on the [GitHub repository](https://github.com/danieltj27/Unlist-My-Post/wiki).

== Installation ==

1. Download, unzip and upload the package to your plugins directory.
2. Log into the dashboard and activate within the plugins page.
3. Edit any post and check the unlist option in the listings meta box.

== Frequently Asked Questions ==

= How do I unlist a post or page? =

Edit any post, page or custom post type and then tick the box in the listings meta box and save your changes.

= Can I still read unlisted posts? =

Yes, when a post is unlisted it won't be shown in any post listings but it will still be publicly accessible to those who have a direct link to the content.

= Can I stop people reading unlisted posts? =

No, unlisted posts are hidden away but not private. If you would like to stop people reading unlisted posts, you should change the post status to private instead.

= Why are unlisted posts still being listed? =

This plugin uses a selection of hooks and filters to exclude the unlisted posts and pages but there is the potential for compatibility issues with other plugins. Always test the plugin before using on a production site.

= Can I choose which post types can be unlisted? =

Yes, you can. To do this, you will need to use the `unlistable_post_types` filter hook which will pass an array of post types that can be unlisted.

== Screenshots ==

1. The setting that is shown on the edit post screen.
2. The column that is added to the posts list table.

== Changelog ==

Refer to the [GitHub repository](https://github.com/danieltj27/Unlist-My-Post) for information on version history.
