<?php
/**
 * Elementor compatibility bridge.
 *
 * @package KSchema
 */

namespace KSchema\Integration;

use KSchema\Schema\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures the plugin coexists cleanly with Elementor.
 *
 * The JSON-LD is emitted in wp_head, never in the Elementor DOM, so
 * rendering and editing are untouched. This bridge additionally prevents the
 * script from being injected inside the Elementor editor/preview iframe,
 * where it would be noise and could confuse the live editor.
 */
class ElementorBridge {

	/**
	 * Register hooks when Elementor is active.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->is_active() ) {
			return;
		}
		add_filter( 'kschema/should_output', array( $this, 'skip_in_editor' ), 30, 2 );
	}

	/**
	 * Whether Elementor is loaded.
	 *
	 * @return bool
	 */
	public function is_active() {
		return defined( 'ELEMENTOR_VERSION' ) || did_action( 'elementor/loaded' );
	}

	/**
	 * Suppress output inside the Elementor editor and preview.
	 *
	 * @param bool    $enabled Whether output is enabled.
	 * @param Context $context Request context (unused; signature parity).
	 * @return bool
	 */
	public function skip_in_editor( $enabled, $context ) {
		unset( $context );

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return $enabled;
		}

		$elementor = \Elementor\Plugin::$instance;

		if ( isset( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
			return false;
		}
		if ( isset( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
			return false;
		}

		return $enabled;
	}
}
