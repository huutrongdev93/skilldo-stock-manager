{!!
Admin::partial('components/page-default/page-index', [
    'name'      => 'Nhà cung cấp',
    'table'     => $table,
]);
!!}

<div class="modal fade" id="js_supplier_modal_status" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thay đổi trạng thái nhà cung cấp</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! form()->select2('supplierStatus', \Skdepot\Status\Supplier::options()->pluck('label', 'value')->toArray(), [
                    'label' => 'Trạng thái'
                ])->html() !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
                <button type="button" class="btn btn-blue js_supplier_btn_status_save">{{trans('button.save')}}</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        const handler = new SuppliersIndex()
        handler.events();
    })
</script>