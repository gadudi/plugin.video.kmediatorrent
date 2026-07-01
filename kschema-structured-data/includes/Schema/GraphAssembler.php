<?php
/**
 * Assembles JSON-LD @graph from individual nodes.
 *
 * @package KSchema
 */

namespace KSchema\Schema;

use KSchema\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Collects piece nodes, de-duplicates by @id, cleans empties, and wraps
 * them into a single @graph document.
 */
class GraphAssembler {

	/**
	 * Collected nodes, indexed by @id (or numeric for id-less nodes).
	 *
	 * @var array<int|string,array>
	 */
	private $nodes = array();

	/**
	 * Add a node to the graph.
	 *
	 * Nodes sharing an @id are merged (last write wins per property) so a
	 * later piece can enrich an earlier node rather than duplicate it.
	 *
	 * @param array|null $node Node array.
	 * @return void
	 */
	public function add( $node ) {
		if ( ! is_array( $node ) || array() === $node ) {
			return;
		}

		if ( isset( $node['@id'] ) ) {
			$id = $node['@id'];
			if ( isset( $this->nodes[ $id ] ) ) {
				$this->nodes[ $id ] = array_merge( $this->nodes[ $id ], $node );
			} else {
				$this->nodes[ $id ] = $node;
			}
			return;
		}

		$this->nodes[] = $node;
	}

	/**
	 * Whether any nodes have been collected.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return array() === $this->nodes;
	}

	/**
	 * Build the final JSON-LD document array.
	 *
	 * @return array
	 */
	public function to_document() {
		$graph = array();
		foreach ( $this->nodes as $node ) {
			$clean = Sanitizer::clean( $node );
			if ( ! Sanitizer::is_empty( $clean ) ) {
				$graph[] = $clean;
			}
		}

		$document = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $graph ),
		);

		/**
		 * Filter the assembled JSON-LD document before output.
		 *
		 * @param array $document The @context/@graph document.
		 */
		return apply_filters( 'kschema/graph', $document );
	}
}
