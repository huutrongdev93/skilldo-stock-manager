{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Chuyển hàng',
    'table'     => $table,
]) !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/transfer/detail-info', compact('tableProduct')); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/transfer/print'); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export', [
    'action' => 'TransferAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết phiếu chuyển hàng'
]); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export-list', [
    'action'    => 'TransferAdminAjax::export',
    'title' => 'Xuất phiếu chuyển hàng'
]); !!}
<script defer>
    $(function ()
    {
        let handle = new TransferIndexHandle();
        handle.events()
    })
</script>


