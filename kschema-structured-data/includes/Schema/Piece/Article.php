<?php
/**
 * Article piece.
 *
 * @package KSchema
 */

namespace KSchema\Schema\Piece;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Emits an Article node for a singular post, linked to the publisher and
 * the page's WebSite/Organization graph.
 */
class Article extends AbstractPiece {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function type() {
		return 'Article';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Context $context Request context.
	 * @return array|null
	 */
	public function build( Context $context ) {
		$post = $context->post();
		if ( ! $post ) {
			return null;
		}

		$permalink = get_permalink( $post );
		$org       = new Organization();

		$node = array(
			'@type'            => 'Article',
			'@id'              => trailingslashit( $permalink ) . '#article',
			'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
			'description'      => $this->description( $post ),
			'datePublished'    => get_post_time( 'c', true, $post ),
			'dateModified'     => get_post_modified_time( 'c', true, $post ),
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'publisher'        => array( '@id' => $org->id() ),
			'author'           => $this->author( $post ),
		);

		$image = $this->image( $post );
		if ( $image ) {
			$node['image'] = $image;
		}

		return $node;
	}

	/**
	 * Resolve the article description.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private function description( \WP_Post $post ) {
		if ( has_excerpt( $post ) ) {
			return wp_strip_all_tags( get_the_excerpt( $post ) );
		}
		return wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 55, '' );
	}

	/**
	 * Build the author node.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	private function author( \WP_Post $post ) {
		return array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
			'url'   => get_author_posts_url( (int) $post->post_author ),
		);
	}

	/**
	 * Build the featured image node.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array|null
	 */
	private function image( \WP_Post $post ) {
		$id = get_post_thumbnail_id( $post );
		if ( ! $id ) {
			return null;
		}

		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $src ) {
			return null;
		}

		return array(
			'@type'  => 'ImageObject',
			'url'    => $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
		);
	}
}
