<div class="dropdown d-inline-block ms-2 me-2">
    <a class="btn btn-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Kho hàng
    </a>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.index') !!}">
                <span>Tồn kho</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.transfers') !!}">
                <span>Chuyển hàng</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.stockTakes') !!}">
                <span>Kiểm kho</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.damageItems') !!}">
                <span>Xuất hủy</span>
            </a>
        </li>
    </ul>
</div>

<div class="dropdown d-inline-block ms-2 me-2">
    <a class="btn btn-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Nhập hàng
    </a>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.suppliers') !!}">
                <span>Nhà cung cấp</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.purchaseOrders') !!}">
                <span>Nhập hàng</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{!! Url::route('admin.stock.purchaseReturns') !!}">
                <span>Trả hàng nhập</span>
            </a>
        </li>
    </ul>
</div>

<div class="dropdown d-inline-block ms-2 me-2">
    <a class="btn btn-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span>{!! $currentBranch->name ?? 'Chọn Chi nhánh' !!}</span>
    </a>
    <ul class="dropdown-menu">
        @foreach($branches as $branch)
            <li>
                <a class="dropdown-item js_btn_chose_branch {{ $branch->id === $currentBranch->id ? 'active' : '' }}" href="#" data-id="{!! $branch->id !!}">
                    <span>{!! $branch->name !!}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

<script>
    $(function () {
        $(document).on('click', '.js_btn_chose_branch', function (e) {

            let id = $(this).attr('data-id');

            let loading = SkilldoUtil.buttonLoading($(this))

            let data = {
                action: 'Stock_Manager_Ajax::changeUserBrand',
                id: id
            }

            loading.start()

            request.post(ajax, data).then(function(response) {

                SkilldoMessage.response(response)

                loading.stop()

                if (response.status === 'success') {
                    window.location.reload()
                }
            })

            return false
        })
    })
</script>