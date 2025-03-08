<table class="display table table-striped media-table mt-4" id="js_purchase_order_detail_cash_flow">
    <thead>
    <tr>
        <th class="manage-column">Mã phiếu</th>
        <th class="manage-column">Thời gian</th>
        <th class="manage-column">Giá trị phiếu</th>
        <th class="manage-column">Đã trả trước</th>
        <th class="manage-column">Tiền chi</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<script id="purchase_order_detail_cash_flow_table_item_template" type="text/x-custom-template">
    <tr>
        <td><a href="#" class="js_btn_target" data-target="cash-flow" data-target-id="${id}">${code}</a></td>
        <td>${created}</td>
        <td>${need_pay_value}</td>
        <td>${paid_value}</td>
        <td>${amount}</td>
    </tr>
</script>