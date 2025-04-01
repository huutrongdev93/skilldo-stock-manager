{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Trả hàng',
    'table'     => $table,
]) !!}
{!! Plugin::partial(STOCK_NAME, 'admin/order-return/detail-info', compact('tableProduct')); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/order-return/print'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export', [
    'action' => 'OrderReturnAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết phiếu trả hàng'
]); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export-list', [
    'action'    => 'TransferAdminAjax::export',
    'title' => 'Xuất phiếu trả hàng'
]); !!}
<script defer>
    $(function ()
    {
        let handle = new OrderReturnIndexHandle();
        handle.events()
    })
</script>


