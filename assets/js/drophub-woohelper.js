jQuery(document).ready(function($) {
    // Handle shipping method selection from custom dropdown
    $(document).on('change', '.shipping-method-dropdown', function() {
        let selectedMethod = $(this).val();

        // Find the corresponding WooCommerce shipping method radio button and click it
        $('input[name^="shipping_method"]').each(function() {
            if ($(this).val() === selectedMethod) {
                $(this).prop('checked', true).trigger('change');
            }
        });
    });

    function hideHiddenShippingCost() {
        $('tr.fee td .hidden-shipping-cost').closest('tr').hide();
    }

    // Initial hide on document ready
    hideHiddenShippingCost();

    // Hide fee row with data-title "Hidden Shipping Cost" whenever the cart is updated
    $(document.body).on('updated_cart_totals updated_checkout', function() {
        hideHiddenShippingCost();
    });

    // Hide fee row with data-title "Hidden Shipping Cost"
    $('tr.fee .hidden-shipping-cost').closest('tr').hide();
});
