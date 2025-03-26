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
                {!! \SkillDo\Form\Form::number('stock', [
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

        class InventoriesIndexHandle
        {
            constructor()
            {
                this.data = {
                    id: 0
                }
            }

            onClickPurchaseOrder(button)
            {
                let productId = button.attr('data-id');

                let data = {
                    product_id: productId,
                }

                SkilldoUtil.storage.set('stock_purchase_order_add_source', data)

                window.open(button.attr('href'),'_blank');
            }

            onClickPurchaseReturn(button)
            {
                let productId = button.attr('data-id');

                let data = {
                    product_id: productId,
                }

                SkilldoUtil.storage.set('stock_purchase_return_add_source', data)

                window.open(button.attr('href'),'_blank');
            }

            events()
            {
                let handle = this;

                $(document)
                    .on('click', '.js_inventory_btn_purchase_order', function () {
                        handle.onClickPurchaseOrder($(this))
                        return false
                    })
                    .on('click', '.js_inventory_btn_purchase_return', function () {
                        handle.onClickPurchaseReturn($(this))
                        return false
                    })
            }
        }

        const handler = new InventoriesIndexHandle()

        handler.events()
	})
</script>