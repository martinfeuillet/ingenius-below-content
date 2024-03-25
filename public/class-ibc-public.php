<?php

class IBC_Public
{
    private string $IBC;

    private string $version;

    public function __construct( $IBC , $version ) {
        $this->IBC     = $IBC;
        $this->version = $version;

        //&& isset( get_queried_object()->taxonomy ) && str_starts_with( get_queried_object()->taxonomy , 'pa_' )
        add_action( 'woocommerce_after_main_content' , array($this , 'add_below_content') , 10 );
        add_filter( 'woocommerce_product_get_image_id' , array($this , 'set_variable_product_thumbnail') , 10 , 2 );
        add_filter( 'woocommerce_page_title' , array($this , 'change_title_of_woocommerce_page') , 10 , 1 );


    }

    /**
     * change thumbnail of variable product to match with the selected attribute
     * @param $post WP_Post the post object
     * @return WP_Post|void | string
     */
    public function set_variable_product_thumbnail( $image_id , $product ) {
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

        $query          = get_queried_object();
        $title          = get_term_meta( $query->term_id , 'new_attr_title' , true ) ?: $title;
        $attr_value     = htmlspecialchars_decode( get_term_meta( $query->term_id , 'attr_value' , true ) ) ?: null;
        $prefix_suffixe = get_term_meta( $query->term_id , 'prefix_suffixe' , true ) ?: null;
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
     * @return void
     */
    public function add_below_content() : void {
        if ( is_product_tag() ) {
            $term              = get_queried_object();
            $below_tag_content = htmlspecialchars_decode( get_term_meta( $term->term_id , 'below_tag_content' , true ) );
            echo "<div class='below-woocommerce-category'>" . $below_tag_content . '</div>';
        }
        // if we are on attribute archive
        if ( is_tax() ) {
            $term = get_queried_object();
            if ( $term->taxonomy != 'product_tag' && $term->taxonomy != 'product_cat' ) {
                $below_attr_content = htmlspecialchars_decode( get_term_meta( $term->term_id , 'below_attr_content' , true ) );
                echo "<div class='below-woocommerce-category'>" . $below_attr_content . '</div>';
            }
        }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() : void {
        wp_enqueue_style( $this->IBC , plugin_dir_url( __FILE__ ) . '/css/ibc-public.css' , array() , $this->version , 'all' );
    }
}
