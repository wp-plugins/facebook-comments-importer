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
The plugin identify your blog posts on Facebook with the FB "link" attribute. So if the Facebook post links to you blog post, it will work.

Facebook Comments Importer is very easy to configure : just specify your Facebook fan page ID... and that's all.

Note : It should work with a normal Facebook profile that is not a fan page if the posts and comments are visible to everyone. But this functionnality has not been tested.

== Installation ==

This section describes how to install the plugin and get it working.

Use the automatic plugin installer of your WordPress admin, or do it manually :

1. Upload the `facebook-comment-importer` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure your Facebook informations under the 'Settings/FB Comments Importer' menu.
1. If the check is green, just wait for new comments. 
If your FB comments are not imported after more than 2 hours, it should be a problem with your configuration. It also can be a problem with Facebook itself. In this case, wait for 24 hours.
== Frequently Asked Questions ==

= What is the ID of my Facebook Fan Page ? =

For exemple :
1. if your Facebook Fan Page url is www.facebook.com/pages/BlogName/123456, your Fan Page ID is just 123456.
1. if your Facebook Fan Page url is just www.facebook.com/pages/MyName/, your Fan Page ID is MyName.
= The test is ok but the comments are not imported, or it can make hours to be imported. =The plugin checks the Facebook API every hour. However, sometimes the Facebook API is very slow or does not respond. In this case, the plugin will check for Facebook on the next hour.Some days, the Facebook API only works well at night. So your comments will be imported at night.
== Screenshots ==

1. Facebook Comments Importer configuration.

== Changelog ==
= 1.1 =* Facebook avatars compatibility (thanks to Justin Silver's contribution)* Author names of commenters can be customized (for ex. "%first_name%, on Facebook")* Improved performance.* Simplified check test to avoid time-outs from Facebook.
= 1.0.1 =
* Fix a bug with localisation

= 1.0 =
* First release