=== WordPress Site Speed Test Plugin - SpeedGuard === 
Contributors: sabrinazeidan
Tags: speed, site speed, performance, optimization, SEO
Requires at least: 4.7
Tested up to: 5.4
Stable tag: 1.5
Requires PHP: 5.4
License: GPLv2 or later 
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Checks your website loading speed on daily basis. It's free.  

== Description ==
Test site speed performance daily, get notified if it's slow, get detailed reports. Right in your WordPress dashboard. It's free. 

_Is it your website’s slow speed holding you back from getting more visitors from Google? Not sure?_

Get the definite answer in less than 10 minutes with SpeedGuard plugin:
1. Install SpeedGuard
2. Add the most important pages of your website to test page speed
3. Get a complete picture of your site’s speed health in 10 minutes

#### With SpeedGuard you get:

* speed tests of the <strong>65 most important pages of your website</strong>
* <strong>reliable results</strong> — every page is tested 3 times to ensure accurate load data tracking
* <strong>up-to-date information</strong> — tests are run and results are updated every single day
* <strong>real users experience</strong> — you can choose both the location and Internet connection speed
* <strong>daily reports</strong> about your site speed health are delivered straight to your inbox. If site performance gets worse, you'll be able to prevent big problems asap
* <strong>detailed data for every test with just one click</strong> which you can pass to the performance engineer to improve your site speed
* <strong>tests are completely automated</strong> since first time setup is done
* <strong>specific recommendations</strong> for improving your site speed results
* <strong>easy to start</strong> — you need just 10 minutes to set up the plugin for the first time

* <strong>It's free</strong>

Stop losing visitors because of slow site speed. No need to guess whether your website is really slow. Install SpeedGuard and get the definite answer in your WordPress Dashboard in 10 minutes.

== Idea Behind ==
Doing SEO for clients and my own projects for the past 10 years, I noticed the solution to the not-getting-traffic problem often can be found in quite basic things: sitemap, robots.txt, duplicates, redirections. But, first of all, page speed. 
 
Today, if your website loads slow, there is no need to even bother with any other optimization at all. 

Page load time is one of Google’s top priorities for 2020 and it’s also its ranking signal. <strong>If Google’s crawler can't access your website because it's loading slow or throwing errors, it will never proceed further with indexation and ranking</strong>, and as a result, your website won't get any decent organic traffic.

I wanted an easy-to-use tool to warn me in case my website load time may harm it’s search rankings. I wanted a native WordPress solution, with all information available from the dashboard, simple but still informative, a guard who will do the monitoring every day and ping me, in case something goes wrong. I have not found one and that's why I've built SpeedGuard plugin. 

I'll be happy to know that you find it useful as well!

== Screenshots ==
1. Plugin installation
2. Getting API Key
3. Running speed tests
3. Settings: widgets, tests and notifications
== Installation ==
= Automatic plugin installation: =
1. Go to Plugins > Add New in your WordPress Admin
2. Search for SpeedGuard plugin
3. Click Install SpeedGuard
4. Activate SpeedGuard after installation
5. Follow further instructions to get API key and start using SpeedGuard
= Getting API Key for free testing: =
1. Fill out this [short form](http://www.webpagetest.org/getkey.php)
2. Check your email and confirm the request
3. You will receive the email with "WebPagetest API Key" subject. Copy your API key from this email into the field on SpeedGuard->Settings page and press "Save API Key".

= Configuration: =
Go to the SpeedGuard->Settings page to set the scan frequency and whether you prefer to be emailed. 

For example, you can set tests to run every day and send you a performance report every day, too. 

Or you may want to receive an email just in case the average site speed is worse than say, 5 seconds (adjustable too). 
In this case, the plugin will perform tests every day but only send you the warning if your site is loading slower than the time you have set.

== Frequently Asked Questions ==

= What service is used for performing tests? =
SpeedGuard is using [WebPageTest](https://www.webpagetest.org/) API, to get reliable test results.

= How accurate the results are? =
Each test is run 3 times (cache disabled), then the average is calculated and displayed in order to get accurate results.
 
= Where the site speed is tested from? =
You can choose one of the following locations to test:
* Dulles, VA
* California, USA
* London, UK
* Mumbai, India


= Are the speed results for desktop or mobile users? =
You can choose the type of Internet connection to test:
* Cable - 5 Mbps down, 1 Mbps up, 28ms first-hop RTT, 0% packet loss
* 3GSlow - 400 Kbps down and up, 400 ms first-hop RTT, 0% packet loss
* 3G - 1.6 Mbps down, 768 Kbps up, 300 ms first-hop RTT, 0% packet loss
* 4G - 9 Mbps down and up, 170 ms first-hop RTT, 0% packet loss

= How many tests I can perform per day? =
You can run up to 65 tests per day. It can be 65 different URLs from your website tested once per day or 65 tests of one specific page — it’s up to you. There is a widget on SpeedGuard pages that shows how many tests you have already used.


== Translations ==

* English - default, always included
* Russian - Привет!

*Note:* No your language yet? You can help to translate this plugin to your language [right from the repository](https://translate.wordpress.org/projects/wp-plugins/speedguard), no extra software needed.


== Credits ==
* Thanx to Baboon designs from the Noun Project for the timer icon.

== Changelog ==

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
* WordPress Multisite support is paused in in this version, but will be provide in the next one with better performance-wise solution for the large networks
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
