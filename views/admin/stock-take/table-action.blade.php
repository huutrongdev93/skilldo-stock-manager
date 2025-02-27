<div class="dropdown d-inline-block btn-group-sm">
    <a class="btn btn-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-regular fa-ellipsis-vertical"></i>
    </a>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item js_btn_export_detail" href="#" data-id="{!! $item->id !!}">
                <i class="fa-duotone fa-solid fa-download"></i>
                <span>Xuất dữ liệu</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.stockTakes.edit', ['id' => $item->id]).'?type=clone' !!}">
                <i class="fa-duotone fa-solid fa-copy"></i>
                <span>Sao chép</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item js_stock_take_btn_print" href="#" data-id="{!! $item->id !!}">
                <i class="fa-duotone fa-solid fa-print"></i>
                <span>In</span>
            </a>
        </li>
    </ul>
</div>