<div id="js_order_detail_history_tab_return" style="display: none">
    {!! Admin::loading() !!}
    <table class="display table table-striped media-table ">
        <thead>
        <tr>
            <th class="">Mã trả hàng</th>
            <th class="">Thời gian</th>
            <th class="">Người nhận trả</th>
            <th class="">Tổng cộng</th>
            <th class="">Trạng thái</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>

    <script id="js_order_detail_history_return_template" type="text/x-custom-template">
        <tr class="js_column" data-id="${id}">
            <td class=""><span>${code}</span></td>
            <td class=""><span>${created}</span></td>
            <td class=""><span>${user_name}</span></td>
            <td class=""><span>${total_payment}</span></td>
            <td class=""><span>${status}</span></td>
        </tr>
    </script>
</div>