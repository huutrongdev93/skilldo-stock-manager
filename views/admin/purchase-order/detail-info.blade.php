<div class="modal fade" id="js_purchase_order_modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thông Tin Phiếu Nhập</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! Admin::loading() !!}
                <div class="js_detail_content"></div>
                <hr />
                {!! $tableProduct->display() !!}
                <div class="pagination mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
            </div>
        </div>
    </div>
</div>

<script id="purchase_order_detail_template" type="text/x-custom-template">
    <div class="row">
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Mã nhập hàng:</div>
                <div class="col-md-6"><b>${code}</b></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Trạng thái:</div>
                <div class="col-md-6"><b>${status}</b></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Thời gian:</div>
                <div class="col-md-6">${purchase_date}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Nhà cung cấp:</div>
                <div class="col-md-6">${supplier_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Chi nhánh:</div>
                <div class="col-md-6">${branch_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người nhập:</div>
                <div class="col-md-6">${purchase_name}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Tổng số lượng:</div>
                <div class="col-md-6">${total_quantity}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng tiền hàng:</div>
                <div class="col-md-6">${sub_total}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Giảm giá:</div>
                <div class="col-md-6">${discount}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Cần trả NCC:</div>
                <div class="col-md-6">${payment}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Đã trả NCC:</div>
                <div class="col-md-6">${total_payment}</div>
            </div>
        </div>
    </div>
</script>