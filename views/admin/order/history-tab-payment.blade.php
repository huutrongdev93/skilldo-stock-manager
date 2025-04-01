<div id="js_order_detail_history_tab_payment" style="display: none">
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

    <script id="js_order_detail_history_payment_template" type="text/x-custom-template">
        <tr class="js_column" data-id="${id}">
            <td class=""><span>${code}</span></td>
            <td class=""><span>${created}</span></td>
            <td class=""><span>${partner_name}</span></td>
            <td class=""><span>${amount}</span></td>
            <td class=""><span>${status}</span></td>
            <td class=""><span>${amount}</span></td>
        </tr>
    </script>
</div>