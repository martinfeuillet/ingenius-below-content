<?php
/**
 * IBC Admin Class
 *
 * Handles admin-specific functionality for the InGenius Below Content plugin.
 *
 * @package IBC
 * @subpackage IBC/admin
 */

/**
 * Class IBC_Admin
 *
 * Handles the display and saving of metaboxes on product tag and product attribute pages
 * for the InGenius Below Content plugin.
 *
 * @package IBC
 * @subpackage IBC/admin
 */
class IBC_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'product_tag_edit_form_fields', array( $this, 'add_product_tag_edit_meta_fields' ), 10, 2 );
		add_action( 'product_cat_edit_form_fields', array( $this, 'add_product_category_edit_meta_fields' ), 10, 2 );

		add_action( 'edit_term', array( $this, 'save_metabox_on_product_tag_page' ), 10, 3 );
		add_action( 'created_term', array( $this, 'save_metabox_on_product_tag_page' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_metabox_on_product_category_page' ), 10, 3 );
		add_action( 'created_term', array( $this, 'save_metabox_on_product_category_page' ), 10, 3 );

		if ( is_admin() && isset( $_GET['taxonomy'], $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) && str_starts_with( sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ), 'pa' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action( $taxonomy_name . '_edit_form_fields', array( $this, 'add_custom_field_to_attr' ), 10, 2 );
		}
		add_action( 'edit_term', array( $this, 'save_metabox_on_product_attr_page' ), 10, 1 );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_get_attribute_terms', array( $this, 'ajax_get_attribute_terms' ) );
	}


	/**
	 * Displays the metabox for editing product tag meta fields.
	 *
	 * @param WP_Term $term that represents the term being edited.
	 */
	public function add_product_tag_edit_meta_fields( WP_Term $term ): void {
		$second_desc = htmlspecialchars_decode( get_term_meta( $term->term_id, 'below_tag_content', true ) );
		?>
		<tr class="form-field">
			<th scope="row"><label for="below_tag_content"><?php esc_html_e( 'below_tag_content', 'ibc' ); ?></label>
			</th>
			<td>
				<?php
				wp_editor(
					$second_desc,
					'below_tag_content',
					array(
						'textarea_name' => 'below_tag_content',
						'textarea_rows' => 10,
						'textarea_cols' => 50,
					)
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Displays the metabox for editing product category meta fields with attribute selection.
	 *
	 * @param WP_Term $term that represents the term being edited.
	 */
	public function add_product_category_edit_meta_fields( WP_Term $term ): void {
		$selected_attributes = get_term_meta( $term->term_id, 'category_attributes', true );
		$selected_attribute  = '';
		$selected_term       = '';

		// Extract single attribute and term from stored data.
		if ( is_array( $selected_attributes ) && ! empty( $selected_attributes ) ) {
			$selected_attribute = array_key_first( $selected_attributes );
			$selected_term      = ! empty( $selected_attributes[ $selected_attribute ]['terms'] ) ? $selected_attributes[ $selected_attribute ]['terms'][0] : '';
		}

		// Get all product attributes.
		$attributes = wc_get_attribute_taxonomies();

		wp_nonce_field( 'ibc_category_attributes_nonce', 'ibc_category_attributes_nonce' );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="category_attributes"><?php esc_html_e( 'Category Attribute Filter', 'ibc' ); ?></label>
			</th>
			<td>
				<div id="ibc-attributes-container">
					<?php if ( ! empty( $attributes ) ) : ?>
						<!-- None option -->
						<div class="ibc-attribute-wrapper">
							<label>
								<input type="radio"
									class="ibc-attribute-radio"
									name="selected_attribute"
									value=""
									data-attribute=""
									<?php checked( empty( $selected_attribute ) ); ?> />
								<?php esc_html_e( 'No attribute filter', 'ibc' ); ?>
							</label>
						</div>
						
						<?php foreach ( $attributes as $attribute ) : ?>
							<?php
							$attribute_name = wc_attribute_taxonomy_name( $attribute->attribute_name );
							$is_selected    = ( $selected_attribute === $attribute_name );
							?>
							<div class="ibc-attribute-wrapper">
								<label>
									<input type="radio"
										class="ibc-attribute-radio"
										name="selected_attribute"
										value="<?php echo esc_attr( $attribute_name ); ?>"
										data-attribute="<?php echo esc_attr( $attribute_name ); ?>"
										<?php checked( $is_selected ); ?> />
									<?php echo esc_html( $attribute->attribute_label ); ?>
								</label>
								<div class="ibc-terms-container"
									id="terms-<?php echo esc_attr( $attribute_name ); ?>"
									style="<?php echo $is_selected ? '' : 'display: none;'; ?>">
									<?php
									if ( $is_selected ) {
										$this->display_attribute_terms( $attribute_name, array( $selected_term ) );
									}
									?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'No product attributes found.', 'ibc' ); ?></p>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}
	/**
	 * Adds meta box on taxonomy single product attributes at the end of the page.
	 *
	 * @param object $term     The term object being edited.
	 * @param string $taxonomy The taxonomy name.
	 */
	public function add_custom_field_to_attr( object $term, string $taxonomy ): void {
		$below_attr_content = htmlspecialchars_decode( get_term_meta( $term->term_id, 'below_attr_content', true ) );
		$selected           = get_term_meta( $term->term_id, 'prefix_suffixe', true );
		$attr_value         = htmlspecialchars_decode( get_term_meta( $term->term_id, 'attr_value', true ) );
		$new_attr_title     = htmlspecialchars_decode( get_term_meta( $term->term_id, 'new_attr_title', true ) );

		// Use taxonomy parameter to avoid unused parameter warning.
		$taxonomy_label = $taxonomy ? ucfirst( str_replace( 'pa_', '', $taxonomy ) ) : '';
		?>

		<tr class="form-field">
			<th scope="row"><label for="below_tag_content"><?php echo esc_html__( 'below_attr_content', 'ibc' ); ?></label>
			</th>
			<td>
				<?php
				wp_editor(
					$below_attr_content,
					'below_attr_content',
					array(
						'textarea_name' => 'below_attr_content',
						'textarea_rows' => 10,
						'textarea_cols' => 50,
					)
				);
				?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label
					for="attr_value"><?php echo esc_html__( "Préfixer le titre de l'archive par du texte", 'ibc' ); ?></label>
			</th>
			<td><input type="text" name="attr_value" id="attr_value" placeholder="texte"
						value="<?php echo esc_attr( $attr_value ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label
					for="prefix_suffixe"><?php echo esc_html__( "position du texte par rapport à l'attribut", 'ibc' ); ?>
			</th>
			<td><select name="prefix_suffixe" id="prefix_suffixe">
					<option
						value="prefix" <?php echo 'prefix' === $selected ? 'selected' : ''; ?>><?php echo esc_html( __( 'avant', 'ibc' ) ); ?></option>
					<option
						value="suffix" <?php echo 'suffix' === $selected ? 'selected' : ''; ?>><?php echo esc_html( __( 'après', 'ibc' ) ); ?></option>
				</select></td>
		</tr>
		<tr>
			<th scope="row"><label
					for="new_attr_title"><?php echo esc_html__( "Remplacer le nom de l'archive,utile pour les liaisons masculin/feminin ou pour le singulier/pluriel, laisser vide pour ne rien changer", 'ibc' ); ?>
			</th>
			<td>
				<input type="text" name="new_attr_title" id="new_attr_title" placeholder="Nouveau nom"
						value="<?php echo esc_attr( $new_attr_title ); ?>">
				<?php if ( $taxonomy_label ) : ?>
					<?php /* translators: %s is the attribute name */ ?>
					<small><?php echo esc_html( sprintf( __( 'Attribute: %s', 'ibc' ), $taxonomy_label ) ); ?></small>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save meta box on taxonomy product tag.
	 *
	 * @param int    $term_id  The ID of the term being saved.
	 * @param mixed  $tt_id    The term taxonomy ID.
	 * @param string $taxonomy The taxonomy name.
	 */
	public function save_metabox_on_product_tag_page( int $term_id, $tt_id = '', string $taxonomy = '' ): void {
		if ( isset( $_POST['below_tag_content'] ) && 'product_tag' === $taxonomy ) { // phpcs:ignore 
			update_term_meta( $term_id, 'below_tag_content', esc_attr( $_POST['below_tag_content'] ) ); // phpcs:ignore
		}
	}

	/**
	 * Save meta box on taxonomy product category.
	 *
	 * @param int    $term_id  The ID of the term being saved.
	 * @param mixed  $tt_id    The term taxonomy ID.
	 * @param string $taxonomy The taxonomy name.
	 */
	public function save_metabox_on_product_category_page( int $term_id, $tt_id = '', string $taxonomy = '' ): void {
		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['ibc_category_attributes_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ibc_category_attributes_nonce'] ) ), 'ibc_category_attributes_nonce' ) ) { // phpcs:ignore
			return;
		}

		$category_attributes = array();

		// Handle single attribute selection.
		$selected_attribute = isset( $_POST['selected_attribute'] ) ? sanitize_text_field( $_POST['selected_attribute'] ) : ''; // phpcs:ignore

		if ( ! empty( $selected_attribute ) ) {
			// Get the selected term for this attribute.
			$term_field_name = 'category_attributes[' . $selected_attribute . '][term]';
			$selected_term = isset( $_POST['category_attributes'][ $selected_attribute ]['term'] ) ? sanitize_text_field( $_POST['category_attributes'][ $selected_attribute ]['term'] ) : ''; // phpcs:ignore

			// Only store if a term is selected (not empty).
			if ( ! empty( $selected_term ) ) {
				$category_attributes[ $selected_attribute ] = array(
					'enabled' => true,
					'terms'   => array( $selected_term ),
				);
			}
		}

		update_term_meta( $term_id, 'category_attributes', $category_attributes );
	}

	/**
	 * Display attribute terms for selection.
	 *
	 * @param string $attribute_name The attribute taxonomy name.
	 * @param array  $selected_terms Array of selected term slugs.
	 */
	private function display_attribute_terms( string $attribute_name, array $selected_terms = array() ): void {
		$terms = get_terms(
			array(
				'taxonomy'   => $attribute_name,
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			echo '<div class="ibc-terms-list">';

			// Add "None" option for radio buttons.
			$selected_term = ! empty( $selected_terms ) ? $selected_terms[0] : '';
			echo '<label>';
			echo '<input type="radio" name="category_attributes[' . esc_attr( $attribute_name ) . '][term]" value="" ' . ( empty( $selected_term ) ? 'checked' : '' ) . ' />';
			echo esc_html__( 'None', 'ibc' );
			echo '</label><br>';

			foreach ( $terms as $term ) {
				$checked = ( $term->slug === $selected_term ) ? 'checked' : '';
				echo '<label>';
				echo '<input type="radio" name="category_attributes[' . esc_attr( $attribute_name ) . '][term]" value="' . esc_attr( $term->slug ) . '" ' . esc_attr( $checked ) . ' />';
				echo esc_html( $term->name );
				echo '</label><br>';
			}
			echo '</div>';
		} else {
			echo '<p>' . esc_html__( 'No terms found for this attribute.', 'ibc' ) . '</p>';
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue_admin_scripts(): void {
		$screen = get_current_screen();
		if ( $screen && ( 'edit-product_cat' === $screen->id || ( isset( $_GET['taxonomy'] ) && 'product_cat' === sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_script(
				'ibc-admin',
				plugin_dir_url( __FILE__ ) . 'js/ibc-admin.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			wp_localize_script(
				'ibc-admin',
				'ibc_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'ibc_ajax_nonce' ),
				)
			);

			wp_enqueue_style(
				'ibc-admin',
				plugin_dir_url( __FILE__ ) . 'css/ibc-admin.css',
				array(),
				'1.0.0'
			);
		}
	}

	/**
	 * AJAX handler to get attribute terms.
	 */
	public function ajax_get_attribute_terms(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ibc_ajax_nonce' ) ) { // phpcs:ignore
			wp_die( 'Security check failed' );
		}

		$attribute_name = sanitize_text_field( $_POST['attribute'] ?? '' ); // phpcs:ignore

		if ( empty( $attribute_name ) ) {
			wp_die( 'Invalid attribute' );
		}

		ob_start();
		$this->display_attribute_terms( $attribute_name );
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	/**
	 *  Save meta box on taxonomy single product attributes
	 *
	 * @param int $term_id The ID of the term being saved.
	 */
	public function save_metabox_on_product_attr_page( int $term_id ): void {
		if ( isset( $_POST['below_attr_content'] ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'below_attr_content', esc_attr( $_POST['below_attr_content'] ) ); // phpcs:ignore
		}
		if ( isset( $_POST['attr_value'] ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'attr_value', esc_attr( $_POST['attr_value'] ) ); // phpcs:ignore
		}
		if ( isset( $_POST['prefix_suffixe'] ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'prefix_suffixe', esc_attr( $_POST['prefix_suffixe'] ) ); // phpcs:ignore
		}
		if ( isset( $_POST['new_attr_title'] ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'new_attr_title', esc_attr( $_POST['new_attr_title'] ) ); // phpcs:ignore
		}
	}
}
