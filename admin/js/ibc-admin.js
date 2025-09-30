(function ($) {
    'use strict';

    $(document).ready(function () {
        // Handle attribute radio button change
        $('.ibc-attribute-radio').on('change', function () {
            var $radio = $(this);
            var attributeName = $radio.data('attribute');

            // Hide all terms containers first
            $('.ibc-terms-container').hide();

            // If an attribute is selected (not the "None" option)
            if (attributeName && attributeName !== '') {
                var $termsContainer = $('#terms-' + attributeName);
                $termsContainer.show();

                // Only load terms if container is empty
                if ($termsContainer.find('.ibc-terms-list').length === 0) {
                    loadAttributeTerms(attributeName, $termsContainer);
                }
            }
        });

        /**
         * Load attribute terms via AJAX
         */
        function loadAttributeTerms(attributeName, $container) {
            $container.html('<p>Loading terms...</p>');

            $.ajax({
                url: ibc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_attribute_terms',
                    attribute: attributeName,
                    nonce: ibc_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $container.html(response.data);
                    } else {
                        $container.html('<p>Error loading terms.</p>');
                    }
                },
                error: function () {
                    $container.html('<p>Error loading terms.</p>');
                }
            });
        }
    });

})(jQuery);
