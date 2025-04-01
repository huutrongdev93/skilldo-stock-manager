<div id="js_order_return_print_content" class="printBox"></div>
<style>
    .printBox {
        font-family: Arial, sans-serif;
        font-size: 12px;
        display: none;
    }
    @media print {
        body * {
            display: none;
        }
        /* Chỉ hiện phần cần in */
        body .wrapper,
        body .wrapper .page-content,
        body .wrapper .page-content .page-body,
        body .wrapper .page-content .page-body .ui-layout
        {
            padding: 0!important;
            margin: 0!important;
        }
        body .wrapper,
        body .wrapper .page-content,
        body .wrapper .page-content .page-body,
        body .wrapper .page-content .page-body .ui-layout,
        body .printBox {
            display: block!important;
        }
        body .printBox * {
            display: block!important;
        }
        body .printBox table {
            display: table!important;
            width: 100%;
        }
        body .printBox table tbody,
        body .printBox table tr,
        body .printBox table td {
            display: revert!important;
        }
        body .printBox span {
            display: inline!important;
        }
        .fs18 {
            font-size: 18px;
        }

        .mb20 {
            margin-bottom: 20px;
        }

        .mb10 {
            margin-bottom: 10px;
        }

        .mb5 {
            margin-bottom: 5px;
        }
        table {
            page-break-inside: auto;
            width: 100%;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table.table-item {
            border: 1px solid #000;
        }
        table.table-item tr td {
            border: 1px solid #000;
        }
    }
</style>
<script id="order_return_print_template" type="text/x-custom-template">
    <div class="mb10" style="text-align:center;"><strong class="fs18">PHIẾU TRẢ HÀNG</strong></div>
    <div class="mb5" style="text-align:center;"><strong>Mã phiếu: ${code}</strong></div>
    <div class="mb5" style="text-align:center;"><strong>Mã đơn hàng: ${order_code}</strong></div>
    <div class="mb20" style="text-align:center;"><em>Ngày chuyển: ${created}</em></div>
    <table width="100%" class="mb10">
        <tbody>
            <tr>
                <td>Chi nhánh: ${branch_name}</td>
            </tr>
            <tr>
                <td>Người trả: ${user_name}</td>
            </tr>
            <tr>
                <td>Khách hàng: ${customer_name}</td>
            </tr>
            <tr>
                <td>Người nhận: ${to_user_name}</td>
            </tr>
        </tbody>
    </table>
    <table border="1" cellpadding="3" style="border-collapse:collapse;width:100%;" class="mb10 table-item">
        <tbody>
            <tr style="text-align:center;font-weight:bold;">
                <td style="text-align:right;">STT</td>
                <td>Mã hàng</td>
                <td>Tên hàng</td>
                <td style="text-align:right;">Giá bán</td>
                <td style="text-align:right;">SL trả</td>
                <td style="text-align:right;">Giá trả</td>
                <td style="text-align:right;">Thành tiền</td>
            </tr>
            ${details}
        </tbody>
    </table>

    <table border="0" cellpadding="3" style="border-collapse:collapse;width:100%;" class="mb20">
        <tbody>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Tổng trả (${total_quantity})</td>
            <td style="text-align:right;">${total_return}</td>
        </tr>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Giảm giá</td>
            <td style="text-align:right;">${discount}</td>
        </tr>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Phí trả hàng</td>
            <td style="text-align:right;">${surcharge}</td>
        </tr>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Trả cho khách</td>
            <td style="text-align:right;">${total_payment}</td>
        </tr>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Đã trả cho khách</td>
            <td style="text-align:right;">${total_paid}</td>
        </tr>
        <tr>
            <td colspan="3">Ghi chú: ${note}</td>
        </tr>
        </tbody>
    </table>
</script>
<script id="order_return_item_print_template" type="text/x-custom-template">
    <tr>
        <td style="text-align:right;">${stt}</td>
        <td>${product_code}</td>
        <td>${product_name} ${product_attribute}</td>
        <td style="text-align:right;">${price_sell}</td>
        <td style="text-align:right;">${quantity}</td>
        <td style="text-align:right;">${price}</td>
        <td style="text-align:right;">${sub_total}</td>
    </tr>
</script>