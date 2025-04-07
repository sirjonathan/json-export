/**
 * Admin Area JavaScript for JSON Export Plugin.
 *
 * Handles toggling visibility of conditional options.
 *
 * @package JSONExport
 * @since   1.0.0
 */

jQuery(document).ready(
    function ($) {
        // Toggle category selection visibility based on radio button.
        $('input[name="content_selection"]').change(
            function () {
                if ($(this).val() === 'category') {
                    $('#category-selection').slideDown('fast');
                } else {
                    $('#category-selection').slideUp('fast');
                }
            }
        );

        // Trigger change on page load to set initial visibility state for category.
        $('input[name="content_selection"]:checked').trigger('change');

    }
);
