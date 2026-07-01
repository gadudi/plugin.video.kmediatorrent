<?php
/**
 * Matches automation rules against a request context.
 *
 * @package KSchema
 */

namespace KSchema\Rules;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a single rule applies to the current context.
 */
class RuleMatcher {

	/**
	 * Whether a rule matches the given context.
	 *
	 * @param array   $rule    Rule definition.
	 * @param Context $context Request context.
	 * @return bool
	 */
	public function matches( array $rule, Context $context ) {
		if ( empty( $rule['enabled'] ) ) {
			return false;
		}
		if ( empty( $rule['schema_type'] ) || empty( $rule['target_type'] ) || empty( $rule['target_name'] ) ) {
			return false;
		}

		if ( 'post_type' === $rule['target_type'] && 'post' === $context->object_type() ) {
			$post = $context->post();
			return $post && get_post_type( $post ) === $rule['target_name'];
		}

		if ( 'taxonomy' === $rule['target_type'] && 'term' === $context->object_type() ) {
			$term = $context->term();
			return $term && $term->taxonomy === $rule['target_name'];
		}

		return false;
	}
}
