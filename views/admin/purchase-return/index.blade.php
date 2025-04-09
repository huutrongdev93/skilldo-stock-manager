{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu trả hàng',
    'table'     => $table,
]) !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/purchase-return/print'); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export', [
    'action' => 'PurchaseReturnAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết nhập hàng'
]); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export-list', [
    'action'    => 'PurchaseReturnAdminAjax::export',
    'title' => 'Xuất phiếu nhập hàng'
]); !!}
<script defer>
    $(function ()
    {
        let handle = new PurchaseReturnIndexHandle();
        handle.events()
    })
</script>


