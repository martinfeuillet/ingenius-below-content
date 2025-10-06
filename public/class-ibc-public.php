<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ingenius.io
 * @since      1.0.0
 *
 * @package    IBC
 * @subpackage IBC/public
 */

/**
 * Class in charge to display the below content on attributes and tags
 *
 * @package    IBC
 * @subpackage IBC/public
 * @author     Ingenius <info@ingenius.io>
 */
class IBC_Public {
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

		add_action( 'woocommerce_after_main_content', array( $this, 'add_below_content' ), 10 );
		add_filter( 'woocommerce_product_get_image_id', array( $this, 'set_variable_product_thumbnail' ), 10, 2 );
		add_filter( 'woocommerce_page_title', array( $this, 'change_title_of_woocommerce_page' ), 10, 1 );
		add_filter( 'woocommerce_loop_product_link', array( $this, 'add_category_params_to_product_link' ), 10, 2 );
		add_filter( 'post_link', array( $this, 'add_category_params_to_product_link' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'add_category_params_to_product_link' ), 10, 2 );

		if ( $this->is_shoptimizer_theme() ) {
			remove_action( 'shoptimizer_before_content', 'shoptimizer_product_cat_banner', 15 );

			add_action( 'shoptimizer_before_content', array( $this, 'custom_shoptimizer_product_cat_banner' ), 15 );
			add_filter( 'single_term_title', array( $this, 'change_title_of_woocommerce_page_for_shoptimizer' ), 10, 1 );
		}
	}

	/**
	 * Check if the current theme is shoptimizer
	 *
	 * @return bool
	 */
	public function is_shoptimizer_theme(): bool {
		return wp_get_theme()->get( 'Name' ) === 'Shoptimizer' || wp_get_theme()->get( 'Template' ) === 'shoptimizer';
	}

	/**
	 * Custom function to display the category banner below header for shoptimizer theme
	 *
	 * @return void
	 */
	public function custom_shoptimizer_product_cat_banner() {
		if ( is_tax() && ! is_product_category() && ! is_product_tag() ) {

			$shoptimizer_layout_woocommerce_category_position = shoptimizer_get_option( 'shoptimizer_layout_woocommerce_category_position' );

			if ( 'below-header' === $shoptimizer_layout_woocommerce_category_position ) {

				$term = get_queried_object();

				if ( shoptimizer_is_acf_activated() ) {
					$categorybanner = function_exists( 'get_field' ) ? get_field( 'category_banner', $term ) : ''; // phpcs:ignore
				} else {
					$categorybanner = '';
				}

				// Remove the default actions as in the original function.
				remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
				remove_action( 'woocommerce_archive_description', 'shoptimizer_woocommerce_taxonomy_archive_description' );
				remove_action( 'woocommerce_archive_description', 'shoptimizer_category_image', 20 );
				remove_action( 'woocommerce_before_main_content', 'shoptimizer_archives_title', 20 );

				?>

				<?php if ( ! empty( $categorybanner ) ) : ?>
					<style>
						.shoptimizer-category-banner,
						.shoptimizer-category-banner.visible {
							background-image: url('<?php echo esc_url( $categorybanner ); ?>'); 
						}
					</style>
				<?php endif; ?>

				<?php if ( ! empty( $categorybanner ) ) { ?>
					<div class="shoptimizer-category-banner lazy-background">
					<?php } else { ?>
						<div class="shoptimizer-category-banner">
						<?php } ?>
						<div class="col-full">
							<h1><?php echo esc_html( apply_filters( 'woocommerce_page_title', single_cat_title( '', false ) ) ); ?></h1>
							<?php the_archive_description( '<div class="taxonomy-description">', '</div>' ); ?>
						</div>
						</div>
				<?php
			}
		}
	}


	/**
	 * Change thumbnail of variable product to match with the selected attribute.
	 *
	 * @param int        $image_id attachment ID of the image.
	 * @param WC_Product $product object of the product.
	 * @return int
	 */
	public function set_variable_product_thumbnail( $image_id, $product ) {
		// Handle category pages with attribute filtering.
		if ( is_product_category() ) {
			return $this->get_category_attribute_variation_image( $image_id, $product );
		}

		// Handle attribute taxonomy pages (original functionality).
		if ( ! is_tax() ) {
			return $image_id;
		}

		$queried_object = get_queried_object();

		if ( ! $queried_object || ! isset( $queried_object->taxonomy ) || ! isset( $queried_object->slug ) ) {
			return $image_id;
		}

		$taxonomy = $queried_object->taxonomy;
		$slug     = $queried_object->slug;

		if ( $product->is_type( 'variable' ) ) {
			$children = $product->get_children();

			foreach ( $children as $variation_id ) {
				$variation            = wc_get_product( $variation_id );
				$variation_attributes = $variation->get_attributes();

				foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
					if ( $attribute_name === $taxonomy && $attribute_value === $slug ) {
						$variation_image_id = $variation->get_image_id();

						if ( $variation_image_id ) {
							return $variation_image_id;
						}
					}
				}
			}
		}

		return $image_id;
	}

	/**
	 * Get variation image based on category attribute settings.
	 *
	 * @param int        $image_id Original image ID.
	 * @param WC_Product $product  Product object.
	 * @return int
	 */
	private function get_category_attribute_variation_image( $image_id, $product ) {
		// Only process variable products.
		if ( ! $product->is_type( 'variable' ) ) {
			return $image_id;
		}

		$term = get_queried_object();
		if ( ! $term || ! isset( $term->term_id ) ) {
			return $image_id;
		}

		// Get category attribute settings.
		$category_attributes = get_term_meta( $term->term_id, 'category_attributes', true );
		if ( empty( $category_attributes ) || ! is_array( $category_attributes ) ) {
			return $image_id;
		}

		// Get the first (and only) attribute and term.
		$selected_attribute = array_key_first( $category_attributes );
		$attribute_data     = $category_attributes[ $selected_attribute ];

		if ( empty( $attribute_data['terms'] ) ) {
			return $image_id;
		}

		$selected_term = $attribute_data['terms'][0];

		// Get product variations.
		$children = $product->get_children();

		foreach ( $children as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation ) {
				continue;
			}

			$variation_attributes = $variation->get_attributes();

			// Check if this variation has the matching attribute/term.
			foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
				$clean_attribute_name = str_replace( 'attribute_', '', $attribute_name );

				if ( $clean_attribute_name === $selected_attribute && $attribute_value === $selected_term ) {
					$variation_image_id = $variation->get_image_id();

					if ( $variation_image_id ) {
						return $variation_image_id;
					}
				}
			}
		}

		// If no matching variation found, return original image.
		return $image_id;
	}

	/**
	 * Change title of WooCommerce page for Shoptimizer theme.
	 *
	 * @param string $title string title of woocommerce page.
	 * @return string|void
	 */
	public function change_title_of_woocommerce_page_for_shoptimizer( $title ) {
		if ( ( is_product_category() || is_product_tag() ) && is_paged() ) {
			if ( get_query_var( 'paged' ) > 1 ) {
				// translators: %s is the current page number.
				$title .= ' - ' . sprintf( __( 'Page %s', 'ibc' ), max( 1, get_query_var( 'paged' ) ) );
			}
		}
		return $title;
	}


	/**
	 * Change title of WooCommerce page for attributes and tags
	 *
	 * @param string $title title of woocommerce page.
	 * @return string|void
	 */
	public function change_title_of_woocommerce_page( string $title ) {

		if ( $this->is_shoptimizer_theme() ) {
			if ( ! is_tax() || is_product_category() || is_product_tag() ) {
				return $title;
			}
		} elseif ( ! is_tax() ) {
			return $title;
		}

		$query      = get_queried_object();
		$title      = get_term_meta( $query->term_id, 'new_attr_title', true ) ? get_term_meta( $query->term_id, 'new_attr_title', true ) : $title;
		$attr_value = htmlspecialchars_decode( get_term_meta( $query->term_id, 'attr_value', true ) ) ? htmlspecialchars_decode( get_term_meta( $query->term_id, 'attr_value', true ) ) : null;

		$prefix_suffixe = get_term_meta( $query->term_id, 'prefix_suffixe', true ) ? get_term_meta( $query->term_id, 'prefix_suffixe', true ) : null;
		if ( $attr_value ) {
			if ( 'prefix' === $prefix_suffixe ) {
				$title = ucfirst( $attr_value ) . ' ' . $title;
			} else {
				$title = ucfirst( $title ) . ' ' . $attr_value;
			}
		}

		if ( is_paged() && get_query_var( 'paged' ) > 1 ) {
			// translators: %s is the current page number.
			$title .= ' - ' . sprintf( __( 'Page %s', 'woocommerce' ), max( 1, get_query_var( 'paged' ) ) );
		}

		return $title;
	}


	/**
	 * Add below content on woocommerce attribute archive and taxonomy archive
	 *
	 * @return void
	 */
	public function add_below_content(): void {
		if ( is_product_tag() ) {
			$term                        = get_queried_object();
			$below_tag_content           = get_term_meta( $term->term_id, 'below_tag_content', true );
			$below_tag_decoded_content   = htmlspecialchars_decode( $below_tag_content );
			$below_tag_formatted_content = wpautop( nl2br( $below_tag_decoded_content ) );
			echo "<div class='below-woocommerce-category'>" . wp_kses_post( $below_tag_formatted_content ) . '</div>';
		}
		if ( is_tax() ) {
			$term = get_queried_object();
			if ( 'product_tag' !== $term->taxonomy && 'product_cat' !== $term->taxonomy ) {
				$below_attr_content   = get_term_meta( $term->term_id, 'below_attr_content', true );
				$decoded_attr_content = htmlspecialchars_decode( $below_attr_content );
				$formatted_content    = wpautop( nl2br( $decoded_attr_content ) );
				echo "<div class='below-woocommerce-category'>" . wp_kses_post( $formatted_content ) . '</div>';
			}
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style( $this->ibc, plugin_dir_url( __FILE__ ) . 'css/ibc-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_script( $this->ibc, plugin_dir_url( __FILE__ ) . 'js/ibc-public.js', array( 'jquery' ), $this->version, false );

		// Pass category attribute data to JavaScript on category pages.
		if ( is_product_category() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->term_id ) ) {
				$category_attributes = get_term_meta( $term->term_id, 'category_attributes', true );
				if ( ! empty( $category_attributes ) && is_array( $category_attributes ) ) {
					$selected_attribute = array_key_first( $category_attributes );
					$attribute_data     = $category_attributes[ $selected_attribute ];

					if ( ! empty( $attribute_data['terms'] ) ) {
						$selected_term = $attribute_data['terms'][0];

						wp_localize_script(
							$this->ibc,
							'ibc_category_data',
							array(
								'attribute' => $selected_attribute,
								'term'      => $selected_term,
							)
						);
					}
				}
			}
		}

		// Pass category attribute data to JavaScript on product pages.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( is_product() ) {
			foreach ( $_GET as $param_key => $param_value ) {
				if ( strpos( $param_key, 'attribute_' ) === 0 ) {
					$attribute_name = sanitize_text_field( $param_key );
					$term_slug      = sanitize_text_field( wp_unslash( $param_value ) );

					wp_localize_script(
						$this->ibc,
						'ibc_variation_data',
						array(
							'attribute' => $attribute_name,
							'term'      => $term_slug,
						)
					);
					break;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Add category attribute parameters to product links on category pages.
	 *
	 * @param string     $link    Product link.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function add_category_params_to_product_link( $link, $product ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Only add parameters on category pages.
		if ( ! is_product_category() ) {
			return $link;
		}

		$term = get_queried_object();
		if ( ! $term || ! isset( $term->term_id ) ) {
			return $link;
		}

		// Get category attribute settings.
		$category_attributes = get_term_meta( $term->term_id, 'category_attributes', true );
		if ( empty( $category_attributes ) || ! is_array( $category_attributes ) ) {
			return $link;
		}

		// Get the first (and only) attribute and term.
		$selected_attribute = array_key_first( $category_attributes );
		$attribute_data     = $category_attributes[ $selected_attribute ];

		if ( empty( $attribute_data['terms'] ) ) {
			return $link;
		}

		$selected_term = $attribute_data['terms'][0];

		// Add parameters to the link using single parameter format.
		$link = add_query_arg(
			array(
				'attribute_' . $selected_attribute => $selected_term,
			),
			$link
		);

		return $link;
	}
}
