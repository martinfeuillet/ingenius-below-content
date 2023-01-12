<?php

class IBC_Admin {
    public function __construct() {
        add_action('product_tag_edit_form_fields', array($this, 'add_product_tag_edit_meta_fields'), 10, 2);

        add_action('edit_term', array($this, 'save_metabox_on_product_tag_page'), 10, 3);
        add_action('created_term', array($this, 'save_metabox_on_product_tag_page'), 10, 3);

        if ( is_admin() && isset( $_GET['taxonomy'], $_GET['post_type'] ) && $_GET['post_type'] === 'product' && substr($_GET['taxonomy'], 0, 2) === 'pa' ) {
            $taxonomy_name = sanitize_text_field( $_GET['taxonomy'] );
            add_action( $taxonomy_name . '_edit_form_fields', array($this, 'add_custom_field_to_attr'), 10, 2 );
        }
        add_action( 'edit_term', array($this, 'save_metabox_on_product_attr_page'), 10, 1);
    }

    // add meta box on taxonomy single product tags at the end of the page
    public function add_product_tag_edit_meta_fields(object $term) {
        $second_desc = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_tag_content', true));
        ?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="below_tag_content"><?php echo __('below_tag_content', 'ibc'); ?></label></th>
			<td>
				<?php

                        $settings = array(
                            'textarea_name' => 'below_tag_content',
                            'value'         => $second_desc,
                            'quicktags'     => array('buttons' => 'em,strong,link'),
                            'tinymce'       => true,
                            'editor_css'    => '<style>#below_tag_content_ifr {height:250px !important;}</style>'
                        );

        wp_editor($second_desc, 'below_tag_content', $settings);
        ?>
			</td>
		</tr>
<?php
    }

    // add meta box on taxonomy single product attributes at the end of the page
    public function add_custom_field_to_attr(object $term, $taxonomy) {
        $below_attr_content = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_attr_content', true));
        $selected           = get_term_meta($term->term_id, 'prefix_suffixe', true);
        $attr_value         = htmlspecialchars_decode(get_term_meta($term->term_id, 'attr_value', true));
        ?>
        
		<tr class="form-field">
			<th scope="row" valign="top"><label for="below_tag_content"><?php echo __('below_attr_content', 'ibc'); ?></label></th>
			<td>
				<?php

                        $settings = array(
                            'textarea_name' => 'below_attr_content',
                            'value'         => $below_attr_content,
                            'quicktags'     => array('buttons' => 'em,strong,link'),
                            'tinymce'       => true,
                            'editor_css'    => '<style>#below_attr_content_ifr {height:250px !important;}</style>'
                        );

        wp_editor($below_attr_content, 'below_attr_content', $settings);
        ?>
			</td>
		</tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="attr_value"><?php echo __("Préfixer le titre de l'archive par du texte", 'ibc'); ?></label></th>
            <td><input type="text" name="attr_value" id="attr_value" placeholder="texte" value="<?php echo htmlspecialchars_decode($attr_value)?>"></td>
        </tr>
        <tr>
            <th scope="row" valign="top"><label for="prefix_suffixe"><?php echo __("position du texte par rapport à l'attribut", 'ibc'); ?></th>
            <td><select name="prefix_suffixe" id="prefix_suffixe">
                <option value="prefix" <?php echo $selected == 'prefix' ? 'selected' : ''; ?>><?php echo __('avant', 'ibc'); ?></option>
                <option value="suffix" <?php echo $selected == 'suffix' ? 'selected' : ''; ?>><?php echo __('après', 'ibc'); ?></option>
            </select></td>
        </tr>
<?php
    }

    //  save meta box on taxonomy single product tags
    public function save_metabox_on_product_tag_page(int $term_id, $tt_id = '', string $taxonomy = '') {
        if (isset($_POST['below_tag_content']) && 'product_tag' === $taxonomy) {
            update_term_meta($term_id, 'below_tag_content', esc_attr($_POST['below_tag_content']));
        }
    }

    // save meta box on taxonomy single product attributes
    public function save_metabox_on_product_attr_page($term_id) {
        if (isset($_POST['below_attr_content'])) {
            update_term_meta($term_id, 'below_attr_content', esc_attr($_POST['below_attr_content']));
        }
        if (isset($_POST['attr_value'])) {
            update_term_meta($term_id, 'attr_value', esc_attr($_POST['attr_value']));
        }
        if (isset($_POST['prefix_suffixe'])) {
            update_term_meta($term_id, 'prefix_suffixe', esc_attr($_POST['prefix_suffixe']));
        }
    }
}
