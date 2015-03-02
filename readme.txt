=== Plugin Name ===
Contributors: munger41
Donate link: http://www.maxizone.fr/?page_id=560
Tags: editor, chief, draft, multisite, author, stat
Requires at least: 3.5
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Helps wp multisite "chief editor" to manage all drafts, comments, authors and "for press" sends across the network. Also includes a calendar and full authors stats.

== Description ==

This plugin is aimed to help the multisite wordpress editor-in-chief in order to plan publication of posts.
Chief editor can manage all posts across all sites in the network. They are shown with a link to the article for quick reviewing or editing.
Chief editor can see all recent comments accross the network of a multisite install, a link allow the user to answer directly.
The Authors tab allow you to compare all authors efficiency accross the network. And give much more stats.
"For Press" automatic email is now possible.
Calendar for global view is available for chief editor of blog network.
Only editors (or higher roles) can use the plugin.

== Installation ==

1. Upload `chief-editor.zip` to the `/wp-content/plugins/` directory OR install with WP admin GUI at network level
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to one of the blogs settings menu -> "Chief editor" item should appear OR go directly to url : http://www.my_site.com/wp-admin/options-general.php?page=chief-editor-admin

== Frequently Asked Questions ==

[Post a comment here](http://www.termel.fr/ "Support")

== Screenshots ==

1. Easily see all drafts, approved and scheduled posts across the network
2. See comments of all blogs in the same place, and get a direct link to answer
3. Complete stats per author, clic on column head to sort, then on button to trace corresponding graphs
4. Horizontal Bars author stats using ChartNew.js lib
5. Pie Chart

== Changelog ==

= 3.0 =
* Most commented posts ever and most commented posts last month added

= 2.9.2 =
* Roles changed : Admin can see settings, Editors can see all but Settings, and special users with edit_others_posts, can review posts before published.

= 2.9.1 =
* Calendar added in order to easy scheduling of posts
* CSS image zoom
* preparing per blog chief editors

= 2.9 =
* bug fix for comments

= 2.8 =
* "For Press" automatic email sends

= 2.7 =
* compatible with default Edit Flow post statuses

= 2.4 =
* dynamic graphs added using ChartNew.js

= 2.3 =
* more authors stats added

= 2.2 =
* Authors stats added

= 2.1 =
* Single wordpress install (not multisite) ready

= 2.0 =
* Comments tab added in order to manage all recent comments accross the network
* Remove schedule functionnality because buggy

= 1.3 =
* Bug fix

= 1.2 =
* Colored lines according post status
* possibility to schedule/unschedule posts directly from chief-editor admin panel

= 1.1 =
* Translated to english
* Table layout improved

= 1.0 =
* First version