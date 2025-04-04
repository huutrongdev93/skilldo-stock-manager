{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu kiểm kho',
    'table'     => $table,
]) !!}
{!! Plugin::partial(STOCK_NAME, 'admin/stock-take/detail-info', compact('tableProduct')); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/stock-take/print'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export', [
    'action' => 'StockTakeAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết phiếu kiểm kho'
]); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export-list', [
    'action'    => 'StockTakeAdminAjax::export',
    'title' => 'Xuất phiếu kiểm kho'
]); !!}
<script defer>
    $(function ()
    {
        let handle = new StockTakeIndexHandle();
        handle.events()
    })
</script>


