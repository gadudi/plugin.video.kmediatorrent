<?php
/**
 * Applies per-post / per-term overrides to the schema pipeline.
 *
 * @package KSchema
 */

namespace KSchema\Override;

use KSchema\Schema\Context;
use KSchema\Schema\PieceFactory;
use KSchema\Support\Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the `_kschema_override` meta for the current object and adjusts the
 * selected pieces accordingly. Runs after the RuleEngine on the shared
 * `kschema/select_pieces` filter so overrides take precedence.
 *
 * Override shape:
 *   mode:            inherit | customize | disable
 *   added_types:     string[]  (extra schema types to emit)
 *   disabled_types:  string[]  (rule/site types to suppress)
 *   property_overrides: array  (consumed by the Phase 3 mapping engine)
 */
class OverrideResolver {

	/**
	 * Meta key for both post and term overrides.
	 */
	const META_KEY = '_kschema_override';

	/**
	 * Piece factory.
	 *
	 * @var PieceFactory
	 */
	private $factory;

	/**
	 * Constructor.
	 *
	 * @param PieceFactory $factory Piece factory.
	 */
	public function __construct( PieceFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Register pipeline hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'kschema/select_pieces', array( $this, 'apply' ), 20, 2 );
		add_filter( 'kschema/should_output', array( $this, 'maybe_suppress' ), 20, 2 );
	}

	/**
	 * Load and normalize the override for a context.
	 *
	 * @param Context $context Request context.
	 * @return array
	 */
	public function get_override( Context $context ) {
		$raw = '';
		if ( 'post' === $context->object_type() && $context->object_id() ) {
			$raw = get_post_meta( $context->object_id(), self::META_KEY, true );
		} elseif ( 'term' === $context->object_type() && $context->object_id() ) {
			$raw = get_term_meta( $context->object_id(), self::META_KEY, true );
		}

		if ( is_string( $raw ) && '' !== $raw ) {
			$raw = json_decode( $raw, true );
		}

		return Overrides::normalize( is_array( $raw ) ? $raw : array() );
	}

	/**
	 * Adjust the piece list based on the override.
	 *
	 * @param \KSchema\Schema\Piece\AbstractPiece[] $pieces  Selected pieces.
	 * @param Context                               $context Request context.
	 * @return \KSchema\Schema\Piece\AbstractPiece[]
	 */
	public function apply( $pieces, Context $context ) {
		$override = $this->get_override( $context );

		// "disable" is enforced by maybe_suppress(); "inherit" changes nothing.
		if ( 'customize' !== $override['mode'] ) {
			return $pieces;
		}

		if ( ! empty( $override['disabled_types'] ) ) {
			$disabled = $override['disabled_types'];
			$pieces   = array_values(
				array_filter(
					$pieces,
					static function ( $piece ) use ( $disabled ) {
						return ! in_array( $piece->type(), $disabled, true );
					}
				)
			);
		}

		foreach ( $override['added_types'] as $type ) {
			if ( '' !== $type ) {
				$pieces[] = $this->factory->make( $type );
			}
		}

		return $pieces;
	}

	/**
	 * Suppress all output when the object override mode is "disable".
	 *
	 * @param bool    $enabled Whether output is enabled.
	 * @param Context $context Request context.
	 * @return bool
	 */
	public function maybe_suppress( $enabled, Context $context ) {
		$override = $this->get_override( $context );
		if ( 'disable' === $override['mode'] ) {
			return false;
		}
		return $enabled;
	}
}
