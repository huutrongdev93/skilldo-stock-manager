<script defer>
    $(function () {
        let inventory = <?php echo json_encode($stock_inventory_data);?>;
        let inventory_element = $('.product-detail-inventory-status .stock_inventory_status');
        $(document).on('change', '.product-detail-cart input[name="product_id"]', function () {
            let id = parseInt($(this).val());
            if(typeof inventory[id] != 'undefined') {
                if(inventory[id].status == null) {
                    return false;
                }
                inventory_element.removeClass('instock');
                inventory_element.removeClass('outstock');
                inventory_element.removeClass('onbackorder');
                inventory_element.addClass(inventory[id].status);
                inventory_element.text(inventory[id].label);

                if(inventory[id].status === 'outstock') {
                    $('.product-detail-cart .wcmc_add_to_cart').prop('disabled', true);
                    $('.product-detail-cart .wcmc_add_to_cart_now').prop('disabled', true);
                }
                else {
                    $('.product-detail-cart .wcmc_add_to_cart').prop('disabled', false);
                    $('.product-detail-cart .wcmc_add_to_cart_now').prop('disabled', false);
                }
            }
        });
        $('.product-detail-cart input[name="product_id"]').trigger('change');
    });
</script>