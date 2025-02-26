<div class="dropdown d-inline-block ms-2 me-2">
    <a class="btn btn-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Kho hàng
    </a>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.index') !!}">
                {!! \Stock\Helper::icon('inventory') !!}
                <span>Kho hàng</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.purchaseOrders') !!}">
                {!! \Stock\Helper::icon('purchaseOrder') !!}
                <span>Nhập hàng</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.purchaseReturns') !!}">
                {!! \Stock\Helper::icon('purchaseReturn') !!}
                <span>Trả hàng</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.damageItems') !!}">
                {!! \Stock\Helper::icon('damageItems') !!}
                <span>Xuất hủy</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.stockTakes') !!}">
                {!! \Stock\Helper::icon('stockTake') !!}
                <span>Kiểm kho</span>
            </a>
        </li>
    </ul>
</div>