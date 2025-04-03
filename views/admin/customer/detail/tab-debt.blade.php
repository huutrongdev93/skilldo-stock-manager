<div id="admin_table_user_detail_debt_list" data-debt="{!! $user->debt !!}">
{!! Admin::partial('components/page-default/page-table', [
    'table' => $table,
]) !!}
</div>

<div class="box">
    <div class="box-footer">
        {!! Admin::button('green', [
            'text' => 'Điều chỉnh',
            'icon' => '<i class="fa-duotone fa-solid fa-rotate"></i>',
            'class' => 'js_user_debt_btn_update_balance',
            'data-id' => $user->id,
        ]) !!}
    </div>
</div>

{!! Plugin::partial(STOCK_NAME, 'admin/customer/detail/modal-debt-payment') !!}
{!! Plugin::partial(STOCK_NAME, 'admin/customer/detail/modal-update-balance') !!}

<script>
    $(function() {
        new CustomerPayment()
    })
</script>