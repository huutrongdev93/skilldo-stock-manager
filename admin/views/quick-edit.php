<div class="modal fade" id="quickEditProductStockModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" id="quickEditProductStockForm">
                <div class="modal-header">
                    <h5 class="modal-title">Cập Tồn Kho</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="m-1">
                        <input type="hidden" value="" class="form-control" name="id">
                        <div class="quickEditProductStockBody"></div>
                    </div>
                    <div class="m-1 text-end">
                        <button type="button" class="btn btn-white" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-blue">Cập nhật</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    table.table { border:1px solid #ccc; }
</style>
<script>
    $(function () {
        let quickEditStockModal = $('#quickEditProductStockModal'), inventories, productId;

        $(document).on('click', '.js_product_quick_edit_stock', function () {
            inventories = $(this).data('inventory');
            productId = $(this).data('id');
            quickEditStockModal.find('input[name="id"]').val(productId);
            quickEditStockModal.find('.quickEditProductStockBody').html('');
            for (const [key, items_tmp] of Object.entries(inventories)) {
                let items = [items_tmp];
                quickEditStockModal.find('.quickEditProductStockBody').append(items.map(function(item) {
                    return $('#quick_edit_product_branch_template').html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                }));
            }
            for (const [key, items_tmp] of Object.entries(inventories)) {
                for (const [key, inventory] of Object.entries(items_tmp.inventory)) {
                    let items = [inventory];
                    quickEditStockModal.find('.quickEditProductStockBody_'+items_tmp.id).append(items.map(function(item) {
                        return $('#quick_edit_product_stock_template').html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                    }));
                }
            }
            quickEditStockModal.modal('show');
        });

        $(document).on('submit', '#quickEditProductStockForm', function () {

            let data = $(this).serializeJSON();

            data.action = 'Stock_Manager_Ajax::quickEditSave';

            $.post(ajax, data, function() {}, 'json').done(function(response) {

                show_message(response.message, response.status);

                if(response.status === 'success') {

                    quickEditStockModal.modal('hide');

                    let dataTotal = {};

                    for (const [branchId, productStock] of Object.entries(data.productStock)) {
                        for (const [productId, stockItem] of Object.entries(productStock)) {
                            if(typeof dataTotal[productId] == 'undefined') dataTotal[productId] = 0;
                            stockItem.stock = parseInt(stockItem.stock);
                            inventories[branchId].inventory[productId].stock = stockItem.stock;
                            dataTotal[productId] += stockItem.stock;
                        }
                    }

                    $.each(dataTotal, function (index, value) {
                        $('.product_stock_'+index).html(value);
                    });

                    $('#tr_' + productId + ' .column-stock .js_product_quick_edit_stock').attr('inventory', JSON.stringify(inventories));
                }
            });

            return false;
        });
    })
</script>
<script id="quick_edit_product_branch_template" type="text/x-custom-template">
    <h3 style="font-size: 16px;">Tồn kho ${name}</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Phân loại hàng hóa</th>
                <th>Tồn kho</th>
            </tr>
        </thead>
        <tbody class="quickEditProductStockBody_${id}"></tbody>
    </table>
</script>
<script id="quick_edit_product_stock_template" type="text/x-custom-template">
    <tr class="">
        <td style="width: 200px;">
            <p style="font-weight: bold; margin-bottom: 2px;">${optionName}</p>
        </td>
        <td>
            <input type="number" value="${stock}" class="form-control" name="productStock[${branch_id}][${id}][stock]">
        </td>
    </tr>
</script>