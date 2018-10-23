# WP User Caching

Warning
=======
This plugin is just a feature plugin and should be considered a proof of concept for user query caching. For full information call at the WordPress core ticket [#40613](https://core.trac.wordpress.org/ticket/40613). 

This plugin add new UI, as it is an under the hood change. 

Instalation
================

Before installing, make sure you have object caching enabled, other this caching plugin will do nothing. 

1. Copy `class-wp-user-query.php` in with `wp-includes` 
1. Copy `wp-user-query-cache.php` in `mu-plugins` directory
1. That's it


Required merged for core
==================
- Add a filter for user query - [#45153](https://core.trac.wordpress.org/ticket/45153)
- 
Add `$this` to found_users_query filter - [#43679](https://core.trac.wordpress.org/ticket/43679)
- 
Add new filter to WP_User_Query - [#43680](https://core.trac.wordpress.org/ticket/43680)

Also worth referencing 

- [#44169](https://core.trac.wordpress.org/ticket/44169)