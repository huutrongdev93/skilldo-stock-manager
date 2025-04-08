<div id="js_purchase_return_print_content" class="printBox"></div>
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
<script id="purchase_return_print_template" type="text/x-custom-template">
    <div class="mb10" style="text-align:center;"><strong class="fs18">PHIẾU TRẢ HÀNG NHẬP</strong></div>
    <div class="mb5" style="text-align:center;"><strong>Mã phiếu: ${code}</strong></div>
    <div class="mb20" style="text-align:center;"><em>Ngày: ${purchase_date}</em></div>
    <table width="100%" class="mb10">
        <tbody>
            <tr>
                <td>Chi nhánh: ${branch_name}</td>
            </tr>
            <tr>
                <td>Người tạo: ${user_created_name}</td>
            </tr>
            <tr>
                <td>Nhà cung cấp: ${supplier_name}</td>
            </tr>
            <tr>
                <td>Địa chỉ:</td>
            </tr>
        </tbody>
    </table>
    <table border="1" cellpadding="3" style="border-collapse:collapse;width:100%;" class="mb10 table-item">
        <tbody>
            <tr style="text-align:center;font-weight:bold;">
                <td style="text-align:right;">STT</td>
                <td>Mã hàng</td>
                <td>Tên hàng</td>
                <td style="text-align:right;">Số lượng</td>
                <td style="text-align:right;">Giá nhập</td>
                <td style="text-align:right;">Giá trả lại</td>
                <td style="text-align:right;">Thành tiền</td>
            </tr>
            ${details}
        </tbody>
    </table>
    <table border="0" cellpadding="3" style="border-collapse:collapse;width:100%;" class="mb20">
        <tbody>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Tổng số lượng:</td>
            <td style="text-align:right;">${total_quantity}</td>
        </tr>
        <tr>
            <td></td>
            <td>Tổng tiền hàng:</td>
            <td style="text-align:right;">${subtotal}</td>
        </tr>
        <tr>
            <td></td>
            <td>Giảm giá:</td>
            <td style="text-align:right;">${return_discount}</td>
        </tr>
        <tr>
            <td></td>
            <td>Tiền NCC trả:</td>
            <td style="text-align:right;">${total_payment}</td>
        </tr>
        <tr>
            <td colspan="3">Ghi chú: ${note}</td>
        </tr>
        </tbody>
    </table>
    <table width="100%">
        <tbody>
        <tr>
            <td align="center" width="50%"><strong>Nhà cung cấp</strong></td>
            <td align="center" width="50%"><strong>Người lập</strong></td>
        </tr>
        </tbody>
    </table>
</script>
<script id="purchase_return_item_print_template" type="text/x-custom-template">
    <tr>
        <td style="text-align:right;">${stt}</td>
        <td>${product_code}</td>
        <td>${product_name} ${product_attribute}</td>
        <td style="text-align:right;">${quantity}</td>
        <td style="text-align:right;">${cost}</td>
        <td style="text-align:right;">${price}</td>
        <td style="text-align:right;">${subtotal}</td>
    </tr>
</script>