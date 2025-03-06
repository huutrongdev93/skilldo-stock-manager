{!! \Admin\Component::block()
    ->header('Thông tin nhà cung cấp')
    ->open() !!}

<div class="row">
    <div class="col-md-6">
        <div class="row mb-3">
            <div class="col-md-4"><span>Mã nhà cung cấp</span></div>
            <div class="col-md-8"><b>{{ $object->code }}</b></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><span>Tên nhà cung cấp</span></div>
            <div class="col-md-8"><b>{{ $object->name }}</b></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><span>Email</span></div>
            <div class="col-md-8"><b>{{ $object->email }}</b></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row mb-3">
            <div class="col-md-4"><span>Số điện thoại</span></div>
            <div class="col-md-8"><b>{{ $object->phone }}</b></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><span>Địa chỉ</span></div>
            <div class="col-md-8"><b>{{ $object->address }}</b></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><span>Công ty</span></div>
            <div class="col-md-8"><b>{{ $object->company ?? '' }}</b></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><span>Mã số thuế</span></div>
            <div class="col-md-8"><b>{{ $object->tax ?? '' }}</b></div>
        </div>
    </div>
</div>

{!! \Admin\Component::block()->close() !!}

{!! Admin::tabs([
    'purchaseOrder' => [
        'label'   => 'Lịch sử nhập hàng', //Tiêu đề của tab
        'content' => Plugin::partial(STOCK_NAME, 'admin/suppliers/detail/tab-purchase-order', ['table' => $tablePurchaseOrder]), //Nội dung của tab
    ],
    'purchaseReturn' => [
        'label'   => 'Lịch sử trả hàng',
        'content' => Plugin::partial(STOCK_NAME, 'admin/suppliers/detail/tab-purchase-order', ['table' => $tablePurchaseReturn])
    ],
    'debt' => [
        'label'   => 'Nợ cần trả NCC',
        'content' => Plugin::partial(STOCK_NAME, 'admin/suppliers/detail/tab-debt', ['table' => $tableDebt, 'object' => $object])
    ],
], 'debt', ['class' => 'mb-0']) !!}

<style>
    .nav-tabs.nav-tabs-horizontal {
        margin-bottom: 0!important;
        border-radius: 4px 4px 0 0;
    }
</style>

<script>
    $(function() {
        const handler = new SuppliersPayment()
        handler.events()
    })
</script>