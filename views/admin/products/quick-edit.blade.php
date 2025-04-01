<div class="modal fade" id="quickEditProductStockModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="" id="quickEditProductStockForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập Tồn Kho</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    {!! Admin::loading() !!}
                    <div class="quickEditProductStockBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-blue">Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>
<style>
    .quickEditProductStockBody .table { border:1px solid #ccc!important; }
</style>
<script id="quick_edit_product_branch_template" type="text/x-custom-template">
    <h3 style="font-size: 16px;" class="mb-3">${name}</h3>
    <table class="table">
        <thead>
        <tr>
            <th>Phân loại hàng hóa</th>
            <th>Tồn kho</th>
        </tr>
        </thead>
        <tbody>${inventories}</tbody>
    </table>
</script>
<script id="quick_edit_product_stock_template" type="text/x-custom-template">
    <tr class="">
        <td style="width: 260px;">
            <p class="fw-bold mb-0">${product_name}</p>
            <p class="color-green">${optionName}</p>
        </td>
        <td>
            <input type="number" value="${stock}" class="form-control" name="productStock[${id}]">
        </td>
    </tr>
</script>
<script>
    $(function () {
	    let quickEditStockModal = $('#quickEditProductStockModal');

	    let quickEditStockModalHandel = new bootstrap.Modal('#quickEditProductStockModal', {backdrop: "static", keyboard: false})

	    let productId = 0;

        $(document).on('click', '.js_product_quick_edit_stock', function () {

            productId = $(this).data('id');

            quickEditStockModal.find('.quickEditProductStockBody').html('');

	        quickEditStockModal.find('.loading').show();

	        quickEditStockModalHandel.show();

	        let data = {
		        productId : productId,
		        action : 'Stock_Manager_Ajax::quickEditLoad'
	        }

	        request.post(ajax, data).then(function (response) {

		        if(response.status === 'success') {

                    let inventoriesHtml = ''

                    if(Object.keys(response.data.inventories).length !== 0) {

                        for (let [index, inventory] of Object.entries(response.data.inventories)) {

                            inventoriesHtml += $('#quick_edit_product_stock_template').html().split(/\$\{(.+?)}/g).map(render(inventory)).join('');
                        }
                    }

                    let tableHtml = $('#quick_edit_product_branch_template').html().split(/\$\{(.+?)}/g).map(render({
                        name : response.data.branch.name,
                        inventories : inventoriesHtml
                    })).join('');

			        quickEditStockModal.find('.loading').hide();

			        quickEditStockModal.find('.quickEditProductStockBody').html(tableHtml);
		        }
		        else {
			        SkilldoMessage.response(response);
		        }
	        });

	        return false;
        });

        $(document).on('submit', '#quickEditProductStockForm', function () {

            let data = $(this).serializeJSON();

            data.action = 'Stock_Manager_Ajax::quickEditSave';

            request.post(ajax, data).then(function(response) {

                SkilldoMessage.response(response);

                if(response.status === 'success') {

	                let column = $('.js_column.tr_' + response.data.productId + ' .column-stock span.badge')

	                column.html(response.data.label);

	                column.removeClass(function (index, className) {
		                return (className.match (/(^|\s)text-bg-\S+/g) || []).join(' ');
	                });

	                column.addClass(response.data.color);

	                quickEditStockModalHandel.hide()
                }
            });

            return false;
        });
    })
</script>