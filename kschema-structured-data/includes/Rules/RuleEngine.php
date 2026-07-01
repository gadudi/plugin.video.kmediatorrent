<?php
/**
 * Global schema automation engine.
 *
 * @package KSchema
 */

namespace KSchema\Rules;

use KSchema\Schema\Context;
use KSchema\Schema\PieceFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Translates the stored automation rules into schema pieces for a request.
 *
 * Hooks the `kschema/select_pieces` filter left by SchemaBuilder in Phase 1,
 * so the assembly pipeline stays unchanged.
 */
class RuleEngine {

	/**
	 * Rule matcher.
	 *
	 * @var RuleMatcher
	 */
	private $matcher;

	/**
	 * Piece factory.
	 *
	 * @var PieceFactory
	 */
	private $factory;

	/**
	 * Constructor.
	 *
	 * @param RuleMatcher  $matcher Rule matcher.
	 * @param PieceFactory $factory Piece factory.
	 */
	public function __construct( RuleMatcher $matcher, PieceFactory $factory ) {
		$this->matcher = $matcher;
		$this->factory = $factory;
	}

	/**
	 * Register the engine on the piece-selection filter.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'kschema/select_pieces', array( $this, 'add_pieces' ), 10, 2 );
	}

	/**
	 * Resolve the schema type names that apply to a context, ordered by
	 * rule priority (ascending) and de-duplicated.
	 *
	 * @param Context $context Request context.
	 * @return string[]
	 */
	public function resolve_types( Context $context ) {
		$rules = $this->get_rules();

		usort(
			$rules,
			static function ( $a, $b ) {
				$pa = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
				$pb = isset( $b['priority'] ) ? (int) $b['priority'] : 10;
				return $pa <=> $pb;
			}
		);

		$types = array();
		foreach ( $rules as $rule ) {
			if ( $this->matcher->matches( $rule, $context ) ) {
				$types[] = $rule['schema_type'];
			}
		}

		return array_values( array_unique( $types ) );
	}

	/**
	 * Append rule-driven pieces to the selected piece list.
	 *
	 * @param \KSchema\Schema\Piece\AbstractPiece[] $pieces  Existing pieces.
	 * @param Context                               $context Request context.
	 * @return \KSchema\Schema\Piece\AbstractPiece[]
	 */
	public function add_pieces( $pieces, Context $context ) {
		foreach ( $this->resolve_types( $context ) as $type ) {
			$pieces[] = $this->factory->make( $type );
		}
		return $pieces;
	}

	/**
	 * Load the stored automation rules.
	 *
	 * @return array<int,array>
	 */
	private function get_rules() {
		$rules = get_option( 'kschema_rules', array() );
		return is_array( $rules ) ? $rules : array();
	}
}
