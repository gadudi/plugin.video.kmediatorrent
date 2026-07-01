<?php
/**
 * Value sanitization helpers for schema output.
 *
 * @package KSchema
 */

namespace KSchema\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Recursively cleans schema data before it is encoded to JSON-LD.
 */
class Sanitizer {

	/**
	 * Recursively remove empty values from a schema node.
	 *
	 * Keeps `0`, `0.0`, `'0'` and `false` (meaningful in schema), but drops
	 * null, empty strings, and empty arrays so JSON-LD stays clean.
	 *
	 * @param mixed $value Value to clean.
	 * @return mixed
	 */
	public static function clean( $value ) {
		if ( is_array( $value ) ) {
			$cleaned = array();
			foreach ( $value as $key => $item ) {
				$item = self::clean( $item );
				if ( self::is_empty( $item ) ) {
					continue;
				}
				$cleaned[ $key ] = $item;
			}
			return $cleaned;
		}

		return $value;
	}

	/**
	 * Determine whether a value should be dropped from output.
	 *
	 * @param mixed $value Value to test.
	 * @return bool
	 */
	public static function is_empty( $value ) {
		if ( null === $value ) {
			return true;
		}
		if ( '' === $value ) {
			return true;
		}
		if ( is_array( $value ) && array() === $value ) {
			return true;
		}
		return false;
	}

	/**
	 * Encode a schema graph as JSON-LD suitable for a <script> block.
	 *
	 * @param array $data Schema data.
	 * @return string
	 */
	public static function encode( array $data ) {
		return (string) wp_json_encode(
			$data,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}
}
