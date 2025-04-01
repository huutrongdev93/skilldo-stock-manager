<div class="modal fade" id="js_order_return_modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thông Tin Phiếu Trả Hàng</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="js_order_return_detail_tabs nav nav-tabs nav-tabs-horizontal mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info" aria-selected="true">Thông tin</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history" aria-selected="true">Lịch sử thanh toán</button>
                    </li>
                </ul>
                <div class="js_order_return_detail_tab_content" data-tab="info">
                    {!! Admin::loading() !!}
                    <div class="js_detail_content"></div>
                    <hr />
                    {!! $tableProduct->display() !!}
                    <div class="pagination mt-3"></div>
                </div>
                <div class="js_order_return_detail_tab_content" data-tab="history" style="display: none">
                    {!! Admin::loading() !!}
                    <table class="display table table-striped media-table ">
                        <thead>
                            <tr>
                                <th class="">Mã phiếu</th>
                                <th class="">Thời gian</th>
                                <th class="">Người tạo</th>
                                <th class="">Giá trị phiếu</th>
                                <th class="">Trạng thái</th>
                                <th class="">Tiền chi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
            </div>
        </div>
    </div>
</div>

<script id="order_return_detail_template" type="text/x-custom-template">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-5"><b class="text-2xl">${code}</b> ${status}</div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Chi nhánh:</div>
                <div class="col-md-6">${branch_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người tạo:</div>
                <div class="col-md-6">${user_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Khách hàng:</div>
                <div class="col-md-6">${customer_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Mã đơn hàng:</div>
                <div class="col-md-6"><a href="{!! Sicommerce_Cart::url('order').'/detail/' !!}${order_id}" target="_blank">${order_code}</a></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Tổng tiền hàng trả (${total_quantity}):</div>
                <div class="col-md-6">${total_return}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Giảm giá phiếu trả:</div>
                <div class="col-md-6">${discount}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Cần trả khách:</div>
                <div class="col-md-6">${total_payment}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Đã trả khách:</div>
                <div class="col-md-6">${total_paid}</div>
            </div>
        </div>
    </div>
</script>

<script id="js_order_return_detail_history_template" type="text/x-custom-template">
    <tr class="js_column" data-id="${id}">
        <td class=""><span>${code}</span></td>
        <td class=""><span>${created}</span></td>
        <td class=""><span>${partner_name}</span></td>
        <td class=""><span>${amount}</span></td>
        <td class=""><span>${status}</span></td>
        <td class=""><span>${amount}</span></td>
    </tr>
</script>