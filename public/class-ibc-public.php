<?php

class IBC_Public {

	private $IBC;

	private $version;

	private $test;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $IBC       The name of the plugin
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($IBC, $version) {

		$this->IBC = $IBC;
		$this->version = $version;

		// add_action('woocommerce_after_single_product_summary', array($this, 'add_below_content'), 11);
		add_action('woocommerce_after_main_content', array($this, 'add_below_content'), 11);
	}

	public function add_below_content() {
		if (is_product_tag()) {
			$term = get_queried_object();
			$below_tag_content = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_tag_content', true));
			echo "<div class='below-tag-content'>" . $below_tag_content . "</div>";
		}
	}

	// enqueue scripts and styles
	public function enqueue_styles() {
		wp_enqueue_style($this->IBC, plugin_dir_url(__FILE__) . '/css/ibc-public.css', array(), $this->version, 'all');
	}
}
