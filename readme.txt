=== TinyPass ===
Contributors: wordpress@tinypass.com
Tags: tinypass, permium content, paid content, monetization, micro payment, access control
Requires at least: 3.1.0
Tested up to: 3.1.0
Stable tag: 0.9

Provide integration between wordpress and TinyPass service

== Description ==

TinyPass is a simple service that allows any web publisher to easily accept quick and secure payments for access to any type of content.

The TinyPass platform can process payments as small as two-cents, and doesn't require changing your site's existing structure and layout.

See http://www.tinypass.com/

== Installation ==

PHP requirements: PHP 5.2+, mbstring, mcrypt

1. Upload TinyPass for WordPress to your wp-content/plugins directory.
2. Activate it in "Plugins" menu in WordPress.
3. Go to Plugins->TinyPass Configuration and enter your aid, secret_key, and environment.  This values can be retrieved from your
www.tinypass.com 'merchant' account.  For testing purposes, a default values have been provided.
4. To enabled tinypass, create a new post and enabled TinyPass checkbox.  Choose the price and the access period.  
Save the post and then attempt to 'view the post'


How TinyPass can be enabled

1)Basic Case - TinyPass enabled per page or post
	This scenario is the most basic usage of TinyPass.  A single page/post will be protected behind TinyPass.
	When creating a page/post, simple click the "Modify Options" under the TinyPass Options box to configure pricing behavior.
	The most basic configuration requires only specifying a price.  TinyPass can be enabled/disabled by clicking the 'Enable' checkbox.

	Page/post content will be truncated when access is denied.  First it will check for a valid excerpt, if no excerpt is specified
	then it truncate the content based on wordpress excerpt length.  Lastly, you can restrict content by using the <!--more--> wordpress
	tag.  TinyPass will hide everything after the <!--more--> when active.

2)Inline Case
	This is essentially similar to the first case except that the TinyPass configuration defined 'inline' in the content body.
	The format for inline TinyPass is:
			<tinypass price="1.99" access="1 day" caption="custom caption" name="custom name"/>
	The attributes are:
			price = price of access - required
			access = e.g. "1 day", "2 weeks", "1 month", "4 hours" - optional
			caption = custom text on price option - optional
			name = title of the article - optional and will default to post title

	Additionally, all the content AFTER the tinypass tag will be hidden so placement of the inline tinypass element is critical.

3)Tags
	TinyPass can now be enabled on wordpress tags.  This means that sections or groups of articles can be accessible via one access ticket or purchase.
	This tags are first level wordpress tags and our configured under the TinyPass plugin sections.  There tags can be created and TinyPass options specified.
	
	
4) Post + Tag
	If a post has been configured for TinyPass and that same post has a TinyPass enabled tag, then an UpSell ticket will be created automatically.
	In this case, the user will be presented with an option to buy the single article access OR to purchase access to all the articles in this tag(section)
		

Inline vs Post Enabled
	In the case where a post has TinyPass definied inline as well as through the basic admin console, the 'inline' configuration will have precedence and the
	basic configuration will be ignored.


Global Settings
	Under the TinyPass Plugin configuration page, TinyPass can be disabled without needing to uninstall.  You can use this option to temoparaily turn off TinyPass
	and access will be granted to all content
			



== Frequently Asked Questions ==

== Changelog ==

0.9
Changed the default sandbox app

0.8
TinyPass enabled tags, pages/posts, and inline.  Better configuration options and friendly TinyPass config popup

0.6
Changed default sandbox aid/key

0.5
Allows custom denied message error
Fixed bad ticket HTML
Added enabled/disable for global tinypass enable
Added support for both sandbox/prod aid/keys

== Upgrade Notice ==

