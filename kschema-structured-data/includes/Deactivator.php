<?php
/**
 * Deactivation routines.
 *
 * @package KSchema
 */

namespace KSchema;

use KSchema\Support\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Handles cleanup that should happen on deactivation (but not uninstall).
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Flush the rendered JSON-LD cache so a re-activation starts clean.
		Cache::flush();
	}
}
