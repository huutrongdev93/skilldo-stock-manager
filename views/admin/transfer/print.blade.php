<div id="js_transfer_print_content" class="printBox"></div>
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
<script id="transfer_print_template" type="text/x-custom-template">
    <div class="mb10" style="text-align:center;"><strong class="fs18">PHIẾU CHUYỂN HÀNG</strong></div>
    <div class="mb5" style="text-align:center;"><strong>Mã phiếu: ${code}</strong></div>
    <div class="mb20" style="text-align:center;"><em>Ngày chuyển: ${send_date}</em></div>
    <table width="100%" class="mb10">
        <tbody>
            <tr>
                <td>Chi nhánh chuyển: ${from_branch_name}</td>
            </tr>
            <tr>
                <td>Người chuyển: ${from_user_name}</td>
            </tr>
            <tr>
                <td>Chi nhánh nhận: ${to_branch_name}</td>
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
                <td style="text-align:right;">SL chuyển</td>
                <td style="text-align:right;">SL nhận</td>
                <td style="text-align:right;">Giá chuyển</td>
            </tr>
            ${details}
        </tbody>
    </table>

    <table border="0" cellpadding="3" style="border-collapse:collapse;width:100%;" class="mb20">
        <tbody>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Tổng chuyển (${total_send_quantity})</td>
            <td style="text-align:right;">${total_send_price}</td>
        </tr>
        <tr>
            <td style="width:33%;"></td>
            <td style="width:33%;">Tổng nhận (${total_receive_quantity})</td>
            <td style="text-align:right;">${total_receive_price}</td>
        </tr>
        <tr>
            <td colspan="3">Ghi chú: ${note}</td>
        </tr>
        </tbody>
    </table>

    <table width="100%">
        <tbody>
        <tr>
            <td align="center" width="50%"><strong>Người chuyển</strong></td>
            <td align="center" width="50%"><strong>Người nhận</strong></td>
        </tr>
        </tbody>
    </table>
</script>
<script id="transfer_item_print_template" type="text/x-custom-template">
    <tr>
        <td style="text-align:right;">${stt}</td>
        <td>${product_code}</td>
        <td>${product_name} ${product_attribute}</td>
        <td style="text-align:right;">${send_quantity}</td>
        <td style="text-align:right;">${receive_quantity}</td>
        <td style="text-align:right;">${receive_price}</td>
    </tr>
</script>