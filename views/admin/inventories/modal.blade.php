<!-- Modal -->
<div class="modal fade" id="js_inventories_model_purchase_order" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Nhập kho</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! SkillDo\Form\Form::number('stock', [
                    'label' => 'Số lượng sản phẩm nhập thêm'
                ]) !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
                <button type="button" class="btn btn-blue js_inventories_btn_purchase_order">{{trans('button.save')}}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="js_inventories_model_purchase_return" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Xuất kho</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! SkillDo\Form\Form::number('stock', [
                    'label' => 'Số lượng sản phẩm xuất ra'
                ]) !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
                <button type="button" class="btn btn-blue js_inventories_btn_purchase_return">{{trans('button.save')}}</button>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-container_new .timeline-container_new--position .timeline-new__infomation .timeline-new__infomation__message {
        word-break: auto-phrase;
    }
</style>

<!-- Modal -->
<div class="modal fade" id="js_inventories_model_histories" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Lịch Sử Xuất Nhập Kho</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! Admin::loading() !!}
                <div class="row">
                    <div class="col-md-6">
                        <div class="box-header">
                            <h5 class="box-title">Kho</h5>
                        </div>
                        <div class="timeline-container_new">
                            <div class="timeline-new__wrapper__content--body history-content-stock"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="box-header">
                            <h5 class="box-title">Kho khách đặt</h5>
                        </div>
                        <div class="timeline-container_new">
                            <div class="timeline-new__wrapper__content--body history-content-reserved"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
            </div>
        </div>
    </div>
</div>

<script id="js_inventories_template_history" type="text/x-custom-template">
    <div class="timeline-container_new--position">
        <div class="timeline-event-contentnew__icon"></div>
        <div class="timeline-item-new--border--padding">
            <div class="timeline-new__infomation">
                <div><span class="timeline-new__infomation__name">${action}</span>
                    <span class="timeline-new__infomation__time">- ${created}</span></div>
                <div class="timeline-new__infomation__message"><span>${message}</span></div>
            </div>
        </div>
    </div>
</script>

<script defer>
	$(function () {

		let productId = 0;

		let inventoryId = 0;

		let modelPurchaseOrder = $('#js_inventories_model_purchase_order');

		let modelPurchaseOrderHandel = new bootstrap.Modal('#js_inventories_model_purchase_order', {backdrop: "static", keyboard: false})

		let modelPurchaseReturn = $('#js_inventories_model_purchase_return');

		let modelPurchaseReturnHandel = new bootstrap.Modal('#js_inventories_model_purchase_return', {backdrop: "static", keyboard: false})

		let modelHistories = $('#js_inventories_model_histories');

		let modelHistoriesHandel = new bootstrap.Modal('#js_inventories_model_histories', {backdrop: "static", keyboard: false})

		let InventoryHandler = function () {
			$(document)
				.on('click', '.js_btn_purchase_order', this.modelPurchaseOrder)
				.on('click', '.js_btn_purchase_return', this.modelPurchaseReturn)
				.on('click', '.js_inventories_btn_purchase_order', this.purchaseOrder)
				.on('click', '.js_inventories_btn_purchase_return', this.purchaseReturn)
				.on('click', '.js_btn_inventories_history', this.history)
		};

		InventoryHandler.prototype.modelPurchaseOrder = function (e) {

			productId = $(this).attr('data-product-id');

			inventoryId = $(this).attr('data-id');

			modelPurchaseOrderHandel.show();

			return false
		};

		InventoryHandler.prototype.modelPurchaseReturn = function (e) {

			productId = $(this).attr('data-product-id');

			inventoryId = $(this).attr('data-id');

			modelPurchaseReturnHandel.show();

			return false
		};

		InventoryHandler.prototype.purchaseOrder = function (e) {

			let branchId = $('select[name="branch"]').val();

			let loading = SkilldoUtil.buttonLoading($(this))

			let data = {
				action: 'Stock_Manager_Ajax::purchaseOrder',
				stock: modelPurchaseOrder.find('input[name="stock"]').val(),
				id: inventoryId,
				branchId: branchId
			}

			loading.start()

			request
                .post(ajax, data)
				.then(function (response) {

					SkilldoMessage.response(response);

					loading.stop();

					if(response.status === 'success') {

						let column = $('.tr_' + response.data.id);

						column.find('.column-stock span').html(response.data.stock);

						column.find('.column-status .badge').text(response.data.label);

						column.find('.column-status .badge').removeClass(function (index, className) {
							return (className.match (/(^|\s)text-bg-\S+/g) || []).join(' ');
						});

						column.find('.column-status .badge').addClass(response.data.color);

						modelPurchaseOrderHandel.hide();
					}
				})
				.catch(function (error) {
					loading.stop();
				});

			return false;
		}

		InventoryHandler.prototype.purchaseReturn = function (e) {

			let branchId = $('select[name="branch"]').val();

			let loading = SkilldoUtil.buttonLoading($(this))

			let data = {
				action: 'Stock_Manager_Ajax::purchaseReturn',
				stock: modelPurchaseReturn.find('input[name="stock"]').val(),
				id: inventoryId,
				branchId: branchId
			}

			loading.start()

			request
				.post(ajax, data)
				.then(function (response) {

					SkilldoMessage.response(response);

					loading.stop();

					if(response.status === 'success') {

						let column = $('.tr_' + response.data.id);

						column.find('.column-stock span').html(response.data.stock);

						column.find('.column-status .badge').text(response.data.label);

						column.find('.column-status .badge').removeClass(function (index, className) {
							return (className.match (/(^|\s)text-bg-\S+/g) || []).join(' ');
						});

						column.find('.column-status .badge').addClass(response.data.color);

						modelPurchaseReturnHandel.hide();
					}
				})
				.catch(function (error) {
					loading.stop();
				});

			return false;
		}

		InventoryHandler.prototype.history = function (e) {

			modelHistories.find('.loading').show();

			modelHistoriesHandel.show();

			let data = {
				id : $(this).data('id'),
				action : 'Stock_Manager_Ajax::inventoryHistory'
			}

			request.post(ajax, data).then(function (response) {

				if(response.status === 'success') {

					let historyStockHtml = ''

					if(Object.keys(response.data.stock).length !== 0) {
						for (let [index, history] of Object.entries(response.data.stock)) {
							historyStockHtml += [history].map(function(item) {
								if(item.action === 'cong') {
									item.action = '<span class="badge text-bg-green">Nhập kho</span>'
                                }
								if(item.action === 'tru') {
									item.action = '<span class="badge text-bg-red">Xuất kho</span>'
								}
								return $('#js_inventories_template_history').html().split(/\$\{(.+?)}/g).map(render(item)).join('');
							});
						}
					}

					let historyReservedHtml = ''

					if(Object.keys(response.data.reserved).length !== 0) {
						for (let [index, history] of Object.entries(response.data.reserved)) {
							historyReservedHtml += [history].map(function(item) {
								if(item.action === 'cong') {
									item.action = '<span class="badge text-bg-green">Nhập kho</span>'
								}
								if(item.action === 'tru') {
									item.action = '<span class="badge text-bg-red">Xuất kho</span>'
								}
								return $('#js_inventories_template_history').html().split(/\$\{(.+?)}/g).map(render(item)).join('');
							});
						}
					}

					modelHistories.find('.loading').hide();
					modelHistories.find('.history-content-stock').html(historyStockHtml);
					modelHistories.find('.history-content-reserved').html(historyReservedHtml);
				}
				else {
					SkilldoMessage.response(response);
				}
			});

			return false;
		};

		new InventoryHandler();
	})
</script>