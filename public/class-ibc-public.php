<?php

class IBC_Public {
	private string $IBC;

	private string $version;

	public function __construct( $IBC, $version ) {
		$this->IBC     = $IBC;
		$this->version = $version;

		// && isset( get_queried_object()->taxonomy ) && str_starts_with( get_queried_object()->taxonomy , 'pa_' )
		add_action( 'woocommerce_after_main_content', array( $this, 'add_below_content' ), 10 );
		add_filter( 'woocommerce_product_get_image_id', array( $this, 'set_variable_product_thumbnail' ), 10, 2 );
		add_filter( 'woocommerce_page_title', array( $this, 'change_title_of_woocommerce_page' ), 10, 1 );

		if ( $this->is_shoptimizer_theme() ) {
			remove_action( 'shoptimizer_before_content', 'shoptimizer_product_cat_banner', 15 );

			add_action( 'shoptimizer_before_content', array( $this, 'custom_shoptimizer_product_cat_banner' ), 15 );
		}
	}

	// if actual theme is shoptimizer or child theme of shoptimizer
	public function is_shoptimizer_theme(): bool {
		return wp_get_theme()->get( 'Name' ) == 'Shoptimizer' || wp_get_theme()->get( 'Template' ) == 'shoptimizer';
	}

	function custom_shoptimizer_product_cat_banner() {
		// if is tax and not category page and not tag page
		if ( is_tax() && ! is_product_category() && ! is_product_tag() ) {

			$shoptimizer_layout_woocommerce_category_position = shoptimizer_get_option( 'shoptimizer_layout_woocommerce_category_position' );

			if ( 'below-header' === $shoptimizer_layout_woocommerce_category_position ) {

				$term = get_queried_object();

				if ( shoptimizer_is_acf_activated() ) {
					$categorybanner = get_field( 'category_banner', $term );
				}

				// Remove the default actions as in the original function
				remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
				remove_action( 'woocommerce_archive_description', 'shoptimizer_woocommerce_taxonomy_archive_description' );
				remove_action( 'woocommerce_archive_description', 'shoptimizer_category_image', 20 );
				remove_action( 'woocommerce_before_main_content', 'shoptimizer_archives_title', 20 );

				?>

				<?php if ( ! empty( $categorybanner ) ) : ?>
					<style>
						.shoptimizer-category-banner,
						.shoptimizer-category-banner.visible {
							background-image: url('<?php echo shoptimizer_safe_html( $categorybanner ); ?>');
						}
					</style>
				<?php endif; ?>

				<?php if ( ! empty( $categorybanner ) ) { ?>
					<div class="shoptimizer-category-banner lazy-background">
					<?php } else { ?>
						<div class="shoptimizer-category-banner">
						<?php } ?>
						<div class="col-full">
							<h1><?php echo apply_filters( 'woocommerce_page_title', single_cat_title( '', false ) ); ?></h1>
							<?php the_archive_description( '<div class="taxonomy-description">', '</div>' ); ?>
						</div>
						</div>
				<?php
			}
		}
	}


	/**
	 * change thumbnail of variable product to match with the selected attribute
	 *
	 * @param $post WP_Post the post object
	 * @return WP_Post|void | string
	 */
	public function set_variable_product_thumbnail( $image_id, $product ) {
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
					if ( $attribute_name == $taxonomy && $attribute_value == $slug ) {
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
	 * @param $title string title of woocommerce page
	 * @return string|void
	 */
	public function change_title_of_woocommerce_page( string $title ) {
		if ( ! is_tax() ) {
			return;
		}

		$query      = get_queried_object();
		$title      = get_term_meta( $query->term_id, 'new_attr_title', true ) ?: $title;
		$attr_value = htmlspecialchars_decode( get_term_meta( $query->term_id, 'attr_value', true ) ) ?: null;

		$prefix_suffixe = get_term_meta( $query->term_id, 'prefix_suffixe', true ) ?: null;
		if ( $attr_value ) {
			if ( $prefix_suffixe == 'prefix' ) {
				$title = ucfirst( $attr_value ) . ' ' . $title;
			} else {
				$title = ucfirst( $title ) . ' ' . $attr_value;
			}
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
			echo "<div class='below-woocommerce-category'>" . $below_tag_formatted_content . '</div>';
		}
		if ( is_tax() ) {
			$term = get_queried_object();
			if ( $term->taxonomy != 'product_tag' && $term->taxonomy != 'product_cat' ) {
				$below_attr_content   = get_term_meta( $term->term_id, 'below_attr_content', true );
				$decoded_attr_content = htmlspecialchars_decode( $below_attr_content );
				$formatted_content    = wpautop( nl2br( $decoded_attr_content ) );
				echo "<div class='below-woocommerce-category'>" . $formatted_content . '</div>';
			}
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style( $this->IBC, plugin_dir_url( __FILE__ ) . 'css/ibc-public.css', array(), $this->version, 'all' );
	}
}
