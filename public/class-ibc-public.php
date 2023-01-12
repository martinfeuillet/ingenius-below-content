<?php

class IBC_Public {
    private $IBC;

    private $version;

    public function __construct($IBC, $version) {
        $this->IBC     = $IBC;
        $this->version = $version;

        // add_action('woocommerce_after_single_product_summary', array($this, 'add_below_content'), 11);
        add_action('woocommerce_after_main_content', array($this, 'add_below_content'), 11);
        add_filter( 'woocommerce_page_title', array($this, 'change_title_of_woocommerce_page'), 10, 1 );

        // add_action( 'pre_get_posts', array($this, 'display_custom_product_variation_image'));
        add_action('the_post', array($this, 'set_variableX_product_thumbnail'));
    }

    public function display_custom_product_variation_image($query) {
        if (is_admin() || ! $query->is_main_query()) {
            return;
        }
        $queried_object = get_queried_object();
        $query->set('post_type', 'product');
        $query->set('post_status', 'publish');
        $query->set('posts_per_page', -1);
        if (is_tax()) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => $queried_object->taxonomy,
                    'field'    => 'slug',
                    'terms'    => $queried_object->slug,
                ),
            ));
        }
        // change post thumbnail of product

        return $query;
    }

    public function set_variableX_product_thumbnail($post) {
        $queried_object = get_queried_object();
        if ( is_admin() && ! is_tax() && ! $queried_object->term_taxonomy_id) {
            return;
        }
        $product = wc_get_product($post->ID);
        if ($product) {
            $current_products = $product->get_children();
            foreach ($current_products as $id) {
                $product    = new WC_Product_Variation($id);
                // get all attribute values
                $product_attributes = $product->get_attributes();
                foreach ($product_attributes as $name => $value) {
                    if ($name == $queried_object->taxonomy && $value == $queried_object->name) {
                        $image_id = $product->get_image_id();
                        if ($image_id) {
                            set_post_thumbnail( $post->ID, $image_id );
                        }
                    }
                }
            }
        }
    }

    public function set_variable_product_thumbnail($post_id) {
        // Get the product variation ID
        $variation_id = get_post_meta( $post_id, '_default_variation_id', true );
        // Get the product variation image
        $image = wp_get_attachment_image( $variation_id, 'thumbnail' );

        // Set the product variation image as the post thumbnail
        set_post_thumbnail( $post_id, $image );
    }

    public function change_title_of_woocommerce_page($title) {
        $query = get_queried_object();
        if (is_tax()) {
            $attr_value     = htmlspecialchars_decode(get_term_meta($query->term_id, 'attr_value', true));
            $prefix_suffixe = get_term_meta($query->term_id, 'prefix_suffixe', true);
            if ($prefix_suffixe == 'prefix') {
                $title = ucfirst($attr_value) . ' ' . $title;
            } else {
                $title = ucfirst($title) . ' ' . $attr_value;
            }
            return $title;
        }
    }

    public function add_below_content() {
        if (is_product_tag()) {
            $term              = get_queried_object();
            $below_tag_content = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_tag_content', true));
            echo "<div class='below-tag-content'>" . $below_tag_content . '</div>';
        }
        // if we are on attribute archive
        if (is_tax()) {
            $term               = get_queried_object();
            $below_attr_content = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_attr_content', true));
            echo "<div class='below-attr-content'>" . $below_attr_content . '</div>';
        }
    }

    // enqueue scripts and styles
    public function enqueue_styles() {
        wp_enqueue_style($this->IBC, plugin_dir_url(__FILE__) . '/css/ibc-public.css', array(), $this->version, 'all');
    }
}
