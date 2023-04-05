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
        add_action( 'the_post' , array($this , 'set_variable_product_thumbnail') );
        add_filter( 'woocommerce_page_title' , array($this , 'change_title_of_woocommerce_page') , 10 , 1 );

    }

    /**
     * change thumbnail of variable product to match with the selected attribute
     * @param $post WP_Post the post object
     * @return WP_Post|void
     */
    public function set_variable_product_thumbnail( WP_Post $post ) {
        if ( ! is_tax() ) {
            return;
        }
        $product = wc_get_product( $post->ID );
        if ( $product ) {
            $current_products = $product->get_children();
            foreach ( $current_products as $id ) {
                $product = new WC_Product_Variation( $id );
                // get all attribute values
                $product_attributes = $product->get_attributes();
                foreach ( $product_attributes as $name => $value ) {
                    if ( get_queried_object()->taxonomy && $name == get_queried_object()->taxonomy && $value == get_queried_object()->slug ) {
                        $image_id = $product->get_image_id();
                        if ( $image_id ) {
                            set_post_thumbnail( $post->ID , $image_id );
                        }
                    }
                }
            }
        }
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
        $attr_value     = htmlspecialchars_decode( get_term_meta( $query->term_id , 'attr_value' , true ) );
        $prefix_suffixe = get_term_meta( $query->term_id , 'prefix_suffixe' , true );
        if ( $prefix_suffixe == 'prefix' ) {
            $title = ucfirst( $attr_value ) . ' ' . $title;
        } else {
            $title = ucfirst( $title ) . ' ' . $attr_value;
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
