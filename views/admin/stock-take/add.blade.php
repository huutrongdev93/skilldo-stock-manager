<div class="stock-page-form stock-take">
    <div class="stock-page-form-content">
        <div class="stock-page-header mb-3 gap-4">
            <div class="stock-page-header-title">
                <a href="{!! Url::route('admin.purchase.returns') !!}" class="fs-4 fw-bold pe-3"><i class="fa-light fa-arrow-left-long"></i></a>
                <span class="fs-3 fw-bold">Kiểm Kho</span>
            </div>
            {!! Plugin::partial(SKDEPOT_NAME, 'admin/search') !!}
            <div class="stock-page-header-action"></div>
        </div>
        <div class="stock-page-products box" id="js_stock_take_products">
            <div class="box-content">
                <ul class="js_stock_take_tab nav nav-tabs nav-tabs-horizontal mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all" aria-selected="true">Tất cả (<span class="js_stock_take_tab_count">0</span>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="match" aria-selected="true">Khớp (<span class="js_stock_take_tab_count">0</span>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notMatch" aria-selected="true">Lệch (<span class="js_stock_take_tab_count">0</span>)</button>
                    </li>
                </ul>
                <div class="stock-page-table js_stock_take_table" style="display: none">
                    {!! $table->display() !!}
                </div>
                {!! Plugin::partial(SKDEPOT_NAME, 'admin/import', [
                    'module' => 'stock_take',
                    'examples' => 'MauFileKiemKho'
                ]) !!}
            </div>
        </div>
    </div>
    <div class="stock-page-form-info">
        <form action="" id="js_stock_take_form">
            <div class="box h-100">
                <div class="box-content">
                    <input type="hidden" value="{!! $action !!}" id="stock_take_action">
                    @if(!empty($id))
                        <input type="hidden" name="stock_take_id" value="{!! $id !!}" id="stock_take_input_id">
                    @endif
                    {!! $form->html() !!}
                    <div class="stock-page-button gap-2">
                        {!! Admin::button('blue', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Lưu Tạm',
                            'id' => 'js_stock_take_btn_draft'
                        ]) !!}

                        {!! Admin::button('green', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Hoàn thành',
                            'id' => 'js_stock_take_btn_save'
                        ]) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="stock_take_new_product_table_item_template" type="text/x-custom-template">
    <tr class="js_column stock-take-column-${id}" data-id="${id}">
        <td class="code column-code">
            <span>${code}</span>
        </td>
        <td class="product_name column-product_name">
            ${fullname}
        </td>
        <td class="stock column-stock">${stock}</td>
        <td class="quantity column-quantity">
            <input type="number" min="1" name="products[${id}][quantity]" value="${quantity}" class="form-control js_input_quantity" />
        </td>
        <td class="adjustment-quantity column-adjustment-quantity">
            <span class="js_input_adjustment_quantity">${adjustment_quantity}</span>
        </td>
        <td class="adjustment-price column-adjustment-price">
            <span class="js_input_adjustment_price">${adjustment_price}</span>
        </td>
        <td class="action column-action">
            <button class="btn btn-red js_stock_take_btn_delete" data-id="${id}">{!! Admin::icon('delete') !!}</button>
        </td>
    </tr>
</script>

<script>
    $(function () {
        const handle = new StockTakeNewHandle()
        handle.events()
    })
</script>

<style>
    .ui-layout {
        max-width: 100%;
    }
</style>