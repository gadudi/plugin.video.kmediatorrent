<?php
/**
 * Generic piece for schema types without a dedicated class.
 *
 * @package KSchema
 */

namespace KSchema\Schema\Piece;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a minimal, valid node for any schema type using core post/term
 * fields. Phase 3's mapping engine enriches these nodes with mapped
 * properties; until then this keeps automation immediately useful.
 */
class GenericPiece extends AbstractPiece {

	/**
	 * The schema type this instance emits.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Constructor.
	 *
	 * @param string $type Schema type name, e.g. "WebPage".
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Context $context Request context.
	 * @return array|null
	 */
	public function build( Context $context ) {
		$post = $context->post();
		$term = $context->term();

		if ( $post ) {
			$permalink = get_permalink( $post );
			return array(
				'@type'         => $this->type,
				'@id'           => trailingslashit( $permalink ) . '#' . strtolower( $this->type ),
				'name'          => wp_strip_all_tags( get_the_title( $post ) ),
				'url'           => $permalink,
				'description'   => $this->post_description( $post ),
				'datePublished' => get_post_time( 'c', true, $post ),
				'dateModified'  => get_post_modified_time( 'c', true, $post ),
			);
		}

		if ( $term ) {
			$link = get_term_link( $term );
			$url  = is_wp_error( $link ) ? '' : $link;
			return array(
				'@type'       => $this->type,
				'@id'         => $url ? ( trailingslashit( $url ) . '#' . strtolower( $this->type ) ) : '',
				'name'        => $term->name,
				'url'         => $url,
				'description' => wp_strip_all_tags( (string) $term->description ),
			);
		}

		return null;
	}

	/**
	 * Derive a description from a post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private function post_description( \WP_Post $post ) {
		if ( has_excerpt( $post ) ) {
			return wp_strip_all_tags( get_the_excerpt( $post ) );
		}
		return wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 55, '' );
	}
}
