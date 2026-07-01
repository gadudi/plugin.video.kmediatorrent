<?php
/**
 * Helper for the list of selectable schema types.
 *
 * @package KSchema
 */

namespace KSchema\Support;

use KSchema\Schema\TypeRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Produces the schema type choices shown across admin screens.
 */
class Types {

	/**
	 * Get the selectable schema types (registry types + common extras).
	 *
	 * @param TypeRegistry $registry Type registry.
	 * @return string[]
	 */
	public static function choices( TypeRegistry $registry ) {
		$types = array_keys( $registry->all() );
		$extra = array( 'WebPage', 'FAQPage', 'Product', 'Event', 'Recipe', 'LocalBusiness', 'BreadcrumbList', 'Person', 'CollectionPage' );
		$types = array_values( array_unique( array_merge( $types, $extra ) ) );
		sort( $types );

		/**
		 * Filter the schema types offered in admin selectors.
		 *
		 * @param string[] $types Schema type names.
		 */
		return apply_filters( 'kschema/type_choices', $types );
	}
}
