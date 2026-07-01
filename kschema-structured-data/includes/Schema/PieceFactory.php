<?php
/**
 * Maps schema type names to piece instances.
 *
 * @package KSchema
 */

namespace KSchema\Schema;

use KSchema\Schema\Piece\Article;
use KSchema\Schema\Piece\GenericPiece;
use KSchema\Schema\Piece\Organization;
use KSchema\Schema\Piece\WebSite;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves a schema type string (from a rule or override) into a concrete
 * piece. Types without a dedicated class fall back to GenericPiece.
 */
class PieceFactory {

	/**
	 * Dedicated piece classes, keyed by schema type.
	 *
	 * @var array<string,string>
	 */
	private $map = array(
		'Article'      => Article::class,
		'Organization' => Organization::class,
		'WebSite'      => WebSite::class,
	);

	/**
	 * Create a piece for a schema type.
	 *
	 * @param string $type Schema type name.
	 * @return \KSchema\Schema\Piece\AbstractPiece
	 */
	public function make( $type ) {
		/**
		 * Filter the piece class map so add-ons can register dedicated
		 * classes for additional schema types.
		 *
		 * @param array<string,string> $map Type => class name.
		 */
		$map = apply_filters( 'kschema/piece_map', $this->map );

		if ( isset( $map[ $type ] ) && class_exists( $map[ $type ] ) ) {
			return new $map[ $type ]();
		}

		return new GenericPiece( $type );
	}
}
