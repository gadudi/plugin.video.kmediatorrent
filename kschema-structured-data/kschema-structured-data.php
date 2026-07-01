<?php
/**
 * Plugin Name:       KSchema Structured Data
 * Plugin URI:        https://example.com/kschema-structured-data
 * Description:       Professional JSON-LD structured data (schema markup) management for posts, pages, custom post types, and taxonomies. Complements existing SEO plugins and is Elementor-compatible.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            KSchema
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kschema
 * Domain Path:       /languages
 *
 * @package KSchema
 */

namespace KSchema;

defined( 'ABSPATH' ) || exit;

/*
 * ---------------------------------------------------------------------------
 * Plugin constants.
 * ---------------------------------------------------------------------------
 */
define( 'KSCHEMA_VERSION', '0.1.0' );
define( 'KSCHEMA_PLUGIN_FILE', __FILE__ );
define( 'KSCHEMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KSCHEMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KSCHEMA_INCLUDES_DIR', KSCHEMA_PLUGIN_DIR . 'includes/' );
define( 'KSCHEMA_DATA_DIR', KSCHEMA_PLUGIN_DIR . 'data/' );

/*
 * ---------------------------------------------------------------------------
 * Autoloading.
 *
 * Prefer the Composer autoloader when present; otherwise fall back to a
 * lightweight PSR-4 autoloader so the plugin runs without a build step.
 * ---------------------------------------------------------------------------
 */
if ( is_readable( KSCHEMA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require KSCHEMA_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	require KSCHEMA_INCLUDES_DIR . 'Autoloader.php';
	( new Autoloader( 'KSchema\\', KSCHEMA_INCLUDES_DIR ) )->register();
}

/*
 * ---------------------------------------------------------------------------
 * Lifecycle hooks.
 * ---------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded so integrations
 * (ACF, Elementor, SEO plugins) can be feature-detected reliably.
 *
 * @return void
 */
function bootstrap() {
	Plugin::instance()->run();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );
