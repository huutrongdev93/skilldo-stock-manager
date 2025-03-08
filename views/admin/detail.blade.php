{!! Plugin::partial(STOCK_NAME, 'admin/cash-flow/detail-info'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/purchase-order/detail-info'); !!}
{!! Plugin::partial(STOCK_NAME, 'admin/purchase-return/detail-info'); !!}
<script>
    $(function() {
        const handler = new WarehouseDetail()
        handler.events()
    })
</script>