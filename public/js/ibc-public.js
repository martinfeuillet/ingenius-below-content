(function ($) {
    'use strict';

    /**
     * Auto-select variation based on category attribute parameters
     */
    $(document).ready(function () {
        // Add category params to product links if we're on a category page
        addCategoryParamsToLinks();

        // Check if we have category attribute data
        if (typeof ibc_variation_data !== 'undefined' && ibc_variation_data.attribute && ibc_variation_data.term) {
            // Wait for WooCommerce variation scripts to load
            setTimeout(function () {
                selectVariationFromCategory();
            }, 500);
        }
    });

    /**
     * Add category attribute parameters to product links on category pages
     */
    function addCategoryParamsToLinks() {
        // Check if we're on a category page and have category data
        if (typeof ibc_category_data !== 'undefined' && ibc_category_data.attribute && ibc_category_data.term) {
            $('a[href*="/product/"], .woocommerce-LoopProduct-link, .product-loop-link, .wc-block-grid__product-link').each(function () {
                var $link = $(this);
                var href = $link.attr('href');

                if (href && href.indexOf('/product/') !== -1) {
                    if (href.indexOf('ibc_attr=') === -1) {
                        var separator = href.indexOf('?') !== -1 ? '&' : '?';
                        var newHref = href + separator + 'ibc_attr=' + encodeURIComponent(ibc_category_data.attribute) + '&ibc_term=' + encodeURIComponent(ibc_category_data.term);
                        $link.attr('href', newHref);
                    }
                }
            });
        }
    }

    /**
     * Select the variation that matches the category attribute
     */
    function selectVariationFromCategory() {
        var attribute = ibc_variation_data.attribute;
        var term = ibc_variation_data.term;

        // Check for YITH WooCommerce Color swatches first
        var yithColorSwatch = $('.select_box.' + attribute + ' .select_option[data-value="' + term + '"]');
        if (yithColorSwatch.length > 0) {
            // Remove selected class from all options in this attribute group
            $('.select_box.' + attribute + ' .select_option').removeClass('selected');
            // Add selected class to the matching option
            yithColorSwatch.addClass('selected').trigger('click');
            return;
        }

        // Fallback to regular select dropdowns
        var selectElement = $('select[name="attribute_' + attribute + '"], select[data-attribute_name="attribute_' + attribute + '"]');

        if (selectElement.length > 0) {
            // Try to find and select the matching option
            var optionFound = false;

            selectElement.find('option').each(function () {
                var optionValue = $(this).val();
                if (optionValue === term) {
                    selectElement.val(term).trigger('change');
                    optionFound = true;
                    return false; // Break the loop
                }
            });

            // If no exact match, try with slug variations
            if (!optionFound) {
                selectElement.find('option').each(function () {
                    var optionValue = $(this).val();
                    var optionText = $(this).text().toLowerCase();

                    if (optionValue.includes(term) || optionText.includes(term.toLowerCase())) {
                        selectElement.val(optionValue).trigger('change');
                        return false; // Break the loop
                    }
                });
            }
        }

        // Also check for radio buttons (some themes use radio buttons for variations)
        var radioButton = $('input[name="attribute_' + attribute + '"][value="' + term + '"]');
        if (radioButton.length > 0) {
            radioButton.prop('checked', true).trigger('change');
        }

        // Check for other color swatch plugins (general approach)
        var colorSwatch = $('[data-attribute="' + attribute + '"][data-value="' + term + '"], [data-attr_name="attribute_' + attribute + '"][data-value="' + term + '"]');
        if (colorSwatch.length > 0) {
            colorSwatch.trigger('click');
        }

        // Trigger variation selection
        $('.variations_form').trigger('woocommerce_variation_select_change');
        $('.variations_form').trigger('check_variations');
    }

})(jQuery);
