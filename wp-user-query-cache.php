<?php
/**
 * Cache the results of query in WP_User_Query to save SQL queries
 *
 *
 * @package   Advanced_Nav_Cache
 * @author    Jonathan Harris <jon@spacedmonkey.co.uk>
 * @license   GPL-2.0+
 * @link      http://www.spacedmonkey.com/
 * @copyright 2018 Spacedmonkey
 *
 * @wordpress-plugin
 * Plugin Name:        User Query caching
 * Plugin URI:         https://www.github.com/spacedmonkey/wp-user-query-cache
 * Description:        Cache the results of query in WP_User_Query to save SQL queries
 * Version:            0.0.2
 * Author:             Jonathan Harris
 * Author URI:         http://www.spacedmonkey.com/
 * License:            GPL v2 or later
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI:  https://www.github.com/spacedmonkey/wp-user-query-cache
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_User_Query_Cache {
	public $cache = false;
	public $cache_key = false;

	public function __construct() {

		// Single site filters
		add_action( 'user_register', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'profile_update', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'register_new_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'delete_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'edit_user_created_user', array( $this, 'clear_user' ), 8, 1 );

		// Most important filter
		add_action( 'clean_user_cache', array( $this, 'clear_user' ), 8, 1 );

		// Multisite User filters
		add_action( 'wpmu_delete_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'make_spam_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'add_user_to_blog', array( $this, 'add_user_to_blog' ), 8, 3 );
		add_action( 'remove_user_from_blog', array( $this, 'remove_user_from_blog' ), 8, 2 );

		// Multisite Site filters
		add_action( 'wp_insert_site', array( $this, 'clear_site' ), 8, 1 );
		add_action( 'wp_delete_site', array( $this, 'clear_site' ), 8, 1 );

		// Different params
		add_action( 'after_password_reset', array( $this, 'after_password_reset' ), 8, 1 );
		add_action( 'retrieve_password_key', array( $this, 'retrieve_password_key' ), 8, 1 );

		// Meta api
		add_action( "add_user_meta", array( $this, 'clear_user' ), 8, 1 );
		add_action( "updated_user_meta", array( $this, 'updated_user_meta' ), 8, 2 );
		add_action( "deleted_user_meta", array( $this, 'updated_user_meta' ), 8, 2 );

		// User query filters
		// Requires https://core.trac.wordpress.org/ticket/43680
		add_filter( 'users_pre_query', array( $this, 'users_pre_query' ), 8, 2 );
		// Requires https://core.trac.wordpress.org/ticket/43679
		add_filter( 'found_users_query', array( $this, 'found_users_query' ), 8, 2 );
	}

	/**
	 * Clear global and all site caches for a user
	 *
	 * @param $user_id
	 */
	public function clear_user( $user_id ) {
		$site_ids = $this->get_user_site_ids( $user_id );
		array_map( array( $this, 'clear_site' ), $site_ids );
		$this->update_last_change( 'last_changed' );
	}

	/**
	 * Clear site level cache salt
	 *
	 * @param $site int|WP_Site
	 */
	public function clear_site( $site ) {
		if ( $site instanceof WP_Site ) {
			$site_id = $site->id;
		} else if ( is_numeric( $site ) ) {
			$site_id = $site;
		} else {
			return;
		}

		$cache_key = $this->site_cache_key( $site_id );
		$this->update_last_change( $cache_key );
	}

	/**
	 * @param  $cache_key Cache key
	 * @return $result of wp_cache_set
	 */
	private function update_last_change( $cache_key = 'last_changed' ){
			return wp_cache_set( $cache_key, microtime(), 'users' );
	}

	/**
	 * Helper method to generate site cache key
	 *
	 * @param $site_id
	 *
	 * @return string
	 */
	private function site_cache_key( $site_id ) {
		return 'site-' . $site_id . '-last_changed';
	}

	/**
	 * When a use is added to a site, clear site and user level changes
	 *
	 * @param $user_id
	 * @param $role
	 * @param $blog_id
	 */
	public function add_user_to_blog( $user_id, $role, $blog_id ) {
		$this->clear_user( $user_id );
		$this->clear_site( $blog_id );
	}

	/**
	 * When a use is removed to a site, clear site and user level changes
	 *
	 * @param $user_id
	 * @param $blog_id
	 */
	public function remove_user_from_blog( $user_id, $blog_id ) {
		$this->clear_user( $user_id );
		$this->clear_site( $blog_id );
	}

	/**
	 * Clear cache after password is changed
	 *
	 * @param $user
	 */
	public function after_password_reset( $user ) {
		$this->clear_user( $user->ID );
	}

	/**
	 * Clear cache after password is changed
	 *
	 * @param $user_login
	 */
	public function retrieve_password_key( $user_login ) {
		$user = get_user_by( 'login', $user_login );
		$this->clear_user( $user->ID );
	}

	/**
	 * On update / delete user meta, clear user cache
	 *
	 * @param $meta_id
	 * @param $user_id
	 */
	public function updated_user_meta( $meta_id, $user_id ) {
		$this->clear_user( $user_id );
	}

	/**
	 * Hook into pre user results, to high jack results and total users.
	 *
	 * @param $results
	 * @param $wp_user_query
	 *
	 * @return array
	 */
	public function users_pre_query( $results, $wp_user_query ) {
		global $wpdb;

		$qv =& $wp_user_query->query_vars;

		$request = "SELECT $wp_user_query->query_fields $wp_user_query->query_from $wp_user_query->query_where $wp_user_query->query_orderby $wp_user_query->query_limit";
		$request = $this->users_request( $request, $wp_user_query );

		if ( ! $request ) {
			$results                    = (array) $this->cache['users'];
			$wp_user_query->total_users = (int) $this->cache['total_users'];
		} else {

			if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] ) {
				$results = $wpdb->get_results( $request );
			} else {
				$results = $wpdb->get_col( $request );
			}

			if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
				$wp_user_query->total_users = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
			} else {
				$wp_user_query->total_users = 0;
			}

			$data = array(
				'users'       => (array) $results,
				'total_users' => (int) $wp_user_query->total_users,
			);
			wp_cache_set( $this->cache_key, $data, 'users' );
		}
		$this->cache     = false;
		$this->cache_key = false;

		return $results;
	}

	/**
	 * If cached, the do not run count query
	 *
	 * @param $query
	 * @param $wp_user_query
	 *
	 * @return string
	 */
	public function found_users_query( $query, $wp_user_query ) {
		if ( false !== $this->cache ) {
			$query = '';
		}

		return $query;
	}

	/**
	 * If cached, then dont run query.
	 *
	 * @param $query
	 * @param $wp_user_query
	 *
	 * @return string
	 */
	public function users_request( $query, $wp_user_query ) {
		global $wpdb;

		$sql             = $wpdb->remove_placeholder_escape( $query );
		$cache_key       = md5( $sql );
		$cache_salt      = $this->get_cache_salt( $wp_user_query );
		$this->cache_key = $cache_key . $cache_salt;
		$this->cache     = wp_cache_get( $this->cache_key, 'users' );
		if ( false !== $this->cache ) {
			$query = '';
			// This is a hack to stop a count notice error.
			$wpdb->last_result = array();
		}

		return $query;
	}

	/**
	 * @param $user_id
	 *
	 * @return array
	 */
	public function get_user_site_ids( $user_id ) {
		global $wpdb;

		$site_ids = array();
		$user_id  = (int) $user_id;
		if ( empty( $user_id ) ) {
			return $site_ids;
		}

		// Logged out users can't have sites
		$keys = get_user_meta( $user_id );

		if ( empty( $keys ) ) {
			return $site_ids;
		}
		if ( ! is_multisite() ) {
			$site_ids[] = get_current_blog_id();

			return $site_ids;
		}


		if ( isset( $keys[ $wpdb->base_prefix . 'capabilities' ] ) && defined( 'MULTISITE' ) ) {
			$site_ids[] = 1;
			unset( $keys[ $wpdb->base_prefix . 'capabilities' ] );
		}

		$keys = array_keys( $keys );

		foreach ( $keys as $key ) {
			if ( 'capabilities' !== substr( $key, - 12 ) ) {
				continue;
			}
			if ( $wpdb->base_prefix && 0 !== strpos( $key, $wpdb->base_prefix ) ) {
				continue;
			}
			$site_id = str_replace( array( $wpdb->base_prefix, '_capabilities' ), '', $key );
			if ( ! is_numeric( $site_id ) ) {
				continue;
			}

			$site_ids[] = (int) $site_id;
		}

		return $site_ids;
	}

	/**
	 * Generate different salts.
	 * If a global user query, use global salt
	 * If a site level query, use site level cache. Also change salt if post is modified
	 *
	 * @param $wp_user_query
	 *
	 * @return bool|mixed|string
	 */
	private function get_cache_salt( $wp_user_query ) {
		$qv    = $wp_user_query->query_vars;
		$group = 'users';
		if ( isset( $qv['blog_id'] ) && $qv['blog_id'] ) {
			$cache_key_site = $this->site_cache_key( $qv['blog_id'] );
			$salt           = wp_cache_get( $cache_key_site, $group );
			if ( ! $salt ) {
				$salt = microtime();
				wp_cache_set( $cache_key_site, microtime(), $group );
			}
			if ( isset( $qv['has_published_posts'] ) && $qv['has_published_posts'] ) {
				switch_to_blog( $qv['blog_id'] );
				$salt .= wp_cache_get_last_changed( 'posts' );
				restore_current_blog();
			}

		} else {
			$salt = wp_cache_get_last_changed( $group );
		}

		return $salt;
	}
}

// Setup the class
new WP_User_Query_Cache();
