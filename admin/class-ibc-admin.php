<?php

class IBC_Admin {

	public function __construct() {

		add_action( 'product_tag_edit_form_fields', array( $this, 'add_product_tag_edit_meta_fields' ), 10, 2 );

		add_action( 'edit_term', array( $this, 'save_metabox_on_product_tag_page' ), 10, 3 );
		add_action( 'created_term', array( $this, 'save_metabox_on_product_tag_page' ), 10, 3 );

		if ( is_admin() && isset( $_GET['taxonomy'], $_GET['post_type'] ) && $_GET['post_type'] === 'product' && str_starts_with( $_GET['taxonomy'], 'pa' ) ) {
			$taxonomy_name = sanitize_text_field( $_GET['taxonomy'] );
			add_action( $taxonomy_name . '_edit_form_fields', array( $this, 'add_custom_field_to_attr' ), 10, 2 );
		}
		add_action( 'edit_term', array( $this, 'save_metabox_on_product_attr_page' ), 10, 1 );
	}


	/**
	 * @param WP_Term $term that represents the term being edited
	 * display the metabox on product tag page
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
	 * @param int    $term_id
	 * @param int    $tt_id
	 * @param string $taxonomy
	 * add meta box on taxonomy single product attributes at the end of the page
	 */
	public function add_custom_field_to_attr( object $term, string $taxonomy ): void {
		$below_attr_content = htmlspecialchars_decode( get_term_meta( $term->term_id, 'below_attr_content', true ) );
		$selected           = get_term_meta( $term->term_id, 'prefix_suffixe', true );
		$attr_value         = htmlspecialchars_decode( get_term_meta( $term->term_id, 'attr_value', true ) );
		$new_attr_title     = htmlspecialchars_decode( get_term_meta( $term->term_id, 'new_attr_title', true ) );
		?>

		<tr class="form-field">
			<th scope="row"><label for="below_tag_content"><?php echo __( 'below_attr_content', 'ibc' ); ?></label>
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
					for="attr_value"><?php echo __( "Préfixer le titre de l'archive par du texte", 'ibc' ); ?></label>
			</th>
			<td><input type="text" name="attr_value" id="attr_value" placeholder="texte"
						value="<?php echo htmlspecialchars_decode( $attr_value ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label
					for="prefix_suffixe"><?php echo __( "position du texte par rapport à l'attribut", 'ibc' ); ?>
			</th>
			<td><select name="prefix_suffixe" id="prefix_suffixe">
					<option
						value="prefix" <?php echo $selected == 'prefix' ? 'selected' : ''; ?>><?php echo __( 'avant', 'ibc' ); ?></option>
					<option
						value="suffix" <?php echo $selected == 'suffix' ? 'selected' : ''; ?>><?php echo __( 'après', 'ibc' ); ?></option>
				</select></td>
		</tr>
		<tr>
			<th scope="row"><label
					for="prefix_suffixe"><?php echo __( "Remplacer le nom de l'archive,utile pour les liaisons masculin/feminin ou pour le singulier/pluriel, laisser vide pour ne rien changer", 'ibc' ); ?>
			</th>
			<td>
				<input type="text" name="new_attr_title" id="new_attr_title" placeholder="Nouveau nom"
						value="<?php echo htmlspecialchars_decode( $new_attr_title ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * @param int    $term_id
	 * @param $tt_id
	 * @param string $taxonomy
	 * save meta box on taxonomy product tag
	 */
	public function save_metabox_on_product_tag_page( int $term_id, $tt_id = '', string $taxonomy = '' ): void {
		if ( isset( $_POST['below_tag_content'] ) && 'product_tag' === $taxonomy ) {
			update_term_meta( $term_id, 'below_tag_content', esc_attr( $_POST['below_tag_content'] ) );
		}
	}

	/**
	 *  Save meta box on taxonomy single product attributes
	 *
	 * @param int $term_id
	 */
	public function save_metabox_on_product_attr_page( int $term_id ): void {
		if ( isset( $_POST['below_attr_content'] ) ) {
			update_term_meta( $term_id, 'below_attr_content', esc_attr( $_POST['below_attr_content'] ) );
		}
		if ( isset( $_POST['attr_value'] ) ) {
			update_term_meta( $term_id, 'attr_value', esc_attr( $_POST['attr_value'] ) );
		}
		if ( isset( $_POST['prefix_suffixe'] ) ) {
			update_term_meta( $term_id, 'prefix_suffixe', esc_attr( $_POST['prefix_suffixe'] ) );
		}
		if ( isset( $_POST['new_attr_title'] ) ) {
			update_term_meta( $term_id, 'new_attr_title', esc_attr( $_POST['new_attr_title'] ) );
		}
	}
}
