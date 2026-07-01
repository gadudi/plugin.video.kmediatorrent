<?php
/**
 * Transient-based cache for rendered JSON-LD.
 *
 * @package KSchema
 */

namespace KSchema\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper around the Transients API with a plugin-scoped key index
 * so the whole cache can be flushed at once.
 */
class Cache {

	/**
	 * Prefix for all cache transients.
	 */
	const PREFIX = 'kschema_ld_';

	/**
	 * Option holding the list of known cache keys (for bulk flush).
	 */
	const INDEX_OPTION = 'kschema_cache_index';

	/**
	 * Default TTL in seconds (12 hours).
	 */
	const TTL = 43200;

	/**
	 * Build a cache key for a rendered object.
	 *
	 * @param string $object_type Object type, e.g. "post" or "term".
	 * @param int    $object_id   Object ID.
	 * @return string
	 */
	public static function key( $object_type, $object_id ) {
		return self::PREFIX . $object_type . '_' . (int) $object_id . '_' . KSCHEMA_VERSION;
	}

	/**
	 * Get a cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed False when not found.
	 */
	public static function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Store a value and track its key for bulk flushing.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to store.
	 * @param int    $ttl   Optional TTL override.
	 * @return void
	 */
	public static function set( $key, $value, $ttl = self::TTL ) {
		set_transient( $key, $value, $ttl );

		$index = get_option( self::INDEX_OPTION, array() );
		if ( ! in_array( $key, $index, true ) ) {
			$index[] = $key;
			update_option( self::INDEX_OPTION, $index, false );
		}
	}

	/**
	 * Delete every tracked cache entry.
	 *
	 * @return void
	 */
	public static function flush() {
		$index = get_option( self::INDEX_OPTION, array() );
		foreach ( $index as $key ) {
			delete_transient( $key );
		}
		update_option( self::INDEX_OPTION, array(), false );
	}
}
