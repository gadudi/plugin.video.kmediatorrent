<?php
/**
 * Request context passed to schema pieces.
 *
 * @package KSchema
 */

namespace KSchema\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable snapshot of the object currently being rendered.
 *
 * Later phases attach the resolved rule set and override data here so the
 * mapping engine can produce concrete property values.
 */
class Context {

	/**
	 * Object type: "post", "term", or "site".
	 *
	 * @var string
	 */
	private $object_type;

	/**
	 * Object ID (post ID or term ID); 0 for site-level.
	 *
	 * @var int
	 */
	private $object_id;

	/**
	 * The queried WP object, when available.
	 *
	 * @var \WP_Post|\WP_Term|null
	 */
	private $object;

	/**
	 * Constructor.
	 *
	 * @param string                 $object_type Object type.
	 * @param int                    $object_id   Object ID.
	 * @param \WP_Post|\WP_Term|null $object      Queried object.
	 */
	public function __construct( $object_type, $object_id, $object = null ) {
		$this->object_type = $object_type;
		$this->object_id   = (int) $object_id;
		$this->object      = $object;
	}

	/**
	 * Build a context for the current main query.
	 *
	 * @return Context
	 */
	public static function for_current_request() {
		if ( is_singular() ) {
			$post = get_queried_object();
			return new self( 'post', get_queried_object_id(), $post instanceof \WP_Post ? $post : null );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			return new self( 'term', get_queried_object_id(), $term instanceof \WP_Term ? $term : null );
		}

		return new self( 'site', 0, null );
	}

	/**
	 * Get the object type.
	 *
	 * @return string
	 */
	public function object_type() {
		return $this->object_type;
	}

	/**
	 * Get the object ID.
	 *
	 * @return int
	 */
	public function object_id() {
		return $this->object_id;
	}

	/**
	 * Get the queried object.
	 *
	 * @return \WP_Post|\WP_Term|null
	 */
	public function object() {
		return $this->object;
	}

	/**
	 * Convenience: the WP_Post, or null.
	 *
	 * @return \WP_Post|null
	 */
	public function post() {
		return ( $this->object instanceof \WP_Post ) ? $this->object : null;
	}

	/**
	 * Convenience: the WP_Term, or null.
	 *
	 * @return \WP_Term|null
	 */
	public function term() {
		return ( $this->object instanceof \WP_Term ) ? $this->object : null;
	}
}
