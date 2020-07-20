=== Site Speed Test - SpeedGuard === 
Contributors: sabrinazeidan
Tags: speed, speed test, site speed, performance, optimization
Requires at least: 4.7
Tested up to: 5.4.2
Stable tag: 1.7
Requires PHP: 5.6
License: GPLv2 or later 
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Keeps an eye on your website’s speed for you; every single day for free. 

== Description ==
Test site speed performance daily, get notified if it's slow, get detailed reports. Right in your WordPress dashboard. It's free. 

_Is it your website’s slow speed holding you back from getting more visitors from Google?_

Get the definite answer in less than 5 minutes:
1. Install SpeedGuard
2. Add the most important pages of your website to run a site speed test
3. Get a complete picture of your site’s speed health in a few minutes

#### With SpeedGuard you get:

* <strong>unlimited Google Lighthouse tests</strong>
* <strong>automatic </strong> everyday monitoring
* <strong>desktop and mobile</strong> testing
* <strong>daily reports</strong> about your site speed health are delivered straight to your inbox. If site performance gets worse, you'll be able to prevent big problems asap
* <strong>links to the Google PageSpeed Insights reports</strong> which you can pass to the performance engineer to improve your site speed
* <strong>tests are completely automated</strong> since first time setup is done
* <strong>easy to use</strong> — just pick pages of your website that you would like to monitor
* <strong>It's free :)</strong>

No need to guess whether your website is slow or fast - get the definite answer in your WordPress Dashboard in a few minutes.

== Idea Behind ==
Today, if your website loads slow, there is no need to even bother with any other optimization at all. 

Page load time is one of Google’s top priorities for 2020 and it’s also its ranking signal. <strong>If Google’s crawler can't access your website because it's loading slow or throwing errors, it will never proceed further with indexation and ranking</strong>, and as a result, your website won't get any decent organic traffic.

I wanted an easy-to-use tool to warn me in case my website load time may harm it’s search rankings. I wanted a native WordPress solution, with all information available from the dashboard, simple but still informative, a guard who will do the monitoring every day and ping me, in case something goes wrong. 
I have not found one and that's why I've built this plugin. 

I'll be happy to know that you find it useful as well, feel free to leave a review :)

== Screenshots ==
1. Plugin installation
2. Getting API Key
3. Running a site speed test
3. Settings: widgets, tests and notifications
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


== Translations ==

* English - default, always included
* Russian - Привет!

*Note:* No your language yet? You can help to translate this plugin to your language [right from the repository](https://translate.wordpress.org/projects/wp-plugins/speedguard), no extra software needed.


== Credits ==
* Thanx to Baboon designs from the Noun Project for the timer icon.

== Changelog ==

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


