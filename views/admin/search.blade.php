<div class="stock-page-header-search d-flex gap-2">
    <div class="search-wrapper">
        <span class="input-icon"><i class="fa-sharp fa-light fa-magnifying-glass"></i></span>
        <input type="text" class="form-control input-search js_warehouse_input_search" placeholder="Tên hoặc mã sản phẩm">
        <div class="autocomplete-suggestions-results"></div>
    </div>
    {!! Admin::button('white', ['icon' => '<i class="fas fa-list"></i>', 'modal' => 'warehouse_product_category_search_modal', 'type' => 'button']) !!}
</div>

<div class="modal fade" id="warehouse_product_category_search_modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Thêm sản phẩm từ danh mục</h4>
            </div>
            <div class="modal-body">
                {!! Admin::loading() !!}
                {!! SkillDo\Form\Form::productsCategories('category_id', ['label' => 'Danh mục']) !!}
                <div class="form-group group text-right">
                    <button type="button" class="btn btn-default" id="js_warehouse_product_category_btn_close" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-blue" id="js_warehouse_product_category_btn_add">{!! Admin::icon('success') !!} Xong</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="warehouse_product_search_item_template" type="text/x-custom-template">
    <div class="product-item cursor-pointer" data-id="${id}">
        <div class="img">
            ${image}
        </div>
        <div class="info">
            <p class="name fs-6 fw-bold">${fullname}</p>
            <p>${code}</p>
            <div class="d-flex gap-3">
                <span>Tồn: ${stock}</span>
                <span>Khách đặt: ${reserved}</span>
            </div>
        </div>
    </div>
</script>