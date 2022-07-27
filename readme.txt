=== Inesonic SpeedSentry ===
Contributors: tuxidriver
Tags: monitor, monitoring, latency, performance, security, uptime, checking, downtme, failure, reporting, event, ssl, content checking, keyword checking, site defacing, hacked, hacking, subscription, REST API
Requires at least: 5.7
Tested up to: 5.9
Requires PHP: 7.4
Stable tag: 1.6
License: GPLv3 and LGPLv3
License URI: https://downloads.inesonic.com/speedsentry_plugin_license.txt

Site monitoring for everyday small business.

== Description ==

**Inesonic** **SpeedSentry** is a site performance monitoring application specifically designed for small businesses using WordPress.

**SpeedSentry** is designed to be simple, easy to use, and affordable. **SpeedSentry** will:

* Monitor your site, know if your site ever goes down.
* Track your site’s performance from multiple geographic regions.
* Monitor specific pages for content changes and/or missing keywords.
* Track your SSL certificates and inform you if any of your certificates are about to expire.
* Optionally send you weekly rollups outlining events and performance over the past week.

You can also use advanced features of **SpeedSentry** to periodically test your REST APIs and generate custom events.

== Installation ==

1. Install “inesonic-speedsentry.zip” using the WordPress Plugins <Add New> button.  Alternately, unzip “inesonic-speedsentry.zip” into “/wp-content/plugins/” directory.
1. Activate the plugin through the “Plugins” menu in WordPress admin panel.
1. Connect the **Inesonic** **SpeedSentry** plug-in to **Inesonic**’s monitoring system by clicking “Connect” or “Reconnect”.  You can alternately manually configure the **SpeedSentry** plug-in by entering your access code.

== Frequently Asked Questions ==

= How does SpeedSentry notify me when a failure occurs =

**SpeedSentry** can notify you via:

  * Automated voice calls.
  * SMS messages
  * Email messages
  * Slack messages
  * REST API calls

You can specify multiple contacts for SMS and email messages.  Note that our free plan only supports email and Slack messages.

= Is SpeedSentry Free =

We offer both free and paid subscriptions.  By design, our paid subscriptions are inexpensive and offer significant additional functionality over our free plan.

= Can I see our performance from multiple locations =

Our paid subscriptions check your performance from multiple geographic regions.  You can view your site’s latency from all regions or from specific regions.

= Is SpeedSentry Invasive =

**Inesonic** **SpeedSentry** simply monitors your site using our infrastructure.  We do not perform any testing directly on your website.  Our plug-in simply requests and then displays data from our infrastructure at periodic intervals.  We designed our plug-in to be small and non-intrusive.

= Will SpeedSentry impact my site's performance =

**SpeedSentry** is designed not to impact your site significantly.  We rate limit our checks for each server you ask us to monitor so that we do not impact your or your hosting provider's infrastructure.  We also use HTTP HEAD messages when appropriate.  Our distributed polling servers coordinate timing with each other so that the rate we check your infrastructure remains constant even as we add new polling servers or you add new monitors.

For Professional and Business subscriptions, if your server responds to ICMP echo or ping messages, we will also issue light-weight ping messages to your server to improve our response time should your server go off-line.  Use of ICMP echo messages is fully automatic.  For details, see [https://speed-sentry.com/ping-support/](https://speed-sentry.com/ping-support/).

= Can I disable SpeedSentry when I make changes =

Our business subscription offers a maintenance mode feature.  When you re-enable site monitoring, we will update our infrastructure to automatically accept changes you've made while monitoring was disabled.

= Can I extend SpeedSentry =

We offer a flexible REST API you can use to extend **SpeedSentry**.  We also offer PHP and Python libraries you can use.  Detailed documentation can be found at [https://speedsentry-documentation.inesonic.com](https://speedsentry-documentation.inesonic.com).

== Screenshots ==

1. The **Inesonic** **SpeedSentry** status panel showing status and latency.
2. The same panel showing site performance over time and SSL certificate expiration data.
3. Listing event history.
4. An example weekly rollup email.

== Changelog ==

= 1.6 =

Initial public release.
