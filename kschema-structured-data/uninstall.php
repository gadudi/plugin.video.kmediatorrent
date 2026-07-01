<?php
/**
 * Uninstall cleanup.
 *
 * Removes all plugin options, per-object override meta, and cached
 * transients when the plugin is deleted from the WordPress admin.
 *
 * @package KSchema
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Delete options.
$kschema_options = array(
	'kschema_settings',
	'kschema_rules',
	'kschema_mapping_profiles',
	'kschema_db_version',
	'kschema_cache_index',
);
foreach ( $kschema_options as $kschema_option ) {
	delete_option( $kschema_option );
}

// Delete per-post and per-term override meta.
delete_post_meta_by_key( '_kschema_override' );

$kschema_term_meta_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s",
		'_kschema_override'
	)
);
foreach ( $kschema_term_meta_ids as $kschema_term_id ) {
	delete_term_meta( (int) $kschema_term_id, '_kschema_override' );
}

// Delete cached JSON-LD transients (and their timeouts).
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_kschema_ld_%' OR option_name LIKE '_transient_timeout_kschema_ld_%'"
);
