=== Plugin Name ===
Contributors: akerbos87
Tags: tags, filter, tagcloud
Requires at least: 3.2.0
Tested up to: 3.3.1
Stable tag: 1.0

Adds parameters to Wordpress' tagcloud for filtering out insignificant tags.

== Description ==
*Significant Tags* adds two possible arguments to Wordpress' tagcloud function (`wp_tag_cloud`) that give you more control over what tags should be shown. In particular, this plugin will cut the most rarely and/or the most often occurring tags, the specific numbers depending on the parameter chosen.

When calling `wp_tag_cloud`, add key `drop_bottom` (and/or `drop_top`) with one of the following values:

* `'N'` with N an integer. Will drop the tags with the N smallest (largest) post counts.
* `'Nc'` with N an integer. Will drop tags with less (more) than N posts.
* `'N%'` with N  an integer. Will drop tags with the N percent smallest (largest) post counts.
* `'Xs'` with X a float. Will drop tags with post count more than X times standard deviation below (above) the average of all counts.

Please direct your support questions to the [forums](http://wordpress.org/tags/significant-tags?forum_id=10) and report bugs or ideas [here](http://bugs.verrech.net/thebuggenie/significanttags).

== Installation ==
Install and activate via Wordpress' plugin management.

== Frequently Asked Questions ==
= What is a standard deviation? =
It is a measure for a set of numbers telling you how far numbers are on average away from the average. For details, look the term up on Wikipedia.

You can use it for setting up tag clouds that scale with your blog, in a sense. Other than dropping a fixed number or percentage of tags, it will truly remove exactly those tags with significantly less posts than your average tag has.

= How can I help? =
You can

* use *Significant Tags*,
* vote on the *Wordpress* plugin portal for it,
* report bugs and/or propose ideas for improvement and
* blog about your experience with it.

== Changelog ==
= 1.0 =
Initial Release
