{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu trả hàng',
    'table'     => $table,
]) !!}
{!! Plugin::partial(STOCK_NAME, 'admin/purchase-return/print'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export', [
    'action' => 'StockPurchaseReturnAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết nhập hàng'
]); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export-list', [
    'action'    => 'StockPurchaseReturnAdminAjax::export',
    'title' => 'Xuất phiếu nhập hàng'
]); !!}
<script defer>
    $(function ()
    {
        let handle = new PurchaseReturnIndexHandle();
        handle.events()
    })
</script>


