<div class="stock-page-form stock-take">
    <div class="stock-page-form-content">
        <div class="stock-page-header mb-3 gap-4">
            <div class="stock-page-header-title">
                <a href="{!! Url::route('admin.order.returns') !!}" class="fs-4 fw-bold pe-3"><i class="fa-light fa-arrow-left-long"></i></a>
                <span class="fs-3 fw-bold">Trả hàng</span>
            </div>
            <div class="stock-page-header-action"></div>
        </div>
        <div class="stock-page-products box" id="js_order_return_products">
            <div class="box-content p-0">
                <div class="stock-page-table js_order_return_table">
                    {!! $table->display() !!}
                </div>
            </div>
        </div>
    </div>
    <div class="stock-page-form-info">
        <form action="" id="js_order_return_form" data-order="{!! htmlspecialchars(json_encode($order->toObject())) !!}" data-order-items="{!! htmlspecialchars(json_encode(\SkillDo\Utils::toObject($orderItems))) !!}">
            <div class="box h-100">
                <div class="box-content">
                    <input type="hidden" value="{!! $action !!}" id="order_return_action">
                    <input type="hidden" value="{!! $order->id !!}" id="order_id">
                    {!! $form->html() !!}
                    <div class="stock-page-button gap-2">
                        {!! Admin::button('green', [
                            'icon' => Admin::icon('save'),
                            'text' => 'Hoàn thành',
                            'id' => 'js_order_return_btn_save'
                        ]) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="order_return_new_product_table_item_template" type="text/x-custom-template">
    <tr class="js_column order-return-column-${id}" data-id="${id}">
        <td class="code column-code">
            <span>${code}</span>
        </td>
        <td class="product_name column-product_name">
            ${fullname}
        </td>
        <td class="quantity column-quantity">
            <div class="d-flex gap-4 align-items-center">
                <input type="text" min="1" name="products[${id}][quantity]" value="${quantity}" class="form-control js_input_quantity" data-input-type="currency" style="max-width: 200px" />
                <p class="mb-0 min">/ ${quantity_sell}</p>
            </div>
        </td>
        <td class="column-price">
            <input type="text" min="0" name="products[${id}][price]" value="${price}" class="form-control js_input_price" data-input-type="currency" />
        </td>
        <td class="column-sub-total">
            <span class="js_input_subtotal">${subtotal}</span>
        </td>
    </tr>
</script>

<script>
    $(function () {
        const handle = new OrderReturnNewHandle()
        handle.events()
    })
</script>

<style>
    .ui-layout {
        max-width: 100%;
    }
</style>