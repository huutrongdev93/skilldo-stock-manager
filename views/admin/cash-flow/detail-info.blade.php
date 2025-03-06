<div class="modal fade" id="js_cash_flow_modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thông Tin Phiếu</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! Admin::loading() !!}
                <div class="js_detail_content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
            </div>
        </div>
    </div>
</div>

<script id="cash_flow_detail_template" type="text/x-custom-template">
    <div class="row">
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Mã phiếu:</div>
                <div class="col-md-6"><b>${code}</b></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Thời gian:</div>
                <div class="col-md-6">${time}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Giá trị:</div>
                <div class="col-md-6">${amount}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người nhận:</div>
                <div class="col-md-6">${partner_code} - ${partner_name}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Số điện thoại:</div>
                <div class="col-md-6">${phone}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Địa chỉ:</div>
                <div class="col-md-6">${address}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row mb-2">
                <div class="col-md-6">Chi nhánh:</div>
                <div class="col-md-6">${branch_name}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">Loại thu chi:</div>
                <div class="col-md-6">${group_name}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">Trạng thái:</div>
                <div class="col-md-6">${status_label}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Người tạo:</div>
                <div class="col-md-6">${user_created}</div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">Đối tượng:</div>
                <div class="col-md-6">${partner_type_name}</div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-12"><p class="fst-italic">${target_note}</p></div>
    </div>

    ${target_table}
</script>

<script id="cash_flow_detail_table_template" type="text/x-custom-template">
    <table class="display table table-striped media-table mt-4">
        <thead>
        <tr>
            <th class="manage-column">Mã phiếu</th>
            <th class="manage-column">Thời gian</th>
            <th class="manage-column">Giá trị phiếu</th>
            <th class="manage-column">Đã trả trước</th>
            <th class="manage-column">Tiền chi</th>
        </tr>
        </thead>
        <tbody>${items}</tbody>
    </table>
</script>

<script id="cash_flow_detail_table_item_template" type="text/x-custom-template">
    <tr>
        <td>${target_code}</td>
        <td>${created}</td>
        <td>${need_pay_value}</td>
        <td>${paid_value}</td>
        <td>${amount}</td>
    </tr>
</script>