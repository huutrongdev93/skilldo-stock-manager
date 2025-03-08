{!! Admin::partial('components/page-default/page-table', [
    'table' => $table,
]) !!}

<div class="box">
    <div class="box-footer">
        {!! Admin::button('green', [
            'text' => 'Thanh toán',
            'icon' => '<i class="fa-duotone fa-solid fa-calculator"></i>',
            'class' => 'js_supplier_debt_btn_payment',
            'data-id' => $object->id
        ]) !!}
    </div>
</div>

{!! Plugin::partial(STOCK_NAME, 'admin/suppliers/detail/modal-debt-payment') !!}