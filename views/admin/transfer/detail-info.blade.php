<div class="modal fade" id="js_transfer_modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thông Tin Phiếu Chuyển Hàng</h4>
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

<script id="transfer_detail_template" type="text/x-custom-template">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-5"><b class="text-2xl">${code}</b> ${status}</div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Chuyển từ:</div>
                <div class="col-md-6">${from_branch_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Chuyển đến:</div>
                <div class="col-md-6">${to_branch_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người chuyển:</div>
                <div class="col-md-6">${from_user_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người nhận:</div>
                <div class="col-md-6">${to_user_name}</div>
            </div>
        </div>
        <div class="col-md-6">

            <div class="row mb-2">
                <div class="col-md-6">Tổng SL chuyển:</div>
                <div class="col-md-6">${total_send_quantity}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng giá trị chuyển:</div>
                <div class="col-md-6">${total_send_price}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng SL nhận:</div>
                <div class="col-md-6">${total_receive_quantity}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng giá trị nhận:</div>
                <div class="col-md-6">${total_receive_price}</div>
            </div>
        </div>
    </div>
</script>