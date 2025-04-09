{!! Plugin::partial(SKDEPOT_NAME, 'admin/cash-flow/detail-info'); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/purchase-order/detail-info'); !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/purchase-return/detail-info'); !!}
<script>
    $(function() {
        const handler = new SkdepotDetail()
        handler.events()
    })
</script>