<?php
/**
 * Schema.org type registry.
 *
 * @package KSchema
 */

namespace KSchema\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Loads and exposes the curated schema.org type + property definitions.
 *
 * The definitions drive the admin mapping UI (which properties exist for a
 * type) and let the builder validate required properties.
 */
class TypeRegistry {

	/**
	 * Loaded type definitions, keyed by schema type name.
	 *
	 * @var array<string,array>|null
	 */
	private $types = null;

	/**
	 * Get all type definitions.
	 *
	 * @return array<string,array>
	 */
	public function all() {
		if ( null === $this->types ) {
			$this->load();
		}
		return $this->types;
	}

	/**
	 * Get a single type definition.
	 *
	 * @param string $type Schema type name, e.g. "Article".
	 * @return array|null
	 */
	public function get( $type ) {
		$all = $this->all();
		return isset( $all[ $type ] ) ? $all[ $type ] : null;
	}

	/**
	 * Whether a type is registered.
	 *
	 * @param string $type Schema type name.
	 * @return bool
	 */
	public function has( $type ) {
		return null !== $this->get( $type );
	}

	/**
	 * Get the required property names for a type.
	 *
	 * @param string $type Schema type name.
	 * @return string[]
	 */
	public function required_properties( $type ) {
		$def = $this->get( $type );
		return ( $def && isset( $def['required'] ) ) ? (array) $def['required'] : array();
	}

	/**
	 * Load definitions from the bundled JSON file.
	 *
	 * @return void
	 */
	private function load() {
		$file = KSCHEMA_DATA_DIR . 'schema-types.json';
		$raw  = is_readable( $file ) ? file_get_contents( $file ) : '';
		$data = $raw ? json_decode( $raw, true ) : array();

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		/**
		 * Filter the registered schema type definitions.
		 *
		 * Lets add-ons register additional types or amend properties.
		 *
		 * @param array $data Type definitions keyed by type name.
		 */
		$this->types = apply_filters( 'kschema/type_registry', $data );
	}
}
