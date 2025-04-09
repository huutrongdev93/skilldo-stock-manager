{!! Admin::partial('components/page-default/page-index', [
    'name'      => 'Phiếu nhập hàng',
    'table'     => $table,
]) !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/purchase-order/print'); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export', [
    'action' => 'PurchaseOrderAdminAjax::exportDetail',
    'title'  => 'Xuất chi tiết nhập hàng'
]); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/export-list', [
    'action'    => 'PurchaseOrderAdminAjax::export',
    'title' => 'Xuất phiếu nhập hàng'
]); !!}

<script defer>
    $(document).ready(function () {
        let handle = new PurchaseOrderIndexHandle();
        handle.events()
    })
</script>


