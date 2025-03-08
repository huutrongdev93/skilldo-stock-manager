<div class="box mb-3" id="js_supplier_detail_data" data-supplier="{!! $supplier !!}">
    <div class="box-header ">
        <h4 class="box-title">Thông tin nhà cung cấp</h4>
    </div>
    <div class="box-content">
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
                    <div class="col-md-4"><span>Ngày tạo</span></div>
                    <div class="col-md-8"><b>{{ $object->created }}</b></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><span>Tổng mua</span></div>
                    <div class="col-md-8"><b>{{ Prd::price($object->total_invoiced) }}</b></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><span>Nợ phải trả</span></div>
                    <div class="col-md-8"><b class="color-red js_supplier_detail_debt_total">{{ number_format($object->debt) }}</b></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row mb-3">
                    <div class="col-md-4"><span>Email</span></div>
                    <div class="col-md-8"><b>{{ $object->email }}</b></div>
                </div>
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
    </div>
    <div class="box-footer">
        {!! Admin::button('green', [
            'text' => 'Điều chỉnh',
            'icon' => '<i class="fa-duotone fa-solid fa-rotate"></i>',
            'class' => 'js_supplier_btn_update_balance',
            'data-id' => $object->id
        ]) !!}
    </div>
</div>

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


{!! Plugin::partial(STOCK_NAME, 'admin/suppliers/detail/modal-update-balance') !!}

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