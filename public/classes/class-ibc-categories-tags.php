<?php
/**
 * Custom categories and tags functionality for jolie-parka.com
 *
 * @link       https://ingenius.io
 * @since      1.0.0
 *
 * @package    IBC
 * @subpackage IBC/public/classes
 */

/**
 * Custom categories and tags functionality specifically for jolie-parka.com
 *
 * @package    IBC
 * @subpackage IBC/public/classes
 * @author     Ingenius <info@ingenius.io>
 */
class IBC_Categories_Tags {
	/**
	 * The name of the plugin.
	 *
	 * @var string $ibc
	 */
	private string $ibc;

	/**
	 * The version of the plugin.
	 *
	 * @var string $version
	 */
	private string $version;

	/**
	 * Constructor.
	 *
	 * @param string $ibc The name of the plugin.
	 * @param string $version The version of the plugin.
	 */
	public function __construct( $ibc, $version ) {
		$this->ibc     = $ibc;
		$this->version = $version;

		if ( $this->is_jolie_parka_website() ) {
			$this->init_hooks();
		}
	}

	/**
	 * Check if current website is jolie-parka.com
	 *
	 * @return bool
	 */
	private function is_jolie_parka_website(): bool {
		$site_url = get_site_url();
		return strpos( $site_url, 'jolie-parka.com' ) !== false;
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_category_tag_on_product_image_on_woocommerce_loop' ), 5 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_categories_tags_below_product_title_on_single_product' ), 6 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue CSS styles
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'categories-tags',
			plugin_dir_url( __DIR__ ) . 'css/categories-tags.css',
			array(),
			$this->version
		);
	}

	/**
	 * Render category tag on product image in shop loop
	 *
	 * @return void
	 */
	public function render_category_tag_on_product_image_on_woocommerce_loop(): void {
		global $product;

		if ( ! $product || ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		$current_category = $this->get_current_category();

		if ( ! $current_category ) {
			return;
		}

		$product_categories   = wp_get_post_terms( $product->get_id(), 'product_cat' );
		$has_current_category = false;

		foreach ( $product_categories as $category ) {
			if ( $category->term_id === $current_category->term_id ) {
				$has_current_category = true;
				break;
			}
		}

		if ( ! $has_current_category ) {
			return;
		}

		echo '<div class="ibc-category-tag">' . esc_html( $current_category->name ) . '</div>';
	}

	/**
	 * Get the current category being viewed
	 *
	 * @return object|null
	 */
	private function get_current_category() {
		if ( is_product_category() ) {
			return get_queried_object();
		}

		return null;
	}



	/**
	 * Display categories below product title on single product page
	 *
	 * @return void
	 */
	public function display_categories_tags_below_product_title_on_single_product(): void {
		global $product;

		if ( ! $product || ! is_product() ) {
			return;
		}

		$product_categories = wp_get_post_terms( $product->get_id(), 'product_cat' );

		if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
			return;
		}

		$main_categories = $this->get_main_categories( $product_categories, 3 );

		echo '<div class="ibc-categories-container">';

		foreach ( $main_categories as $category ) {
			$category_link = get_term_link( $category );

			if ( ! is_wp_error( $category_link ) ) {
				echo '<a href="' . esc_url( $category_link ) . '" target="_blank" class="ibc-category-tag" style="display: inline-block; margin: 2px 5px 2px 0; position: static; text-decoration: none;">' . esc_html( $category->name ) . '</a>';
			}
		}

		echo '</div>';
	}

	/**
	 * Get main categories with priority order
	 *
	 * @param array $categories All product categories.
	 * @param int   $limit Maximum number of categories to return.
	 * @return array
	 */
	private function get_main_categories( $categories, $limit = 3 ): array {
		if ( empty( $categories ) ) {
			return array();
		}

		usort(
			$categories,
			function ( $a, $b ) {
				if ( 0 === $a->parent && 0 !== $b->parent ) {
					return -1;
				}
				if ( 0 !== $a->parent && 0 === $b->parent ) {
					return 1;
				}
				return strcmp( $a->name, $b->name );
			}
		);

		return array_slice( $categories, 0, $limit );
	}
}
