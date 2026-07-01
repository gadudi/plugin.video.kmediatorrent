<?php
/**
 * Normalizes and sanitizes per-object override data.
 *
 * @package KSchema
 */

namespace KSchema\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Shared shape helpers for the `_kschema_override` structure, used by the
 * admin screens (sanitize on save) and the resolver (normalize on read).
 */
class Overrides {

	/**
	 * Allowed override modes.
	 *
	 * @var string[]
	 */
	const MODES = array( 'inherit', 'customize', 'disable' );

	/**
	 * Normalize a decoded override array to a complete shape.
	 *
	 * @param array $data Raw override data.
	 * @return array
	 */
	public static function normalize( array $data ) {
		$mode = isset( $data['mode'] ) && in_array( $data['mode'], self::MODES, true )
			? $data['mode']
			: 'inherit';

		return array(
			'mode'               => $mode,
			'added_types'        => isset( $data['added_types'] ) ? array_values( (array) $data['added_types'] ) : array(),
			'disabled_types'     => isset( $data['disabled_types'] ) ? array_values( (array) $data['disabled_types'] ) : array(),
			'property_overrides' => isset( $data['property_overrides'] ) ? (array) $data['property_overrides'] : array(),
		);
	}

	/**
	 * Sanitize submitted override input.
	 *
	 * @param array $input Raw submitted data.
	 * @return array
	 */
	public static function sanitize( array $input ) {
		$normalized = self::normalize( $input );

		$normalized['added_types']    = self::sanitize_type_list( $normalized['added_types'] );
		$normalized['disabled_types'] = self::sanitize_type_list( $normalized['disabled_types'] );

		return $normalized;
	}

	/**
	 * Sanitize a list of schema type strings.
	 *
	 * @param array $types Raw type list.
	 * @return string[]
	 */
	private static function sanitize_type_list( array $types ) {
		$clean = array();
		foreach ( $types as $type ) {
			$type = sanitize_text_field( $type );
			if ( '' !== $type ) {
				$clean[] = $type;
			}
		}
		return array_values( array_unique( $clean ) );
	}
}
