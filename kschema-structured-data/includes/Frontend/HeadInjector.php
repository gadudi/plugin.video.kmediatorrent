<?php
/**
 * Injects the JSON-LD document into the page head.
 *
 * @package KSchema
 */

namespace KSchema\Frontend;

use KSchema\Schema\Context;
use KSchema\Schema\SchemaBuilder;
use KSchema\Support\Cache;
use KSchema\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks wp_head late and prints a single cached JSON-LD script block.
 *
 * Output lives in wp_head (not the DOM body), so Elementor rendering and
 * editing are never touched.
 */
class HeadInjector {

	/**
	 * Schema builder.
	 *
	 * @var SchemaBuilder
	 */
	private $builder;

	/**
	 * Constructor.
	 *
	 * @param SchemaBuilder $builder Schema builder.
	 */
	public function __construct( SchemaBuilder $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Late priority so SEO plugins run first (needed for the bridge).
		add_action( 'wp_head', array( $this, 'render' ), 99 );
	}

	/**
	 * Render the JSON-LD block.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->should_output() ) {
			return;
		}

		$context = Context::for_current_request();
		$json    = $this->get_json( $context );

		if ( '' === $json ) {
			return;
		}

		// $json is validated JSON produced by wp_json_encode; safe to print
		// inside a <script type="application/ld+json"> block.
		echo "\n<script type=\"application/ld+json\" class=\"kschema-graph\">" . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Whether output is enabled for this request.
	 *
	 * @return bool
	 */
	private function should_output() {
		$settings = get_option( 'kschema_settings', array() );
		$enabled  = ! isset( $settings['output_enabled'] ) || $settings['output_enabled'];

		if ( is_feed() || is_embed() || is_404() ) {
			$enabled = false;
		}

		/**
		 * Filter whether the JSON-LD block should be output on this request.
		 *
		 * @param bool $enabled Whether to output.
		 */
		return (bool) apply_filters( 'kschema/should_output', $enabled );
	}

	/**
	 * Get the encoded JSON, using the cache where allowed.
	 *
	 * @param Context $context Request context.
	 * @return string
	 */
	private function get_json( Context $context ) {
		$settings   = get_option( 'kschema_settings', array() );
		$use_cache  = ( ! isset( $settings['cache_enabled'] ) || $settings['cache_enabled'] )
			&& 'site' !== $context->object_type();
		$cache_key  = Cache::key( $context->object_type(), $context->object_id() );

		if ( $use_cache ) {
			$cached = Cache::get( $cache_key );
			if ( false !== $cached ) {
				return (string) $cached;
			}
		}

		$document = $this->builder->build( $context );

		if ( empty( $document['@graph'] ) ) {
			return '';
		}

		$json = Sanitizer::encode( $document );

		if ( $use_cache ) {
			Cache::set( $cache_key, $json );
		}

		return $json;
	}
}
