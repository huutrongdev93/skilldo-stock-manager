<div class="stock-page-form damage-items">
    <div class="stock-page-form-content">
        <div class="stock-page-header mb-3 gap-4">
            <div class="stock-page-header-title">
                <a href="{!! Url::route('admin.stock.damageItems') !!}" class="fs-4 fw-bold pe-3"><i class="fa-light fa-arrow-left-long"></i></a>
                <span class="fs-3 fw-bold">Xuất Hủy Hàng</span>
            </div>
            {!! Plugin::partial(STOCK_NAME, 'admin/search') !!}
            <div class="stock-page-header-action"></div>
        </div>
        <div class="stock-page-products box" id="js_damage_items_products">
            <div class="box-content">
                <div class="stock-page-table js_damage_items_table" style="display: none">
                    {!! $table->display() !!}
                </div>
                {!! Plugin::partial(STOCK_NAME, 'admin/import', [
                    'module' => 'damage_items',
                    'examples' => 'MauFileXuatHuy'
                ]) !!}
            </div>
        </div>
    </div>
    <div class="stock-page-form-info">
        <form action="" id="js_damage_items_form">
            <div class="box h-100">
                <div class="box-content">
                    <input type="hidden" value="{!! $action !!}" id="damage_items_action">
                    @if(!empty($id))
                        <input type="hidden" name="damage_items_id" value="{!! $id !!}" id="damage_items_input_id">
                    @endif
                    {!! $form->html() !!}
                    <div class="stock-page-button gap-2">
                        {!! Admin::button('blue', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Lưu Tạm',
                            'id' => 'js_damage_items_btn_draft'
                        ]) !!}

                        {!! Admin::button('green', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Hoàn thành',
                            'id' => 'js_damage_items_btn_save'
                        ]) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="damage_items_new_product_table_item_template" type="text/x-custom-template">
    <tr class="js_column" data-id="${id}">
        <td class="code column-code">
            <span>${code}</span>
        </td>
        <td class="product_name column-product_name">
            <span>${title}</span>
        </td>
        <td class="quantity column-quantity">
            <input type="number" min="1" name="products[${id}][quantity]" value="${quantity}" class="form-control js_input_quantity" />
        </td>
        <td class="cost column-cost">
            <span>${cost}</span>
        </td>
        <td class="subtotal column-subtotal">
            <span class="js_subtotal">${subtotal}</span>
        </td>
        <td class="action column-action">
            <button class="btn btn-red js_damage_items_btn_delete" data-id="${id}">{!! Admin::icon('delete') !!}</button>
        </td>
    </tr>
</script>

<script>
    $(function () {
        let handle = new DamageItemsNewHandle()
        handle.events()
    })
</script>

<style>
    .ui-layout {
        max-width: 100%;
    }
</style>