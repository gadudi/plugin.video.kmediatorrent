<?php
/**
 * Lightweight PSR-4 autoloader (Composer fallback).
 *
 * @package KSchema
 */

namespace KSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Maps a namespace prefix to a base directory and autoloads classes.
 */
class Autoloader {

	/**
	 * Namespace prefix, e.g. "KSchema\".
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Base directory for the prefix.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $prefix   Namespace prefix (with trailing separator).
	 * @param string $base_dir Base directory (with trailing slash).
	 */
	public function __construct( $prefix, $base_dir ) {
		$this->prefix   = $prefix;
		$this->base_dir = rtrim( $base_dir, '/\\' ) . '/';
	}

	/**
	 * Register the autoloader with SPL.
	 *
	 * @return void
	 */
	public function register() {
		spl_autoload_register( array( $this, 'load_class' ) );
	}

	/**
	 * Load a class file for a fully-qualified class name.
	 *
	 * @param string $class Fully-qualified class name.
	 * @return void
	 */
	public function load_class( $class ) {
		$len = strlen( $this->prefix );
		if ( 0 !== strncmp( $this->prefix, $class, $len ) ) {
			return;
		}

		$relative = substr( $class, $len );
		$file     = $this->base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
}
