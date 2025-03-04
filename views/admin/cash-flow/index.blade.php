{!!
Admin::partial('components/page-default/page-index', [
    'name'      => $title,
    'table'     => $table,
]);
!!}

{!!
Plugin::partial(STOCK_NAME, 'admin/cash-flow/add/modal', [
    'modalTitle' => 'Lập phiếu thu',
    'modalId'    => 'receipt',
    'form'       => $formReceipt,
]);
!!}

{!!
Plugin::partial(STOCK_NAME, 'admin/cash-flow/add/modal', [
    'modalTitle' => 'Lập phiếu chi',
    'modalId'    => 'payment',
    'form'       => $formPayment,
]);
!!}

{!!
Plugin::partial(STOCK_NAME, 'admin/cash-flow/add/modal-partner', [
    'modalTitle' => 'Lập phiếu chi',
    'form'       => $formPartner,
]);
!!}

{!! Plugin::partial(STOCK_NAME, 'admin/cash-flow/detail-info'); !!}

<script defer>
    $(function() {
        let handle = new CashFlowIndexHandle();
        handle.events()
    })
</script>