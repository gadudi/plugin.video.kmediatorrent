<?php
/**
 * Global settings + automation rules admin screen.
 *
 * @package KSchema
 */

namespace KSchema\Admin;

use KSchema\Schema\TypeRegistry;
use KSchema\Support\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the top-level "Schema" admin page and a rules editor built on
 * the Settings API (nonce/capability handling delegated to core).
 */
class SettingsPage {

	/**
	 * Admin page slug.
	 */
	const SLUG = 'kschema';

	/**
	 * Settings group for the rules option.
	 */
	const RULES_GROUP = 'kschema_rules_group';

	/**
	 * Rules option key.
	 */
	const RULES_OPTION = 'kschema_rules';

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
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the top-level admin menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Schema', 'kschema' ),
			__( 'Schema', 'kschema' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' ),
			'dashicons-networking',
			81
		);
	}

	/**
	 * Register the rules setting with a sanitize callback.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::RULES_GROUP,
			self::RULES_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_rules' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize and rebuild the rules array from submitted rows.
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array<int,array>
	 */
	public function sanitize_rules( $input ) {
		$rows  = is_array( $input ) ? $input : array();
		$clean = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$target = isset( $row['target'] ) ? sanitize_text_field( $row['target'] ) : '';
			$type   = isset( $row['schema_type'] ) ? sanitize_text_field( $row['schema_type'] ) : '';

			// Skip incomplete rows (e.g. the empty "add new" row).
			if ( '' === $target || '' === $type || false === strpos( $target, ':' ) ) {
				continue;
			}

			list( $target_type, $target_name ) = array_pad( explode( ':', $target, 2 ), 2, '' );
			if ( ! in_array( $target_type, array( 'post_type', 'taxonomy' ), true ) || '' === $target_name ) {
				continue;
			}

			$id = isset( $row['id'] ) && '' !== $row['id']
				? sanitize_key( $row['id'] )
				: 'rule_' . substr( md5( $target . $type . wp_rand() ), 0, 10 );

			$clean[] = array(
				'id'          => $id,
				'label'       => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '',
				'target_type' => $target_type,
				'target_name' => sanitize_key( $target_name ),
				'schema_type' => $type,
				'priority'    => isset( $row['priority'] ) ? (int) $row['priority'] : 10,
				'enabled'     => ! empty( $row['enabled'] ),
			);
		}

		return $clean;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rules   = (array) get_option( self::RULES_OPTION, array() );
		$rules[] = array(); // Trailing blank row for adding a new rule.
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Schema Automation Rules', 'kschema' ); ?></h1>
			<p><?php esc_html_e( 'Assign a schema type to each post type or taxonomy. Rules run automatically on the front end; per-post and per-term overrides take precedence.', 'kschema' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::RULES_GROUP ); ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Enabled', 'kschema' ); ?></th>
							<th><?php esc_html_e( 'Label', 'kschema' ); ?></th>
							<th><?php esc_html_e( 'Applies to', 'kschema' ); ?></th>
							<th><?php esc_html_e( 'Schema type', 'kschema' ); ?></th>
							<th><?php esc_html_e( 'Priority', 'kschema' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rules as $i => $rule ) : ?>
							<?php $this->render_row( (int) $i, (array) $rule ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( 'Leave the last (empty) row blank, or fill it in to add a new rule. Clear a row\'s schema type to delete it.', 'kschema' ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a single editable rule row.
	 *
	 * @param int   $i    Row index.
	 * @param array $rule Rule data.
	 * @return void
	 */
	private function render_row( $i, array $rule ) {
		$name         = self::RULES_OPTION . '[' . $i . ']';
		$enabled      = ! empty( $rule['enabled'] );
		$label        = isset( $rule['label'] ) ? $rule['label'] : '';
		$priority     = isset( $rule['priority'] ) ? (int) $rule['priority'] : 10;
		$current      = isset( $rule['target_type'], $rule['target_name'] )
			? $rule['target_type'] . ':' . $rule['target_name']
			: '';
		$current_type = isset( $rule['schema_type'] ) ? $rule['schema_type'] : '';
		?>
		<tr>
			<td>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( isset( $rule['id'] ) ? $rule['id'] : '' ); ?>" />
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $enabled ); ?> />
			</td>
			<td>
				<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Optional label', 'kschema' ); ?>" />
			</td>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[target]">
					<option value=""><?php esc_html_e( '— Select —', 'kschema' ); ?></option>
					<?php $this->render_target_options( $current ); ?>
				</select>
			</td>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[schema_type]">
					<option value=""><?php esc_html_e( '— None (delete) —', 'kschema' ); ?></option>
					<?php $this->render_type_options( $current_type ); ?>
				</select>
			</td>
			<td>
				<input type="number" class="small-text" name="<?php echo esc_attr( $name ); ?>[priority]" value="<?php echo esc_attr( (string) $priority ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render <option> elements for post types and taxonomies.
	 *
	 * @param string $current Currently selected "type:name" value.
	 * @return void
	 */
	private function render_target_options( $current ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		echo '<optgroup label="' . esc_attr__( 'Post types', 'kschema' ) . '">';
		foreach ( $post_types as $pt ) {
			$value = 'post_type:' . $pt->name;
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $pt->labels->singular_name )
			);
		}
		echo '</optgroup>';

		echo '<optgroup label="' . esc_attr__( 'Taxonomies', 'kschema' ) . '">';
		foreach ( $taxonomies as $tax ) {
			$value = 'taxonomy:' . $tax->name;
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $tax->labels->singular_name )
			);
		}
		echo '</optgroup>';
	}

	/**
	 * Render <option> elements for available schema types.
	 *
	 * @param string $current Currently selected type.
	 * @return void
	 */
	private function render_type_options( $current ) {
		// Types without a dedicated piece are still selectable (GenericPiece).
		foreach ( Types::choices( $this->registry ) as $type ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $type ),
				selected( $current, $type, false ),
				esc_html( $type )
			);
		}
	}
}
