<?php
/**
 * WebSite piece.
 *
 * @package KSchema
 */

namespace KSchema\Schema\Piece;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Emits a site-level WebSite node, linked to the Organization publisher.
 */
class WebSite extends AbstractPiece {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function type() {
		return 'WebSite';
	}

	/**
	 * Stable @id for the WebSite node.
	 *
	 * @return string
	 */
	public function id() {
		return $this->node_id( '#website' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Context $context Request context.
	 * @return array|null
	 */
	public function build( Context $context ) {
		$org = new Organization();

		$node = array(
			'@type'       => 'WebSite',
			'@id'         => $this->id(),
			'name'        => get_bloginfo( 'name' ),
			'url'         => home_url( '/' ),
			'description' => get_bloginfo( 'description' ),
			'publisher'   => array( '@id' => $org->id() ),
		);

		return $node;
	}
}
