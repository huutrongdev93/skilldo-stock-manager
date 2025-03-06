{!! Plugin::partial(STOCK_NAME, 'admin/cash-flow/detail-info'); !!}

<script>
    $(function() {
        const handler = new WarehouseDetail()
        handler.events()
    })
</script>