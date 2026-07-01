<?php
/**
 * Per-term schema override fields.
 *
 * @package KSchema
 */

namespace KSchema\Admin;

use KSchema\Override\OverrideResolver;
use KSchema\Schema\TypeRegistry;
use KSchema\Support\Overrides;
use KSchema\Support\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Adds schema override fields to the term edit screen of public taxonomies.
 */
class TermFields {

	/**
	 * Nonce action.
	 */
	const NONCE = 'kschema_term_override';

	/**
	 * Type registry.
	 *
	 * @var TypeRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param TypeRegistry $registry Type registry.
	 */
	public function __construct( TypeRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Register hooks for each public taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'hook_taxonomies' ) );
	}

	/**
	 * Attach edit-field and save hooks to public taxonomies.
	 *
	 * @return void
	 */
	public function hook_taxonomies() {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render' ), 10, 1 );
			add_action( "edited_{$taxonomy}", array( $this, 'save' ), 10, 1 );
		}
	}

	/**
	 * Render the override fields on the term edit screen.
	 *
	 * @param \WP_Term $term Term being edited.
	 * @return void
	 */
	public function render( $term ) {
		$override = $this->read( $term->term_id );
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		$mode = $override['mode'];
		?>
		<tr class="form-field">
			<th scope="row"><label><?php esc_html_e( 'Schema mode', 'kschema' ); ?></label></th>
			<td>
				<label><input type="radio" name="kschema_override[mode]" value="inherit" <?php checked( $mode, 'inherit' ); ?> /> <?php esc_html_e( 'Inherit global rules', 'kschema' ); ?></label><br />
				<label><input type="radio" name="kschema_override[mode]" value="customize" <?php checked( $mode, 'customize' ); ?> /> <?php esc_html_e( 'Customize', 'kschema' ); ?></label><br />
				<label><input type="radio" name="kschema_override[mode]" value="disable" <?php checked( $mode, 'disable' ); ?> /> <?php esc_html_e( 'Disable schema for this term', 'kschema' ); ?></label>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label><?php esc_html_e( 'Add schema types', 'kschema' ); ?></label></th>
			<td>
				<select name="kschema_override[added_types][]" multiple size="4" style="min-width:220px;">
					<?php foreach ( Types::choices( $this->registry ) as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $override['added_types'], true ) ); ?>><?php echo esc_html( $type ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label><?php esc_html_e( 'Disable schema types', 'kschema' ); ?></label></th>
			<td>
				<select name="kschema_override[disabled_types][]" multiple size="4" style="min-width:220px;">
					<?php foreach ( Types::choices( $this->registry ) as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $override['disabled_types'], true ) ); ?>><?php echo esc_html( $type ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( '"Add"/"Disable" apply only in Customize mode.', 'kschema' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the term override.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$raw  = isset( $_POST['kschema_override'] ) ? wp_unslash( $_POST['kschema_override'] ) : array();
		$data = Overrides::sanitize( is_array( $raw ) ? $raw : array() );

		if ( 'inherit' === $data['mode'] && empty( $data['added_types'] ) && empty( $data['disabled_types'] ) ) {
			delete_term_meta( $term_id, OverrideResolver::META_KEY );
			return;
		}

		update_term_meta( $term_id, OverrideResolver::META_KEY, wp_json_encode( $data ) );
	}

	/**
	 * Read and normalize a stored term override.
	 *
	 * @param int $term_id Term ID.
	 * @return array
	 */
	private function read( $term_id ) {
		$raw = get_term_meta( $term_id, OverrideResolver::META_KEY, true );
		if ( is_string( $raw ) && '' !== $raw ) {
			$raw = json_decode( $raw, true );
		}
		return Overrides::normalize( is_array( $raw ) ? $raw : array() );
	}
}
