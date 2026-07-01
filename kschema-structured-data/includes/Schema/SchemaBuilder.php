<?php
/**
 * Builds the JSON-LD graph for a request.
 *
 * @package KSchema
 */

namespace KSchema\Schema;

use KSchema\Schema\Piece\Organization;
use KSchema\Schema\Piece\WebSite;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates piece selection for the current context and returns the
 * assembled document.
 *
 * Site-level nodes (Organization, WebSite) are always emitted. Content
 * pieces (Article, Product, ...) are contributed by the RuleEngine via the
 * kschema/select_pieces filter, then adjusted by per-object overrides.
 */
class SchemaBuilder {

	/**
	 * Type registry.
	 *
	 * @var TypeRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param TypeRegistry $registry Type registry.
	 */
	public function __construct( TypeRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Build the JSON-LD document for a context.
	 *
	 * @param Context $context Request context.
	 * @return array Document array (may be empty of graph nodes).
	 */
	public function build( Context $context ) {
		$assembler = new GraphAssembler();

		foreach ( $this->select_pieces( $context ) as $piece ) {
			$assembler->add( $piece->build( $context ) );
		}

		return $assembler->to_document();
	}

	/**
	 * Choose which pieces to run for the given context.
	 *
	 * @param Context $context Request context.
	 * @return \KSchema\Schema\Piece\AbstractPiece[]
	 */
	private function select_pieces( Context $context ) {
		$pieces = array(
			new Organization(),
			new WebSite(),
		);

		/**
		 * Filter the pieces selected for a request.
		 *
		 * Phase 2's RuleEngine hooks here to translate automation rules and
		 * overrides into the concrete piece list.
		 *
		 * @param \KSchema\Schema\Piece\AbstractPiece[] $pieces  Selected pieces.
		 * @param Context                               $context Request context.
		 */
		return apply_filters( 'kschema/select_pieces', $pieces, $context );
	}
}
