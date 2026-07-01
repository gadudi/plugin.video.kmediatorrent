<?php
/**
 * Main plugin container / bootstrapper.
 *
 * @package KSchema
 */

namespace KSchema;

use KSchema\Frontend\HeadInjector;
use KSchema\Schema\SchemaBuilder;
use KSchema\Schema\TypeRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton that wires together the plugin subsystems.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether run() has already executed.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Boot the plugin subsystems.
	 *
	 * @return void
	 */
	public function run() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		$this->load_textdomain();

		$registry = new TypeRegistry();
		$builder  = new SchemaBuilder( $registry );

		( new HeadInjector( $builder ) )->register();

		/**
		 * Fires after the plugin has booted its core subsystems.
		 *
		 * Later phases (Admin settings, override metaboxes, integrations)
		 * hook here.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'kschema/booted', $this );
	}

	/**
	 * Load the plugin translations.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'kschema',
			false,
			dirname( plugin_basename( KSCHEMA_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
