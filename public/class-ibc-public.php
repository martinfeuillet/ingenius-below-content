<?php

class IBC_Public {
    private $IBC;

    private $version;

    public function __construct($IBC, $version) {
        $this->IBC     = $IBC;
        $this->version = $version;

        add_action('woocommerce_after_main_content', array($this, 'add_below_content'), 11);
        add_action('the_post', array($this, 'set_variable_product_thumbnail'));

        add_filter('woocommerce_page_title', array($this, 'change_title_of_woocommerce_page'), 10, 1);
    }

    // change thumbnail of variable product to match with the selected attribute
    public function set_variable_product_thumbnail($post) {
        $queried_object = get_queried_object();
        if (is_admin() && ! is_tax() && (isset($queried_object->taxonomy) && ! substr($queried_object->taxonomy, 0, 3) == 'pa_')) {
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
                    if ($queried_object->taxonomy && $name == $queried_object->taxonomy && $value == $queried_object->slug) {
                        $image_id = $product->get_image_id();
                        if ($image_id) {
                            set_post_thumbnail($post->ID, $image_id);
                        }
                    }
                }
            }
        }
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

    // add below content on tag and attribute archive
    public function add_below_content() {
        if (is_product_tag()) {
            $term              = get_queried_object();
            $below_tag_content = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_tag_content', true));
            echo "<div class='below-woocommerce-category'>" . $below_tag_content . '</div>';
        }
        // if we are on attribute archive
        $term               = get_queried_object();
        if (is_tax() && $term->taxonomy != 'product_tag' && $term->taxonomy != 'product_cat') {
            $below_attr_content = htmlspecialchars_decode(get_term_meta($term->term_id, 'below_attr_content', true));
            echo "<div class='below-woocommerce-category'>" . $below_attr_content . '</div>';
        }
    }

    // enqueue scripts and styles
    public function enqueue_styles() {
        wp_enqueue_style($this->IBC, plugin_dir_url(__FILE__) . '/css/ibc-public.css', array(), $this->version, 'all');
    }
}
