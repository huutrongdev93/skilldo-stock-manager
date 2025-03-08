{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu nhập hàng',
    'table'     => $table,
]) !!}
{!! Plugin::partial(STOCK_NAME, 'admin/purchase-order/print'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export', [
    'action' => 'StockPurchaseOrderAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết nhập hàng'
]); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export-list', [
    'action'    => 'StockPurchaseOrderAdminAjax::export',
    'title' => 'Xuất phiếu nhập hàng'
]); !!}

<script defer>
    $(document).ready(function () {
        let handle = new PurchaseOrderIndexHandle();
        handle.events()
    })
</script>


