=== WP-TTFGen ===
Contributors: vtroia, mdabbs
Donate link: http://www.ttfgen.com
Tags: ttf, truetype, font, fonts, seo, image renderer, jquery, images
Requires at least: 2.2
Tested up to: 3.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TTFGen allows you to embed fonts on your website by generating static images from your own CSS parameters. TTFGen is 100% cross browser compatible.

== Description ==
 
TrueType Font Generator, or TTFGen, is a combination of PHP and jQuery which allows you to use custom fonts on your website which are guaranteed to be cross browser compatible and SEO Friendly.

TTFGen automatically generates static text images based on your own set of parameters. A small jQuery script is used to call the PHP image renderer, which uses the TTF file to generate the image.

TTFGen is SEO friendly because it uses jQuery to replace text within your website after the page loads.


== Installation ==

1. Upload the entire `wp-ttfgen` folder to the `/wp-content/plugins/` directory.
1. Modify the two lines in the '/wp-ttfgen/scripts/config.php' file. Your correct full server path will be required for the script to operate properly.  
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Access TTFGen from the settings > TTFGen menu. 
1. Modify general settings as needed. If you are not sure about the jQuery settings, do not change them. 
1. Upload a TTF font via the 'Add New Font' section of the plugin. 
1. Set the CSS Font assignments to match your uploaded font. For example, assigning class 'test' to a specific font will cause the plugin to search your site for every instance of the CSS class 'test'. It will replace the text within the class with the rendered image.
1. Modify the class (or ID)'s CSS properties to assign fields such as color, size, etc.  
1. The plugin requires that the wp_footer() be loaded in your footer template. 


== Frequently Asked Questions ==

= Full list of FAQs available on our website = 
* http://www.ttfgen.com/faq/

= How exactly does the plugin work?  =

* Upload a TTF font via the plugin page
* Assign the font to a CSS class or ID: 
* Example: CSS Type: ID | CSS Name: myHeader 
* Add the CSS class 'myClass' to any tag in your PHP template that you want to change.
 
Ex: <h1 class="myClass">Your Text here</h1>
* Modify the CSS with font properties. ex: 
<pre>
   .myClass {
	border: 0px; 
	font-size: 22px; 
	text-align:left;
	color: #d0be8c; 
	text-transform:capitalize;
	}
</pre>


= What about Mouse Over / Hover colors?  =

TTFGen support rollover colors with SPAN tags! 

* Place a <SPAN> tag within your <a> tags. 
* Assign the class or ID of the span from the TTFGen plugin page. 
* Assign the rollover color via the plugin. 

= Can I see a live demo of TTFGen?  =
Yes, at http://www.ttfgen.com/demo/

= What image type is the text rendered as? =

Each image is rendered as a transparent PNG. 

= What CSS Properties can I specify? =

* background-color
* color
* font-size
* leading
* line-height
* height
* margin
* text-align
* text-transform
* width

= What if something isn't working? =

Please visit http://www.ttfgen.com/wordpress for support. Since this software is still in beta, it would be a tremendous help if you let us know about any bugs in our software. 

== Screenshots ==

1. Add Font section of the TTFGen admin page. 
2. CSS property selector of the TTFGen admin page. 


== Changelog ==

= 1.0.5 =
* Problems with plugin loading if you are using the built in jQuery file. 
* Path fixes for jQuery and TTF Plugin.  

= 1.0.4 =
* Fixed bug which would cause errors during installation if wp-content Uploads folder does not exist (applies to new installations). 
* Fixed path bug for font folder and font-cache creation which added an invalid folder path to the database.  

= 1.0.3 =
* MAJOR FIX: Fixed die statement which was called if directory permissions were not in place for the config file. 

= 1.0.2 =
* Fixed more path issues, this time relating to the plugin update. 

= 1.0.1 =
* Fixed the wordpress path issues, that were being causes by duplicate defined constants in other plugins. The constant path definitions were all modified to include TTF in their names to avoid any similar problems from occuring in the future. 

= 1.0.01 =
* Fixed error in base TTFGen javascript path. 

= 1.0 =
* Moved the default location of the Fonts and FontCache folders within the wp-content/uploads folder to prevent erasing all data on plugin updates. 
* Additional checks to see if fonts listed in database exist on the server and are readable. 
* Added hover color capabilities from within the WordPress admin
* Fixed double refresh bug
* Fixed path problem when wp-content is not in the root folder. 

= 0.9.7 =
* The Config File is now auto generated. You no longer need to update the file manually. 
* Ability to save changes to the general settings.  
* Ability to re-load default configuration. 


= 0.9.6 =
* Major Update / Bug Fixes
* Error / Status messages now appearing. 
* CSS Assignment updating / modifying now available. 
* Added font folder location displays to the general settings options
* Fixed bug when entering a '#' or a '.' for CSS ID or classes. 
* Added ability to specify a CSS element in additional to an ID or Class. 

= 0.9.5 =
* Fixed bug where URL text with parentheses '(' or ')' was not being rendered properly. 

= 0.9.4 =
* Major fix where the path to the wp-ttfgen plugin was incorrect, causing file not found errors in the javascript. 
* Correct path errors in the config.php
* Fixed jQuery script which used the font web name instead of the font file name 

= 0.9.3 =
* File uploading error caused by letter case and spaces fixed. 

= 0.9.2 =
* Fix for determining PHP maxUploadSize. The maxSize is now fixed at 1mb. 

= 0.9.1 =
* Update to read-me and version number to correct subversion repository problems.  

= 0.9 =
* Wordpress beta version based on the stable, standalone version. 

= 0.7 =
* Original alpha version. 

== Upgrade Notice ==

= 1.0 =
First major release. 

== Standalone ==

You do not need to use wordpress to use TTFGen. Visit www.ttfgen.com to download the standalone jQuery/PHP version. 

== Donations ==

We know that everyone's time is valuable. If this plugin saves you endless amounts of time from manually creating images for your website, we hope that you will consider a small $2 donation.

