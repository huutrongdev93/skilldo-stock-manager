<div class="modal fade" id="js_stock_take_modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thông Tin Phiếu Kiểm Kho</h4>
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

<script id="stock_take_detail_template" type="text/x-custom-template">
    <div class="row">
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Mã trả kiểm kho:</div>
                <div class="col-md-6"><b>${code}</b></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Trạng thái:</div>
                <div class="col-md-6"><b>${status}</b></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Chi nhánh:</div>
                <div class="col-md-6">${branch_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người kiểm kho:</div>
                <div class="col-md-6">${user_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Ngày cân bằng:</div>
                <div class="col-md-6">${balance_date}</div>
            </div>
        </div>
        <div class="col-md-6">

            <div class="row mb-2">
                <div class="col-md-6">Tổng thực tế (${total_actual_quantity}):</div>
                <div class="col-md-6">${total_actual_price}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng lệch tăng (${total_increase_quantity}):</div>
                <div class="col-md-6">${total_increase_price}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng lệch giảm (${total_reduced_quantity}):</div>
                <div class="col-md-6">${total_reduced_price}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Tổng chênh lệch (${total_adjustment_quantity}):</div>
                <div class="col-md-6">${total_adjustment_price}</div>
            </div>
        </div>
    </div>
</script>