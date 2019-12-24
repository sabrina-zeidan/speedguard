=== SpeedGuard === 
Contributors: sabrinazeidan
Tags: speed, page load time, site speed, performance, optimization, SEO, page speed, search engine optimization
Requires at least: 4.7
Tested up to: 4.9.4 
Stable tag: trunk 
Requires PHP: 5.4
License: GPLv2 or later 
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Monitors load time of 65 most important pages of your website. Every single day. For free.  

== Description ==
 
_Is it your website slow speed that is holding you back from getting more visitors from Google? Not sure?_

Get the definite answer less than in 10 minutes with SpeedGuard plugin:
1. Install SpeedGuard
2. Add the most important pages of your website to be tested
3. Get a complete picture of your site speed health in 10 minutes

#### With SpeedGuard you get:

* speed tests of <strong>65 most important pages of your website</strong>
* <strong>reliable results</strong> — every page is tested 3 times to ensure accurate load data tracking
* <strong>up-to-date information</strong> — tests are run and results are updated every single day
* <strong>daily reports</strong> about your site speed health are delivered straight to your inbox. In case site performance get worse you'll be able to prevent big problems asap
* <strong>detailed data for every test with just one click</strong> which you can pass to the performance engineer to improve your site speed
* <strong>tests are completely automated</strong> since first time setup is don
* <strong>specific recommendations</strong> for improving your site speed results
* <strong>easy to start</strong> — you need just 10 minutes to set up plugin for the first time
* <strong>tests are completely automated</strong> since first time setup is done
* <strong>It's free</strong>

Stop losing visitors because of slow site speed. No need to guess whether your website is really slow. Install SpeedGuard and get the definite answer in your WordPress Dashboard in 10 minutes.

== Idea Behind ==
Doing SEO for clients and my own projects for the past 10 years, I noticed that solution to the not-getting-traffic problem often can be found in quite basic things: sitemap, robots.txt, duplicates, redirections. But, first of all, page speed. 
 
Today, if your website loads slow, there is no need to even bother with any other optimization at all. 

Website speed is one of Google’s top priorities for 2018 and it’s also its ranking signal. <strong>If Google crawler can't access your website because it's loading slow or throwing errors, it will never proceed futher with indexation and ranking</strong>, and as a results your website won't get any decent organic traffic.

I wanted an easy-to-use tool to warn me in case my website load time may harm its search rankings. I wanted native WordPress solution, with all information available from dashboard, simple but still informative, a guard who will do the monitoring everyday and ping me in case something go wrong. I have not found the one, that's why I've built my SpeedGuard plugin. 

I'll be happy to know that you find it usefull as well!

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
2. Check your email and confirm request
3. You will receive email with "WebPagetest API Key" subject. Copy your API key from this email into the field on SpeedGuard->Settings page and press "Save API Key".

= Configuration: =
Go to the SpeedGuard->Settings page to set the check frequency and the case when you prefer to be emailed. 

For example, you can set tests to run every day and send you performance report everyday, too. 

Or you may want to receive an email just in case the average site speed is worse than say, 5 seconds (adjustable too). 
In this case plugin will perform tests everyday but send you the warning only in if your site is loading slower than the time you have set.

== Frequently Asked Questions ==

= What service is used for performing tests? =
SpeedGuard is using Google service [WebPageTest](https://www.webpagetest.org/) API to get reliable test results.

= How accurate the resuts are? =
Each test is run 3 times (cache disabled), then average is calculated and displayed in order to get the accurate results.

= Where the site speed is tested from? =
Currently SpeedGuard runs all tests from the server in Dulles, VA, USA. Soon I'll add the ability to choose the custom location.

= Are the speed results for desktop or mobile users? =
Currently SpeedGuard runs all tests for the desktop users using cable Internet connection. Soon I'll add the ability to monitor load time for mobile devices as well.

= How many tests I can perform per day? =
You can run 65 tests per day. Will it 65 different urls to test or 65 tests of the one aprticular url — it's up to you. There is a widget on SpeedGuard pages that shows how many tests you have already used.


== Translations ==

* English - default, always included
* Russian - Привет!

*Note:* No your language yet? You can help translating this plugin to your language right [from the repository](https://translate.wordpress.org/projects/wp-plugins/speedguard), no extra software needed.


== Credits ==
* Thanx to Baboon designs from the Noun Project for the timer icon.

== Changelog ==
= Version 1.1.0 =
* Tests page view updated.

= Version 1.0.0 =
* Initial public release.
