<?php
/**
 * Base class for schema graph pieces.
 *
 * @package KSchema
 */

namespace KSchema\Schema\Piece;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * A "piece" produces one node of the JSON-LD @graph for a request.
 *
 * Concrete pieces (Article, WebSite, Organization, ...) implement build().
 */
abstract class AbstractPiece {

	/**
	 * The schema.org @type this piece emits.
	 *
	 * @return string
	 */
	abstract public function type();

	/**
	 * Build the node for the given context.
	 *
	 * @param Context $context Request context.
	 * @return array|null Node array, or null to emit nothing.
	 */
	abstract public function build( Context $context );

	/**
	 * Build a stable @id anchor for a node on the current request.
	 *
	 * @param string $fragment Fragment identifier, e.g. "#article".
	 * @param string $base_url Optional base URL; defaults to the permalink/home.
	 * @return string
	 */
	protected function node_id( $fragment, $base_url = '' ) {
		$base = $base_url ? $base_url : home_url( '/' );
		return trailingslashit( $base ) . ltrim( $fragment, '/' );
	}
}
