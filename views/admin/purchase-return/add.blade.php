<div class="stock-page-form purchase-return">
    <div class="stock-page-form-content">
        <div class="stock-page-header mb-3 gap-4">
            <div class="stock-page-header-title">
                <a href="{!! Url::route('admin.purchase.returns') !!}" class="fs-4 fw-bold pe-3"><i class="fa-light fa-arrow-left-long"></i></a>
                <span class="fs-3 fw-bold">Trả Hàng</span>
            </div>
            {!! Plugin::partial(SKDEPOT_NAME, 'admin/search') !!}
            <div class="stock-page-header-action"></div>
        </div>
        <div class="stock-page-products box" id="js_purchase_return_products">
            <div class="box-content">
                <div class="stock-page-table js_purchase_return_table" style="display: none">
                    {!! $table->display() !!}
                </div>
                {!! Plugin::partial(SKDEPOT_NAME, 'admin/import', [
                    'module' => 'purchase_return',
                    'examples' => 'MauFileTraHangNhap'
                ]) !!}
            </div>
        </div>
    </div>
    <div class="stock-page-form-info">
        <form action="" id="js_purchase_return_form">
            <div class="box h-100">
                <div class="box-content">
                    <input type="hidden" value="{!! $action !!}" id="purchase_return_action">
                    <input type="hidden" value="{!! $source ?? '' !!}" id="purchase_return_source">
                    @if(!empty($id))
                        <input type="hidden" name="purchase_return_id" value="{!! $id !!}" id="purchase_return_input_id">
                    @endif
                    {!! $form->html() !!}
                    <div class="stock-page-button gap-2">
                        {!! Admin::button('blue', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Lưu Tạm',
                            'id' => 'js_purchase_return_btn_draft'
                        ]) !!}

                        {!! Admin::button('green', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Hoàn thành',
                            'id' => 'js_purchase_return_btn_save'
                        ]) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="purchase_return_new_product_table_item_template" type="text/x-custom-template">
    <tr class="js_column" data-id="${id}">
        <td class="code column-code">
            <span>${code}</span>
        </td>
        <td class="product_name column-product_name">
            <span>${fullname}</span>
        </td>
        <td class="quantity column-quantity">
            <input type="number" min="1" name="products[${id}][quantity]" value="${quantity}" class="form-control js_input_quantity" />
        </td>
        <td class="cost column-cost">
            ${cost}
        </td>
        <td class="price column-price">
            <input type="text" min="1" name="products[${id}][price]" value="${price}" data-input-type="currency" class="form-control js_input_price" />
        </td>
        <td class="subtotal column-subtotal">
            <span class="js_input_subtotal">${subtotal}</span>
        </td>
        <td class="action column-action">
            <button class="btn btn-red js_purchase_return_btn_delete" data-id="${id}">{!! Admin::icon('delete') !!}</button>
        </td>
    </tr>
</script>

<script>
    $(function () {
        const handle = new PurchaseReturnNewHandle()
        handle.events()
    })
</script>

<style>
    .ui-layout {
        max-width: 100%;
    }
</style>