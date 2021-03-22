=== Site Speed Test - SpeedGuard === 
Contributors: sabrinazeidan
Tags: speed, page speed, test speed, performance, optimization
Requires at least: 4.7
Tested up to: 5.7
Stable tag: 1.8.4
Requires PHP: 5.6
License: GPLv2 or later 
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Keeps an eye on your website’s speed for you; every single day for free. 

== Description ==
**Google PageSpeed Insights right in your WordPress dashboard. 
It's free.**
<strong>Test site speed performance daily, get notified if it's slow, get detailed reports.</strong>

[youtube https://www.youtube.com/watch?v=y_RvQEhdq9c]

== With SpeedGuard you get: ==

* <strong>unlimited Google Lighthouse tests (PageSpeed Insights API)</strong>
* <strong>automatic </strong> everyday monitoring
* <strong>desktop and mobile</strong> testing
* <strong>daily reports</strong> about your site speed health are delivered straight to your inbox. If site performance gets worse, you'll be able to prevent big problems asap
* <strong>links to the Google PageSpeed Insights reports</strong> which you can pass to the performance engineer to improve your site speed
* <strong>tests are completely automated</strong> since first time setup is done
* <strong>easy to use</strong> — just pick pages of your website that you would like to monitor
* <strong>It's free :)</strong>

No need to guess whether your website is slow or fast - get the definite answer in your WordPress Dashboard in a few minutes.

== Test speed of different types of content in WordPress :==

* Posts
* Pages
* Events
* WooCommerce Products
* any other Custom Post Type
* Archives
* Categories
* Tags
* any other Custom Taxonomy

= Idea Behind =
Today, if your website loads slow, there is no need to even bother with any other optimization at all. 

Page load time is one of Google’s top priorities for 2020 and it’s also its ranking signal. <strong>If Google’s crawler can't access your website because it's loading slow or throwing errors, it will never proceed further with indexation and ranking</strong>, and as a result, your website won't get any decent organic traffic.

I wanted an easy-to-use tool to warn me in case my website load time may harm it’s search rankings. I wanted a native WordPress solution, with all information available from the dashboard, simple but still informative, a guard who will do the monitoring every day and ping me, in case something goes wrong. 
I have not found one and that's why I've built this plugin. 

I'll be happy to know that you find it useful as well, feel free to leave a review :)

== Screenshots ==
1. HomePage is tested on activation
2. Add pages you want to test
3. View PageSpeed Insights reports
4. Choose Mobile or Desktop
5. Choose when you would like to get notified

== Installation ==
= Automatic plugin installation: =
1. Go to Plugins > Add New in your WordPress Admin
2. Search for SpeedGuard plugin
3. Click Install SpeedGuard
4. Activate SpeedGuard after installation
5. Follow further instructions to add pages that you want to test


= Configuration: =
Go to the SpeedGuard -> Settings page to set the scan frequency and whether you prefer to be emailed. 

For example, you can set tests to run every day and send you a performance report every day, too. 

Or you may want to receive an email just in case the average site speed is worse than say, 5 seconds (adjustable too). 
In this case, the plugin will perform tests every day but only send you the warning if your site is loading slower than the time you have set.

== Frequently Asked Questions ==

= How tests are performed? =
Starting from version 1.7 SpeedGuard is using [Google PageSpeed Insights API](https://developers.google.com/speed/pagespeed/insights/) which uses [Lighthouse](https://developers.google.com/web/tools/lighthouse) technology to perform tests. 

= Do I need Google PageSpeed Insights API key to use SpeedGuard? =
No, you don't. Just add pages you need to test. 

= Are the speed results for desktop or mobile users? =
You can choose the type of device to emulate:
* Desktop
* Mobile

= Is it compatible with WordPress Multisite? =
It is! Use per-site activation.

= Where can I suggest a new feature or report a bug? =
On [SpeedGuard's GitHub repo](https://github.com/sabrina-zeidan/speedguard)! 

= Translations =

* English - default, always included
* Russian - Привет!

*Note:* No your language yet? You can help to translate this plugin to your language [right from the repository](https://translate.wordpress.org/projects/wp-plugins/speedguard), no extra software needed.


= Credits =
* Thanx to Baboon designs from the Noun Project for the timer icon.

== Changelog ==
= Version 1.8.4 - March 22, 2020 =
* [Fixed] Error on date archive pages 
* [Fixed] Homepage can be added multiple times 
* [Fixed] Site's average is not updated properly when tests are deleted
* [Tweak] jQuery independence: all functions use vanilla JS now

= Version 1.8.3 - November 9, 2020 =
* [Fixed] Threshold error (5 minutes + timezone) after WordPress 5.3  
* [Fixed] Settings are being reset to defaults
* [Fixed] PHP Warning: Illegal string offset 'displayValue' in Admin bar when test is in progress
* [Fixed] Critical error on custom post type archive page 
* [Fixed] Tests for terms pages were not being deleted on uninstall
* [Fixed] Styles and scripts loaded for not logged-in user after version 1.8
* [Fixed] 504 admin-ajax.php error (or inifinite spinning) on bulk retest
* [Tweak] Backward compatibility with PHP 5.6
* [Tweak] Wait time before retesting reduced to 3 minutes

= Version 1.8.2 - Septemeber 9, 2020 =
* Typo fixed

= Version 1.8.1 - Septemeber 9, 2020 =
* [Fixed] Error happened on some installs: Unexpected end of file in ../speedguard/admin/class-speedguard-admin.php on line 403
* [Fixed] Error happened on CPT's pages in wp-admin:  Object of class WP_Error could not be converted to string in  ../speedguard/admin/includes/class.widgets.php on line 80
* [Tweak] REST API Internal + Auth security improved
* [Tweak] Automatically re-test if monitored page is added again

= Version 1.8 - August 10, 2020 =

* [New] Support for archives is added
* [New] Tests results can be sorted now (by time, URL and speed)
* [New] Homepage test is added automatically on plugin activation
* [Tweak] Tests are run with AJAX in the background
* [Tweak] Already guarded items are excluded from autocomplete
* [Tweak] Type-in validation improved
* [Tweak] Settings and Tests links are added to plugin's tab on the Plugins page
* [Fixed] Homepage can't be added if it an archive
* [Fixed] Sanitization type-in doesn't work in all cases
* [Fixed] Upcoming email notification is sent to the old email after it's been updated
* [Fixed] Notice to wait for 5 minutes before next run stays even after 5 minutes passed
* [Fixed] Email report contains a line with no results if the test is in running at the moment

= Version 1.7 =

* As WebPageTest.org stopped providing public API keys, SpeedGuard switched to make tests using [Google PageSpeed Insights API](https://developers.google.com/speed/pagespeed/insights/) which uses [Lighthouse](https://developers.google.com/web/tools/lighthouse) technology.
* Tracked performance metrics is [Largest Contentful Paint](https://web.dev/lcp/)
* Minor bugs fixed

_If you've got working WebPageTest API key and want to keep using it to run tests, you still can use [SpeedGuard version 1.6](https://github.com/sabrina-zeidan/speedguard/releases/tag/v1.6), but mind that it's not going to be supported/updated anytime soon._

= Version 1.6 =
* Performance of external requests improved (tips and API credits)
* Minor bugs fixed

= Version 1.5.1 =
* Typo update


= Version 1.5 =
* WordPress Multisite support (per site activation)
* Choice of connection type
* Choice of location
* Better report email styling
* Minor bugs fixed

= Version 1.4.1 =
* Language packs update

= Version 1.4 =
* Any URLs from current website can be added directly to the input field
* Fully Loaded in reports changed Speed Index to reflect user experience better https://sites.google.com/a/webpagetest.org/docs/using-webpagetest/metrics/speed-index
* Admin-ajax.php is relaced with WP REST API
* WordPress Multisite support is paused in in this version, but will be provided in the next one with better performance-wise solution for the large networks
* Minor bugs fixed

= Version 1.3.1 =
* Minor bugs fixed

= Version 1.3 =
* Pages and custom post types support added.

= Version 1.2.2 =
* Minor bugs fixed, some notes added.

= Version 1.2.1 =
* Language bug fixed.

= Version 1.2 =
* Multisite support added. 

= Version 1.1.0 =
* Tests page view updated.

= Version 1.0.0 =
* Initial public release.


