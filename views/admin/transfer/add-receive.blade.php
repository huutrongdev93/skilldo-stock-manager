<div class="stock-page-form transfer-receive">
    <div class="stock-page-form-content">
        <div class="stock-page-header mb-3 gap-4">
            <div class="stock-page-header-title">
                <a href="{!! Url::route('admin.stock.transfers') !!}" class="fs-4 fw-bold pe-3"><i class="fa-light fa-arrow-left-long"></i></a>
                <span class="fs-3 fw-bold">Chuyển hàng</span>
            </div>
            {!! Plugin::partial(STOCK_NAME, 'admin/search') !!}
            <div class="stock-page-header-action"></div>
        </div>
        <div class="stock-page-products box" id="js_transfer_receive_products">
            <div class="box-content">
                <ul class="js_transfer_receive_tab nav nav-tabs nav-tabs-horizontal mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all" aria-selected="true">Tất cả (<span class="js_transfer_receive_tab_count">0</span>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="match" aria-selected="true">Khớp (<span class="js_transfer_receive_tab_count">0</span>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notMatch" aria-selected="true">Lệch (<span class="js_transfer_receive_tab_count">0</span>)</button>
                    </li>
                </ul>
                <div class="stock-page-table js_transfer_receive_table" style="display: none">
                    {!! $table->display() !!}
                </div>
            </div>
        </div>
    </div>
    <div class="stock-page-form-info">
        <form action="" id="js_transfer_receive_form">
            <div class="box h-100">
                <div class="box-content">
                    <input type="hidden" value="{!! $action !!}" id="transfer_receive_action">
                    @if(!empty($id))
                        <input type="hidden" name="transfer_receive_id" value="{!! $id !!}" id="transfer_receive_input_id">
                    @endif
                    {!! $form->html() !!}
                    <div class="stock-page-button gap-2">
                        {!! Admin::button('blue', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Lưu Tạm',
                            'id' => 'js_transfer_receive_btn_draft'
                        ]) !!}

                        {!! Admin::button('green', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Nhận hàng',
                            'id' => 'js_transfer_receive_btn_save'
                        ]) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="transfer_receive_new_product_table_item_template" type="text/x-custom-template">
    <tr class="js_column transfer-receive-column-${id}" data-id="${id}">
        <td class="code column-code">
            <span>${code}</span>
        </td>
        <td class="product_name column-product_name">
            ${fullname}
        </td>
        <td class="stock column-stock">${stock}</td>
        <td class="quantity column-send-quantity">${send_quantity}</td>
        <td class="quantity column-receive-quantity">
            <input type="number" min="1" name="products[${id}][receive_quantity]" value="${receive_quantity}" class="form-control js_input_receive_quantity" />
        </td>
        <td class="column-price">
            <span class="js_input_price">${price}</span>
        </td>
        <td class="column-receive-price">
            <span class="js_input_receive_price">${receive_price}</span>
        </td>
    </tr>
</script>

<script>
    $(function () {
        const handle = new TransferReceiveNewHandle()
        handle.events()
    })
</script>

<style>
    .ui-layout {
        max-width: 100%;
    }
</style>