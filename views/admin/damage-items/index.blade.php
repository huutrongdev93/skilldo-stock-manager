{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu xuất hủy',
    'table'     => $table,
]) !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/damage-items/detail-info', compact('tableProduct')); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/damage-items/print'); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export', [
    'action' => 'DamageItemsAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết phiếu xuất hủy hàng'
]); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export-list', [
    'action'    => 'DamageItemsAdminAjax::export',
    'title' => 'Xuất phiếu danh sách phiếu xuất hủy hàng'
]); !!}
<script defer>
    $(function() {
        let handle = new DamageItemsIndexHandle();
        handle.events()
    })
</script>


