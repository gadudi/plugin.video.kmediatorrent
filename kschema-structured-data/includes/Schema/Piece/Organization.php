<?php
/**
 * Organization (publisher) piece.
 *
 * @package KSchema
 */

namespace KSchema\Schema\Piece;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Emits a site-level Organization node used as the publisher reference.
 */
class Organization extends AbstractPiece {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function type() {
		return 'Organization';
	}

	/**
	 * Stable @id for the Organization node.
	 *
	 * @return string
	 */
	public function id() {
		return $this->node_id( '#organization' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Context $context Request context.
	 * @return array|null
	 */
	public function build( Context $context ) {
		$node = array(
			'@type' => 'Organization',
			'@id'   => $this->id(),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		$logo_id = (int) get_option( 'site_logo' );
		if ( ! $logo_id && function_exists( 'get_theme_mod' ) ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
		}

		if ( $logo_id ) {
			$src = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( $src ) {
				$node['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $src,
				);
			}
		}

		return $node;
	}
}
