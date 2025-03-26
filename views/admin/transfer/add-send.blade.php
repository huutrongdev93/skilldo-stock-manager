<div class="stock-page-form stock-take">
    <div class="stock-page-form-content">
        <div class="stock-page-header mb-3 gap-4">
            <div class="stock-page-header-title">
                <a href="{!! Url::route('admin.stock.transfers') !!}" class="fs-4 fw-bold pe-3"><i class="fa-light fa-arrow-left-long"></i></a>
                <span class="fs-3 fw-bold">Chuyển hàng</span>
            </div>
            {!! Plugin::partial(STOCK_NAME, 'admin/search') !!}
            <div class="stock-page-header-action"></div>
        </div>
        <div class="stock-page-products box" id="js_transfer_products">
            <div class="box-content p-0">
                <div class="stock-page-table js_transfer_table" style="display: none">
                    {!! $table->display() !!}
                </div>
                {!! Plugin::partial(STOCK_NAME, 'admin/import', [
                    'module' => 'transfer',
                    'examples' => 'MauFileChiTietChuyenHang'
                ]) !!}
            </div>
        </div>
    </div>
    <div class="stock-page-form-info">
        <form action="" id="js_transfer_form">
            <div class="box h-100">
                <div class="box-content">
                    <input type="hidden" value="{!! $action !!}" id="transfer_action">
                    @if(!empty($id))
                        <input type="hidden" name="transfer_id" value="{!! $id !!}" id="transfer_input_id">
                    @endif
                    {!! $form->html() !!}
                    <div class="stock-page-button gap-2">
                        {!! Admin::button('blue', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Lưu Tạm',
                            'id' => 'js_transfer_btn_draft'
                        ]) !!}

                        {!! Admin::button('green', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Hoàn thành',
                            'id' => 'js_transfer_btn_save'
                        ]) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="transfer_new_product_table_item_template" type="text/x-custom-template">
    <tr class="js_column stock-take-column-${id}" data-id="${id}">
        <td class="code column-code">
            <span>${code}</span>
        </td>
        <td class="product_name column-product_name">
            ${fullname}
        </td>
        <td class="stock column-stock">${stock}</td>
        <td class="quantity column-send-quantity">
            <input type="number" min="1" name="products[${id}][send_quantity]" value="${send_quantity}" class="form-control js_input_send_quantity" />
        </td>
        <td class="adjustment-quantity column-price">
            <span class="js_input_price">${price}</span>
        </td>
        <td class="adjustment-price column-send-price">
            <span class="js_input_send_price">${send_price}</span>
        </td>
        <td class="action column-action">
            <button class="btn btn-red js_transfer_btn_delete" data-id="${id}">{!! Admin::icon('delete') !!}</button>
        </td>
    </tr>
</script>

<script>
    $(function () {
        const handle = new TransferSendNewHandle()
        handle.events()
    })
</script>

<style>
    .ui-layout {
        max-width: 100%;
    }
</style>