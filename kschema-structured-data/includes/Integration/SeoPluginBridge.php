<?php
/**
 * Coexistence bridge for third-party SEO plugins.
 *
 * @package KSchema
 */

namespace KSchema\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Detects active SEO plugins (Yoast, Rank Math, SEOPress) and reconciles our
 * JSON-LD graph with theirs according to the configured mode:
 *
 *   complement (default): drop the nodes the SEO plugin already emits so we
 *                         never duplicate them — unless the type is "forced".
 *   replace:              suppress the SEO plugin's own schema output so ours
 *                         is the single source of truth.
 *   standalone:           do nothing (assume no other schema source).
 */
class SeoPluginBridge {

	/**
	 * Register hooks based on the configured mode.
	 *
	 * @return void
	 */
	public function register() {
		$mode = $this->mode();

		if ( 'complement' === $mode ) {
			add_filter( 'kschema/graph', array( $this, 'reconcile' ) );
		} elseif ( 'replace' === $mode ) {
			$this->suppress_foreign_schema();
		}
	}

	/**
	 * Get the configured SEO coexistence mode.
	 *
	 * @return string
	 */
	private function mode() {
		$settings = get_option( 'kschema_settings', array() );
		return isset( $settings['seo_mode'] ) ? $settings['seo_mode'] : 'complement';
	}

	/**
	 * Detect the active SEO plugin.
	 *
	 * @return string|null One of "yoast", "rankmath", "seopress", or null.
	 */
	public function detect() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}
		if ( class_exists( '\\RankMath' ) || function_exists( 'rank_math' ) ) {
			return 'rankmath';
		}
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return 'seopress';
		}
		return null;
	}

	/**
	 * Remove nodes an active SEO plugin already outputs, in complement mode.
	 *
	 * @param array $document JSON-LD document (@context/@graph).
	 * @return array
	 */
	public function reconcile( $document ) {
		$active = $this->detect();
		if ( ! $active || empty( $document['@graph'] ) ) {
			return $document;
		}

		$settings = get_option( 'kschema_settings', array() );
		$forced   = isset( $settings['forced_types'] ) ? (array) $settings['forced_types'] : array();

		/**
		 * Filter the schema types considered "already handled" by the active
		 * SEO plugin and therefore dropped from our graph in complement mode.
		 *
		 * @param string[] $types  Overlapping type names.
		 * @param string   $active Detected SEO plugin slug.
		 */
		$overlap = apply_filters(
			'kschema/seo_overlap_types',
			array( 'WebSite', 'Organization', 'WebPage', 'Article', 'BreadcrumbList', 'Person' ),
			$active
		);

		$document['@graph'] = array_values(
			array_filter(
				$document['@graph'],
				static function ( $node ) use ( $overlap, $forced ) {
					$type = isset( $node['@type'] ) ? $node['@type'] : '';
					if ( in_array( $type, $forced, true ) ) {
						return true; // User forced ours to win.
					}
					return ! in_array( $type, $overlap, true );
				}
			)
		);

		return $document;
	}

	/**
	 * Disable the active SEO plugin's own schema output (replace mode).
	 *
	 * @return void
	 */
	private function suppress_foreign_schema() {
		// Yoast SEO.
		add_filter( 'wpseo_json_ld_output', '__return_false' );

		// Rank Math.
		add_filter(
			'rank_math/json_ld',
			static function () {
				return array();
			},
			99
		);

		// SEOPress.
		add_filter( 'seopress_schemas_auto_active', '__return_false' );
	}
}
