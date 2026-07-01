<?php
/**
 * Per-post schema override meta box.
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
 * Adds a "Schema Override" meta box to public post types. Uses the classic
 * meta box path, which Elementor-edited posts still expose, so overrides
 * work regardless of the editing experience.
 */
class PostMetabox {

	/**
	 * Nonce action.
	 */
	const NONCE = 'kschema_post_override';

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_box' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the meta box on public post types.
	 *
	 * @return void
	 */
	public function add_box() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'kschema_override',
				__( 'Schema Override', 'kschema' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Post being edited.
	 * @return void
	 */
	public function render( $post ) {
		$override = $this->read( $post->ID );
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );

		$mode = $override['mode'];
		?>
		<p><strong><?php esc_html_e( 'Mode', 'kschema' ); ?></strong></p>
		<p>
			<label><input type="radio" name="kschema_override[mode]" value="inherit" <?php checked( $mode, 'inherit' ); ?> /> <?php esc_html_e( 'Inherit global rules', 'kschema' ); ?></label><br />
			<label><input type="radio" name="kschema_override[mode]" value="customize" <?php checked( $mode, 'customize' ); ?> /> <?php esc_html_e( 'Customize', 'kschema' ); ?></label><br />
			<label><input type="radio" name="kschema_override[mode]" value="disable" <?php checked( $mode, 'disable' ); ?> /> <?php esc_html_e( 'Disable schema here', 'kschema' ); ?></label>
		</p>

		<p><strong><?php esc_html_e( 'Add schema types', 'kschema' ); ?></strong></p>
		<select name="kschema_override[added_types][]" multiple size="4" style="width:100%;">
			<?php foreach ( Types::choices( $this->registry ) as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $override['added_types'], true ) ); ?>><?php echo esc_html( $type ); ?></option>
			<?php endforeach; ?>
		</select>

		<p><strong><?php esc_html_e( 'Disable schema types', 'kschema' ); ?></strong></p>
		<select name="kschema_override[disabled_types][]" multiple size="4" style="width:100%;">
			<?php foreach ( Types::choices( $this->registry ) as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $override['disabled_types'], true ) ); ?>><?php echo esc_html( $type ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( '"Add"/"Disable" apply only in Customize mode.', 'kschema' ); ?></p>
		<?php
	}

	/**
	 * Save the override.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw     = isset( $_POST['kschema_override'] ) ? wp_unslash( $_POST['kschema_override'] ) : array();
		$data    = Overrides::sanitize( is_array( $raw ) ? $raw : array() );

		if ( 'inherit' === $data['mode'] && empty( $data['added_types'] ) && empty( $data['disabled_types'] ) ) {
			delete_post_meta( $post_id, OverrideResolver::META_KEY );
			return;
		}

		update_post_meta( $post_id, OverrideResolver::META_KEY, wp_json_encode( $data ) );
	}

	/**
	 * Read and normalize a stored override.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function read( $post_id ) {
		$raw = get_post_meta( $post_id, OverrideResolver::META_KEY, true );
		if ( is_string( $raw ) && '' !== $raw ) {
			$raw = json_decode( $raw, true );
		}
		return Overrides::normalize( is_array( $raw ) ? $raw : array() );
	}
}
