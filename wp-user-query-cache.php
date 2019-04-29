<?php
/**
 * Cache the results of query in WP_User_Query to save SQL queries
 *
 * Plugin Name:     User Query caching
 * Plugin URI:      https://github.com/spacedmonkey/wp-user-query-cache/
 * Description:     Cache the results of query in WP_User_Query to save SQL queries
 * Author:          Jonathan Harris
 * Author URI:      https://www.spacedmonkey.com
 * Text Domain:     wp-user-query-cache
 * Domain Path:     /languages
 * Version:         0.0.4
 *
 * @package         Wp_User_Query_Cache
 * @author          Jonathan Harris <jon@spacedmonkey.co.uk>
 * @license         GPL-2.0+
 * @link            http://www.spacedmonkey.com/
 * @copyright       2019 Spacedmonkey
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/src/class-wp-user-query-cache.php';

// Setup the class.
new WP_User_Query_Cache();
