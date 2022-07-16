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
                    $('.product-detail-cart .product_add_to_cart').prop('disabled', true);
                    $('.product-detail-cart .product_add_to_cart_now').prop('disabled', true);
                }
                else {
                    $('.product-detail-cart .product_add_to_cart').prop('disabled', false);
                    $('.product-detail-cart .product_add_to_cart_now').prop('disabled', false);
                }
            }
        });

        $('.product-detail-cart input[name="product_id"]').trigger('change');

        let variations = $('#product_options_form__box').data('product-variations');

        if(typeof variations != 'undefined') {

            $('.product-detail-inventory-status').hide();

            let options = $('.option-type__swatch');

            options.each(function (){
                let id = $(this).data('id');
                let outStock = true;
                for (const [key, variation] of Object.entries(variations)) {
                    let hasItem = false;
                    for (const [keyItems, itemId] of Object.entries(variation.items)) {
                        if(itemId == id) {
                            hasItem = true;
                            break;
                        }
                    }
                    if(hasItem == true && variation.stock_status == 'instock') {
                        outStock = false;
                    }
                }
                if(outStock == true) {
                    $(this).addClass('option-stock__block');
                }
            });

            options.click(function (){

                let id = $(this).data('id');

                let group = $(this).data('group');

                $('.option-type__swatch').each(function () {
                    let groupId = $(this).data('group');
                    if(groupId != group) {
                        $(this).removeClass('option-stock__disabled');
                    }
                });

                for (const [key, variation] of Object.entries(variations)) {
                    let hasItem = false;
                    if(variation.stock_status == 'outstock') {
                        for (const [groupId, itemId] of Object.entries(variation.items)) {
                            if(itemId == id) {
                                hasItem = true; break;
                            }
                        }
                        if(hasItem == true) {
                            for (const [groupId, itemId] of Object.entries(variation.items)) {
                                if(group == groupId) continue;
                                $('.option-type__swatch[data-id="'+itemId+'"]').addClass('option-stock__disabled');
                            }
                        }

                    }

                }
            });
        }
    });
</script>
<style>
    .option-stock__block, .option-stock__disabled {
        cursor: not-allowed!important;
        pointer-events: none;
        opacity: .2;
    }
</style>