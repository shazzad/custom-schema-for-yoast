<?php
/**
 * The editor meta box.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast\Admin;

use Shazzad\CustomSchemaForYoast\Meta;
use Shazzad\CustomSchemaForYoast\Validator;
use Shazzad\CustomSchemaForYoast\Yoast\Hooks as YoastHooks;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves the "Custom Schema" box on every public post type.
 */
final class MetaBox {

	const NONCE_ACTION = 'csfy_save_meta';
	const NONCE_FIELD  = 'csfy_nonce';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add' ] );
		add_action( 'save_post', [ $this, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Register the box for each public post type; admins only.
	 *
	 * @return void
	 */
	public function add(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( Meta::post_types() as $post_type ) {
			add_meta_box(
				'csfy-schema',
				__( 'Custom Schema (JSON-LD)', 'custom-schema-for-yoast' ),
				[ $this, 'render' ],
				$post_type,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Assets on edit screens of supported post types only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, Meta::post_types(), true ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style( 'csfy-admin', CSFY_URL . 'assets/admin.css', [], CSFY_VERSION );
		wp_enqueue_script( 'csfy-admin', CSFY_URL . 'assets/admin.js', [], CSFY_VERSION, true );

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, used to prefill a template.
		$published = $post_id && 'publish' === get_post_status( $post_id );
		wp_localize_script(
			'csfy-admin',
			'csfyData',
			[
				'permalink' => $published ? (string) get_permalink( $post_id ) : '',
				'title'     => $post_id ? get_post_field( 'post_title', $post_id ) : '',
				'confirm'   => __( 'Replace the current JSON with the template?', 'custom-schema-for-yoast' ),
			]
		);
	}

	/**
	 * Box markup.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		$json   = Meta::json( $post->ID );
		$mode   = Meta::mode( $post->ID );
		$main   = Meta::main_entity( $post->ID );
		$result = Validator::parse( $json );
		$yoast  = YoastHooks::available();

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<div class="csfy-box">
			<fieldset class="csfy-mode">
				<legend class="screen-reader-text"><?php esc_html_e( 'Output mode', 'custom-schema-for-yoast' ); ?></legend>
				<label>
					<input type="radio" name="csfy_mode" value="append" <?php checked( $mode, Meta::MODE_APPEND ); ?> />
					<strong><?php esc_html_e( 'Append to Yoast\'s graph', 'custom-schema-for-yoast' ); ?></strong>
					<span class="description"><?php esc_html_e( 'Your nodes join Yoast\'s @graph; breadcrumbs, Organization and WebSite stay.', 'custom-schema-for-yoast' ); ?></span>
				</label>
				<label>
					<input type="radio" name="csfy_mode" value="replace" <?php checked( $mode, Meta::MODE_REPLACE ); ?> />
					<strong><?php esc_html_e( 'Replace Yoast\'s schema on this page', 'custom-schema-for-yoast' ); ?></strong>
					<span class="description"><?php esc_html_e( 'Yoast prints nothing here; only your JSON is output.', 'custom-schema-for-yoast' ); ?></span>
				</label>
			</fieldset>

			<p class="csfy-main-entity">
				<label>
					<input type="checkbox" name="csfy_main_entity" value="1" <?php checked( $main ); ?> <?php disabled( $mode, Meta::MODE_REPLACE ); ?> />
					<?php esc_html_e( 'Set as the page\'s main entity (WebPage.mainEntity points at your first node)', 'custom-schema-for-yoast' ); ?>
				</label>
			</p>

			<p>
				<label for="csfy_json" class="screen-reader-text"><?php esc_html_e( 'JSON-LD', 'custom-schema-for-yoast' ); ?></label>
				<textarea id="csfy_json" name="csfy_json" class="widefat code" rows="18" spellcheck="false" placeholder='{"@type": "SoftwareApplication", "name": "…"}'><?php echo esc_textarea( $json ); ?></textarea>
			</p>

			<p>
				<button type="button" class="button" id="csfy-insert-template"><?php esc_html_e( 'Insert SoftwareApplication template', 'custom-schema-for-yoast' ); ?></button>
				<span class="description"><?php esc_html_e( 'Accepts one node, an array of nodes, or a full document with @graph.', 'custom-schema-for-yoast' ); ?></span>
			</p>

			<?php if ( ! $yoast ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Yoast SEO is not active; the JSON below is printed as a standalone script in either mode.', 'custom-schema-for-yoast' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $yoast && ! YoastHooks::prints_schema() ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Yoast SEO\'s schema output is disabled in its settings; the JSON below is printed as a standalone script in either mode.', 'custom-schema-for-yoast' ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! $result->ok ) : ?>
				<div class="notice notice-error inline"><p>
					<strong><?php esc_html_e( 'Invalid JSON — nothing is output on the front end.', 'custom-schema-for-yoast' ); ?></strong>
					<?php echo esc_html( (string) $result->error ); ?>
				</p></div>
			<?php elseif ( ! $result->is_empty() ) : ?>
				<div class="notice notice-success inline"><p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: node count, 2: mode label */
							_n( 'Valid JSON — %1$d node will be output in %2$s mode.', 'Valid JSON — %1$d nodes will be output in %2$s mode.', count( $result->nodes ), 'custom-schema-for-yoast' ),
							count( $result->nodes ),
							Meta::MODE_REPLACE === $mode ? __( 'Replace', 'custom-schema-for-yoast' ) : __( 'Append', 'custom-schema-for-yoast' )
						)
					);
					?>
				</p></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Persist. $_POST values are passed slashed to update_post_meta, which unslashes exactly once.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, Meta::post_types(), true ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! array_key_exists( 'csfy_json', $_POST ) ) {
			return; // Box not on this form (e.g. quick edit).
		}

		// Raw JSON on purpose: it is validated by json_decode and only ever re-encoded on output, never echoed.
		$json = is_string( $_POST['csfy_json'] ) ? $_POST['csfy_json'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- see above; update_post_meta unslashes.
		update_post_meta( $post_id, Meta::KEY_JSON, $json );

		$mode = isset( $_POST['csfy_mode'] ) ? sanitize_key( wp_unslash( $_POST['csfy_mode'] ) ) : Meta::MODE_APPEND;
		$mode = Meta::sanitize_mode( $mode );
		update_post_meta( $post_id, Meta::KEY_MODE, $mode );

		// The block editor submits via fetch, so the disabled checkbox is not sent in Replace mode; leave the stored choice alone then.
		if ( Meta::MODE_APPEND === $mode ) {
			update_post_meta( $post_id, Meta::KEY_MAIN, isset( $_POST['csfy_main_entity'] ) ? '1' : '0' );
		}
	}
}
