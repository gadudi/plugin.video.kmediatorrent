<?php
/**
 * Activation routines.
 *
 * @package KSchema
 */

namespace KSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Handles first-time setup, default options, and version migrations.
 */
class Activator {

	/**
	 * Option key holding the installed schema/config version.
	 */
	const VERSION_OPTION = 'kschema_db_version';

	/**
	 * Option key holding global settings.
	 */
	const SETTINGS_OPTION = 'kschema_settings';

	/**
	 * Option key holding automation rules.
	 */
	const RULES_OPTION = 'kschema_rules';

	/**
	 * Option key holding named mapping profiles.
	 */
	const PROFILES_OPTION = 'kschema_mapping_profiles';

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::seed_defaults();
		self::maybe_migrate();

		update_option( self::VERSION_OPTION, KSCHEMA_VERSION );
	}

	/**
	 * Seed default option values on first install without clobbering
	 * any existing configuration.
	 *
	 * @return void
	 */
	private static function seed_defaults() {
		if ( false === get_option( self::SETTINGS_OPTION, false ) ) {
			add_option(
				self::SETTINGS_OPTION,
				array(
					// "complement" | "replace" | "standalone".
					'seo_mode'        => 'complement',
					'output_enabled'  => true,
					'cache_enabled'   => true,
					// Per-type "force ours" overrides for the SEO bridge.
					'forced_types'    => array(),
				)
			);
		}

		if ( false === get_option( self::RULES_OPTION, false ) ) {
			add_option(
				self::RULES_OPTION,
				array(
					array(
						'id'          => 'default_post_article',
						'label'       => 'Posts as Article',
						'target_type' => 'post_type',
						'target_name' => 'post',
						'schema_type' => 'Article',
						'priority'    => 10,
						'enabled'     => true,
					),
					array(
						'id'          => 'default_page_webpage',
						'label'       => 'Pages as WebPage',
						'target_type' => 'post_type',
						'target_name' => 'page',
						'schema_type' => 'WebPage',
						'priority'    => 10,
						'enabled'     => true,
					),
				)
			);
		}

		if ( false === get_option( self::PROFILES_OPTION, false ) ) {
			add_option( self::PROFILES_OPTION, array() );
		}
	}

	/**
	 * Run version-gated migrations. Placeholder for future schema changes.
	 *
	 * @return void
	 */
	private static function maybe_migrate() {
		$installed = get_option( self::VERSION_OPTION, '0.0.0' );

		if ( version_compare( $installed, KSCHEMA_VERSION, '>=' ) ) {
			return;
		}

		// Future: run ordered migration callbacks keyed by version.
	}
}
