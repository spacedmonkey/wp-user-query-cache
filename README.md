# WP User Caching

Warning
=======
This plugin is just a feature plugin and should be considered a proof of concept for user query caching. For full information call at the WordPress core ticket [#40613](https://core.trac.wordpress.org/ticket/40613).

This plugin add new UI, as it is an under the hood change.

Instalation
================

Before installing, make sure you have object caching enabled, other this caching plugin will do nothing.

1. Copy `class-wp-user-query.php` in with `wp-includes` ( or wait until WordPress 5.1 to be released ).
1. Copy `wp-user-query-cache.php` in `mu-plugins` directory
1. That's it!


Core Tickets
==================
- New filter to short circuit WP_User_Query results - [#44169](https://core.trac.wordpress.org/ticket/44169)

Reference
========

- New filter to short circuit WP_User_Query results - [#44169](https://core.trac.wordpress.org/ticket/44169)
- Add caching to WP_User_query - [#41847](https://core.trac.wordpress.org/ticket/41847)
