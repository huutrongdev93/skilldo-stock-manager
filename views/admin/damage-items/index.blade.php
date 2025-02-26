{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu xuất hủy',
    'table'     => $table,
]) !!}
{!! Plugin::partial(STOCK_NAME, 'admin/damage-items/detail-info', compact('tableProduct')); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/damage-items/print'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export', [
    'action' => 'StockDamageItemsAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết phiếu xuất hủy hàng'
]); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/export-list', [
    'action'    => 'StockDamageItemsAdminAjax::export',
    'title' => 'Xuất phiếu danh sách phiếu xuất hủy hàng'
]); !!}
<script defer>
    $(function() {
        let handle = new DamageItemsIndexHandle();
        handle.events()
    })
</script>


