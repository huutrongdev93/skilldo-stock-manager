<div class="modal fade" id="js_supplier_debt_modal_payment" tabindex="-1" role="dialog" aria-hidden="true">
    <form class="modal-dialog modal-lg js_supplier_debt_form_payment" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thanh toán</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row mb-3">
                            <div class="col-md-6"><b>Nợ hiện tại</b></div>
                            <div class="col-md-6"><span class="js_supplier_debt_total"></span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6"><b>Trả cho NCC:</b></div>
                            <div class="col-md-6">
                                <input type="text" min="0" name="supplier_payment" value="0" class="form-control js_input_supplier_total_payment" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><b>Nợ sau:</b></div>
                            <div class="col-md-6">
                                <div class="col-md-6"><span class="js_supplier_debt_balance"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
                </div>

                <table class="display table table-striped media-table mt-4">
                    <thead>
                        <tr>
                            <th class="manage-column">Mã phiếu nhập</th>
                            <th class="manage-column">Thời gian</th>
                            <th class="manage-column">Giá trị phiếu nhập</th>
                            <th class="manage-column">Đã trả trước</th>
                            <th class="manage-column">Còn cần trả</th>
                            <th class="manage-column">Tiền trả</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>


                <div class="row mt-4">
                    <div class="col-md-5"></div>
                    <div class="col-md-7">
                        <div class="row mb-3">
                            <div class="col-md-7"><b>Tổng thanh toán phiếu nhập:</b></div>
                            <div class="col-md-5"><span class="js_total_payment_purchase"></span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-7"><b>Cộng vào tài khoản nhà cung cấp:</b></div>
                            <div class="col-md-5"><span class="js_total_payment_supplier"></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!! Admin::button('green', ['text' => 'Tạo phiếu chi', 'icon' => Admin::icon('save'), 'type' => 'button', 'class' => 'js_supplier_debt_btn_submit']) !!}
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{ trans('button.close') }}</button>
            </div>
        </div>
    </form>
</div>

<script id="supplier_debt_purchase_order_template" type="text/x-custom-template">
    <tr>
        <td>${code}</td>
        <td>${created}</td>
        <td>${sub_total}</td>
        <td>${total_payment}</td>
        <td>${payment}</td>
        <td>
            <input type="text" min="0" name="payment[${id}]" value="0" class="form-control js_input_payment" data-id="${id}" />
        </td>
    </tr>
</script>