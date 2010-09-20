=== Facebook Comments Importer ===
Contributors: Neoseifer22
Tags: comments, facebook
Requires at least: 2.9
Tested up to: 3.0.1
Stable tag: 1.1

Imports the comments posted on your facebook fan page (or public profile) to your blog.

== Description ==

Your blog has a Facebook fan page ? All of your blog posts are also posted on your fan page ?
This plugin is maybe for you. It checks your Facebook fan page every hour, then imports all Facebook comments back to your blog.
The plugin identify your blog posts on Facebook with the FB "link" attribute. So if the Facebook post links to you blog post, it should work.

Facebook Comments Importer is very easy to configure : just specify your Facebook fan page ID... and that's all.

Note : It should work with a normal Facebook profile that is not a fan page if the posts and comments are visible to everyone. But this functionnality has not been tested.

== Installation ==

This section describes how to install the plugin and get it working.

Use the automatic plugin installer of your WordPress admin, or do it manually :

1. Upload the `facebook-comment-importer` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure your Facebook informations under the 'Settings/FB Comments Importer' menu.
1. If all checks are green, just wait for new comments. If your FB comments are not imported after more than 2 hours, there is a problem with your configuration.

== Frequently Asked Questions ==

= What is the ID of my Facebook Fan Page ? =

For exemple :
1. if your Facebook Fan Page url is www.facebook.com/pages/BlogName/123456, your Fan Page ID is just 123456.
1. if your Facebook Fan Page url is just www.facebook.com/pages/MyName/, your Fan Page ID is MyName.

= Why is the FB Comments Importer settings page so long to show =

The settings page checks your Facebook page if all of your settings are ok in order to import your comments. If Facebook is a bit long to respond, this page could be slow.
However, FB Comments Importer does not slow down your blog in any way, it justs checks for comments every hour.

== Screenshots ==

1. Facebook Comments Importer configuration.

== Changelog ==
= 1.1 =* Avatar compatibility (thanks to Justin Silver's contribution)* Improve performance* Simplified check test to avoid time-outs from Facebook.
= 1.0.1 =
* Fix a bug with localisation

= 1.0 =
* First release