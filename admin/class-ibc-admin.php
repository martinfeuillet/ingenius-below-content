<?php

class IBC_Admin {


	private $IBC;

	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $IBC       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($IBC, $version) {

		$this->IBC = $IBC;
		$this->version = $version;

		// add_action('admin_init', array($this, 'add_plugin_page'));

		// register metafield on product tag and product attributes
		add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_meta_fields'));

		// add meta box on taxonomy single product tags at the end of the page
		add_action('product_tag_edit_form_fields', array($this, 'add_product_tag_edit_meta_fields'), 10, 2);

		//  save meta box on taxonomy single product tags
		add_action('edit_term', array($this, 'save_metabox_on_product_tag_page'), 10, 3);
		add_action('created_term', array($this, 'save_metabox_on_product_tag_page'), 10, 3);

		// add_action('edited_product_tag', array($this, 'tag_save_taxonomy_custom_meta'), 10, 2);
		// add_action('create_product_tag', array($this, 'tag_save_taxonomy_custom_meta'), 10, 2);
	}


	public function add_product_tag_edit_meta_fields($term) {
		$second_desc = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_tag_content', true));

?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="below_tag_content"><?php echo __('below_tag_content', 'ibc'); ?></label></th>
			<td>
				<?php

				$settings = array(
					'textarea_name' => 'below_tag_content',
					'value' => $second_desc,
					'quicktags' => array('buttons' => 'em,strong,link'),
					'tinymce' => true,
					'editor_css' => '<style>#below_tag_content_ifr {height:250px !important;}</style>'
				);

				wp_editor($second_desc, 'below_tag_content', $settings);
				?>
			</td>
		</tr>
<?php
	}

	public function save_metabox_on_product_tag_page($term_id, $tt_id = '', $taxonomy = '') {
		if (isset($_POST['below_tag_content']) && 'product_tag' === $taxonomy) {
			update_term_meta($term_id, 'below_tag_content', esc_attr($_POST['below_tag_content']));
		}
	}
}
