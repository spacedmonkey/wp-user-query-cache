<?php
/**
 * Cache the results of query in WP_User_Query to save SQL queries
 *
 * Plugin Name:     User Query caching
 * Plugin URI:      https://github.com/spacedmonkey/wp-user-query-cache/
 * Description:     Cache the results of query in WP_User_Query to save SQL queries
 * Author:          Jonathan Harris
 * Author URI:      https://www.spacedmonkey.com
 * Text Domain:     wp-user-query-cache-1
 * Domain Path:     /languages
 * Version:         0.0.3
 *
 * @package         Wp_User_Query_Cache
 * @author          Jonathan Harris <jon@spacedmonkey.co.uk>
 * @license         GPL-2.0+
 * @link            http://www.spacedmonkey.com/
 * @copyright       2019 Spacedmonkey
 */

/**
 * Class WP_User_Query_Cache
 */
class WP_User_Query_Cache {
	/**
	 * Cache value.
	 *
	 * @var bool
	 */
	public $cache = false;
	/**
	 * Global cache key.
	 *
	 * @var bool $cache_key
	 */
	public $cache_key = false;

	/**
	 * WP_User_Query_Cache constructor.
	 */
	public function __construct() {

		// Single site filters.
		add_action( 'user_register', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'profile_update', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'register_new_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'delete_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'edit_user_created_user', array( $this, 'clear_user' ), 8, 1 );

		// Most important filter.
		add_action( 'clean_user_cache', array( $this, 'clear_user' ), 8, 1 );

		// Multisite User filters.
		add_action( 'wpmu_delete_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'make_spam_user', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'add_user_to_blog', array( $this, 'add_user_to_blog' ), 8, 3 );
		add_action( 'remove_user_from_blog', array( $this, 'remove_user_from_blog' ), 8, 2 );

		// Multisite Site filters.
		add_action( 'wp_insert_site', array( $this, 'clear_site' ), 8, 1 );
		add_action( 'wp_delete_site', array( $this, 'clear_site' ), 8, 1 );

		// Different params.
		add_action( 'after_password_reset', array( $this, 'after_password_reset' ), 8, 1 );
		add_action( 'retrieve_password_key', array( $this, 'retrieve_password_key' ), 8, 1 );

		// Meta api.
		add_action( 'add_user_meta', array( $this, 'clear_user' ), 8, 1 );
		add_action( 'updated_user_meta', array( $this, 'updated_user_meta' ), 8, 2 );
		add_action( 'deleted_user_meta', array( $this, 'updated_user_meta' ), 8, 2 );

		// User query filters..
		add_filter( 'users_pre_query', array( $this, 'users_pre_query' ), 8, 2 );
		add_filter( 'found_users_query', array( $this, 'found_users_query' ), 8, 2 );

		// User query count.
		add_filter( 'pre_count_users', array( $this, 'pre_count_users' ), 8, 3 );
	}

	/**
	 * Clear global and all site caches for a user.
	 *
	 * @param int $user_id User ID.
	 */
	public function clear_user( $user_id ) {
		$site_ids = $this->get_user_site_ids( $user_id );
		array_map( array( $this, 'clear_site' ), $site_ids );
		$this->update_last_change( 'last_changed' );
	}

	/**
	 * Clear site level cache salt.
	 *
	 * @param int|WP_Site $site Site to clear, with object or id.
	 */
	public function clear_site( $site ) {
		if ( $site instanceof WP_Site ) {
			$site_id = $site->id;
		} elseif ( is_numeric( $site ) ) {
			$site_id = $site;
		} else {
			return;
		}

		$cache_key = $this->site_cache_key( $site_id );
		$this->update_last_change( $cache_key );
	}

	/**
	 * Helper to get last updated value.
	 *
	 * @param string $cache_key Cache key.
	 *
	 * @return string $result of wp_cache_set.
	 */
	private function update_last_change( $cache_key = 'last_changed' ) {
		return wp_cache_set( $cache_key, microtime(), 'users' );
	}

	/**
	 * Helper method to generate site cache key.
	 *
	 * @param int $site_id Blog id for cache key generation.
	 *
	 * @return string
	 */
	private function site_cache_key( $site_id ) {
		return 'site-' . $site_id . '-last_changed';
	}

	/**
	 * When a use is added to a site, clear site and user level changes.
	 *
	 * @param int    $user_id User ID.
	 * @param string $role    Current user role.
	 * @param int    $blog_id Blog ID.
	 */
	public function add_user_to_blog( $user_id, $role, $blog_id ) {
		$this->clear_user( $user_id );
		$this->clear_site( $blog_id );
	}

	/**
	 * When a use is removed to a site, clear site and user level changes.
	 *
	 * @param int $user_id User ID.
	 * @param int $blog_id Blog ID.
	 */
	public function remove_user_from_blog( $user_id, $blog_id ) {
		$this->clear_user( $user_id );
		$this->clear_site( $blog_id );
	}

	/**
	 * Clear cache after password is changed
	 *
	 * @param WP_User $user Current user of cleared password.
	 */
	public function after_password_reset( $user ) {
		$this->clear_user( $user->ID );
	}

	/**
	 * Clear cache after password is changed
	 *
	 * @param String $user_login Username string.
	 */
	public function retrieve_password_key( $user_login ) {
		$user = get_user_by( 'login', $user_login );
		$this->clear_user( $user->ID );
	}

	/**
	 * On update / delete user meta, clear user cache
	 *
	 * @param int $unused  Meta id, unused.
	 * @param int $user_id User ID, used to clear caches.
	 */
	public function updated_user_meta( $unused, $user_id ) {
		$this->clear_user( $user_id );
	}

	/**
	 * Hook into pre user results, to high jack results.
	 *
	 * @param null          $results       Pre Value.
	 * @param WP_User_Query $wp_user_query WP User query object.
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
			if ( is_array( $qv['fields'] ) || 'all' === $qv['fields'] ) {
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
	 * @param string        $query         String of SQL query.
	 * @param WP_User_Query $wp_user_query WP User query object.
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
	 * @param string        $query         String of SQL query.
	 * @param WP_User_Query $wp_user_query WP User query object.
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
	 * Hook into user count and force the lookup through wp_user_query to which is cached.
	 *
	 * @param null   $unused   Unused variable.
	 * @param string $strategy Unused variable.
	 * @param null   $site_id  Site ID to get list of count of users.
	 *
	 * @return array Array of user counts.
	 */
	public function pre_count_users( $output = null, $strategy = 'time', $site_id = null ) {
		$cache_key_site = $this->site_cache_key( $site_id );
		$salt           = wp_cache_get( $cache_key_site, 'users' );
		if ( ! $salt ) {
			$salt = microtime();
			wp_cache_set( $cache_key_site, microtime(), 'users' );
		}
		$cache_key = 'count_users_' . $site_id . '_' . $salt;
		$cache     = wp_cache_get( $cache_key, 'users' );
		if ( ! $cache ) {
			remove_filter( 'pre_count_users', array( $this, 'pre_count_users' ), 8, 3 );
			$output = count_users( $strategy, $site_id );
			wp_cache_set( $cache_key, $output, 'users' );
			add_filter( 'pre_count_users', array( $this, 'pre_count_users' ), 8, 3 );
		} else{
			$output = $cache;
		}

		return $output;
	}

	/**
	 * Helper function to get count of users by sites and role
	 *
	 * @param int    $site_id (Default null).
	 * @param string $role    (Default empty string).
	 *
	 * @return int
	 */
	protected function get_user_count( $site_id = null, $role = '' ) {
		$args        = array(
			'count_total' => true,
			'number'      => 1,
			'fields'      => 'ids',
			'blog_id'     => $site_id,
			'role'        => $role,
		);
		$user_search = new WP_User_Query( $args );

		return $user_search->total_users;
	}

	/**
	 * Get list of site ids by user id.
	 *
	 * @param int $user_id User ID.
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

		// Logged out users can't have sites.
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
	 * @param WP_User_Query $wp_user_query User query object.
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
