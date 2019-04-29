# User Query caching #
**Contributors:** spacedmonkey  
**Donate link:** https://example.com/  
**Tags:** user, caching  
**Requires at least:** 5.1  
**Tested up to:** 5.1  
**Requires PHP:** 5.6  
**Stable tag:** 0.0.4  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Cache the results of query in WP_User_Query to save SQL queries

## Description ##

This plugin is just a feature plugin and should be considered a proof of concept for user query caching. For full information call at the WordPress core ticket [#40613](https://core.trac.wordpress.org/ticket/40613).

This plugin add new UI, as it is an under the hood change. This plugin requires WordPress 5.1.

### Core Tickets

- New filter to short circuit WP_User_Query results - [#44169](https://core.trac.wordpress.org/ticket/44169)

### Reference

- New filter to short circuit WP_User_Query results - [#44169](https://core.trac.wordpress.org/ticket/44169)
- Add caching to WP_User_query - [#41847](https://core.trac.wordpress.org/ticket/41847)

## Installation ##


Before installing, make sure you have object caching enabled, other this caching plugin will do nothing.

1. Copy `wp-user-query-cache.php` in `mu-plugins` directory
1. That's it!



