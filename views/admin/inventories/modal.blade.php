<div class="modal fade" id="js_inventories_model_detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Thông tin tồn kho</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="js_inventories_detail_tabs nav nav-tabs nav-tabs-horizontal mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info" aria-selected="true">Thông tin</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history" aria-selected="true">Thẻ kho</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="onHand" aria-selected="true">Tồn kho</button>
                    </li>
                </ul>
                <div class="js_inventories_detail_tab_content" data-tab="info">
                    <form action="" id="js_inventories_detail_info_form">
                        {!! Admin::loading() !!}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="">Mã hàng</label>
                                <input name="code" type="text" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="">Tên hàng</label>
                                <input name="title" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="box" style="box-shadow: none!important;">
                            <div class="box-header">
                                <div class="header-title">Kho hàng</div>
                            </div>
                            <div class="box-content">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="">Giá vốn</label>
                                        <input name="cost" type="text" class="form-control js_input_price" data-input-type="currency">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Tồn kho</label>
                                        <input name="stock" type="text" class="form-control">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="" class="mb-3">Áp dụng giá vốn cho</label>
                                        <div class="form-check">
                                            <p><label><input name="cost_scope" type="radio" class="form-check-input" value="1" checked> Chi nhánh hiện tại</label></p>
                                            <p><label><input name="cost_scope" type="radio" class="form-check-input" value="2"> Toàn bộ chi nhánh</label></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="js_inventories_detail_tab_content" data-tab="history" style="display: none">
                    {!! Admin::loading() !!}
                    <table class="display table table-striped media-table ">
                        <thead>
                        <tr>
                            <th class="">Chứng từ</th>
                            <th class="">Thời gian</th>
                            <th class="">Loại giao dịch</th>
                            <th class="">Đối tác</th>
                            <th class="">Giá GD</th>
                            <th class="">Giá vốn</th>
                            <th class="">Số lượng</th>
                            <th class="">Tồn cuối</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="pagination mt-3"></div>
                </div>
                <div class="js_inventories_detail_tab_content" data-tab="onHand" style="display: none">
                    {!! Admin::loading() !!}
                    <table class="display table table-striped media-table ">
                        <thead>
                            <tr>
                                <th class="">Chi nhánh</th>
                                <th class="">Tồn kho</th>
                                <th class="">KH đặt</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{trans('button.close')}}</button>
                <button type="submit" class="btn btn-blue" form="js_inventories_detail_info_form">{{trans('button.save')}}</button>
            </div>
        </div>
    </div>
</div>

<script id="js_inventories_detail_history_template" type="text/x-custom-template">
    <tr class="js_column" data-id="${id}">
        <td class=""><span>${target_code}</span></td>
        <td class=""><span>${created}</span></td>
        <td class=""><span>${target_name}</span></td>
        <td class=""><span>${partner_name}</span></td>
        <td class=""><span>${price}</span></td>
        <td class=""><span>${cost}</span></td>
        <td class=""><span>${quantity}</span></td>
        <td class=""><span>${end_stock}</span></td>
    </tr>
</script>

<script id="js_inventories_detail_onHand_template" type="text/x-custom-template">
    <tr class="js_column" data-id="${id}">
        <td class=""><span>${branch_name}</span></td>
        <td class=""><span>${stock}</span></td>
        <td class=""><span>${reserved}</span>
    </tr>
</script>

<script defer>
	$(function () {

        class InventoriesIndexHandle
        {
            constructor()
            {
                this.detail = {
                    modal: {
                        id: `#js_inventories_model_detail`,
                        element: $(`#js_inventories_model_detail`),
                        handel: null,
                    },
                    history: {
                        tbody: $(`.js_inventories_detail_tab_content[data-tab="history"] tbody`),
                        loading: $(`.js_inventories_detail_tab_content[data-tab="history"] .loading`),
                        divPagination: $(`.js_inventories_detail_tab_content[data-tab="history"] .pagination`),
                        templateName: `#js_inventories_detail_history_template`,
                        pagination: null
                    },
                    info: {
                        form : $(`#js_inventories_detail_info_form`),
                        loading : $(`#js_inventories_detail_info_form .loading`),
                        button : null,
                    },
                    onHand: {
                        tbody: $(`.js_inventories_detail_tab_content[data-tab="onHand"] tbody`),
                        loading: $(`.js_inventories_detail_tab_content[data-tab="onHand"] .loading`),
                        templateName: `#js_inventories_detail_onHand_template`,
                    }
                }

                this.productId = 0;

                this.product = {}
            }

            onClickPurchaseOrder(button)
            {
                let productId = button.attr('data-id');

                let data = {
                    product_id: productId,
                }

                SkilldoUtil.storage.set('stock_purchase_order_add_source', data)

                window.open(button.attr('href'),'_blank');
            }

            onClickPurchaseReturn(button)
            {
                let productId = button.attr('data-id');

                let data = {
                    product_id: productId,
                }

                SkilldoUtil.storage.set('stock_purchase_return_add_source', data)

                window.open(button.attr('href'),'_blank');
            }

            clickButtonDetail(button) {

                if(this.detail.modal.handel === null)
                {
                    this.detail.modal.handel = new bootstrap.Modal(this.detail.modal.id, {backdrop: "static", keyboard: false})
                }

                if(this.detail.history.pagination === null)
                {
                    this.detail.history.pagination = new Pagination({
                        limit: 10,
                        page : 1
                    })
                }

                this.detail.info.button = button

                let productId = button.data('id')

                this.product = button.data('bill')

                if(this.productId != productId)
                {
                    this.productId = productId
                    this.detail.info.form.find('input[name="code"]').val(this.product.code)
                    this.detail.info.form.find('input[name="title"]').val(this.product.title)
                    this.detail.info.form.find('input[name="cost"]').val(SkilldoUtil.formatNumber(this.product.price_cost))
                    this.detail.info.form.find('input[name="stock"]').val(this.product.stock)
                }

                this.detail.modal.handel.show()
            }

            loadHistories()
            {
                this.detail.history.tbody.html('');

                this.detail.history.divPagination.html('');

                let self = this

                this.detail.history.loading.show();

                let data = {
                    action    : 'StockInventoryAdminAjax::histories',
                    page      : this.detail.history.pagination.page,
                    limit     : this.detail.history.pagination.limit,
                    productId : this.productId,
                }

                request.post(ajax, data)
                    .then(function(response) {

                        if (response.status === 'error') SkilldoMessage.response(response);

                        if (response.status === 'success') {

                            let historyContent = ''

                            for (const [key, item] of Object.entries(response.data.items)) {
                                historyContent += $(self.detail.history.templateName)
                                    .html()
                                    .split(/\$\{(.+?)\}/g)
                                    .map(render(item))
                                    .join('');
                            }

                            this.detail.history.pagination.setLimit(response.data.pagination.limit);

                            this.detail.history.pagination.setTotal(response.data.pagination.total);

                            this.detail.history.pagination.setCurrentPage(response.data.pagination.page);

                            this.detail.history.tbody.html(historyContent);

                            this.detail.history.divPagination.html(this.detail.history.pagination.render());
                        }

                        this.detail.history.loading.hide();

                    }.bind(this))
            }

            loadOnHand()
            {
                this.detail.onHand.tbody.html('');

                this.detail.onHand.loading.show();

                let self = this

                let data = {
                    action    : 'StockInventoryAdminAjax::onHand',
                    productId : this.productId,
                }

                request.post(ajax, data)
                    .then(function(response) {

                        if (response.status === 'error') SkilldoMessage.response(response);

                        if (response.status === 'success') {

                            let content = ''

                            for (const [key, item] of Object.entries(response.data.items)) {
                                content += $(self.detail.onHand.templateName)
                                    .html()
                                    .split(/\$\{(.+?)\}/g)
                                    .map(render(item))
                                    .join('');
                            }

                            this.detail.onHand.tbody.html(content);
                        }

                        this.detail.onHand.loading.hide();

                    }.bind(this))
            }

            saveProduct(form)
            {
                this.detail.info.loading.show()

                let data = form.serializeJSON()
                data.cost = data.cost.replace(/,/g, '')
                data.action     = 'StockInventoryAdminAjax::saveProduct',
                data.productId  = this.productId,

                request.post(ajax, data, {
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                    .then(function(response) {

                        SkilldoMessage.response(response);

                        if (response.status === 'success') {

                            this.product.code = data.code
                            this.product.title = data.title
                            this.product.price_cost = data.cost
                            this.product.stock = data.stock
                            this.detail.info.button.attr('data-bill', JSON.stringify(this.product));
                        }

                        this.detail.info.loading.hide();

                    }.bind(this))
            }

            events()
            {
                let handle = this;

                $(document)
                    .on('click', '.js_inventory_btn_purchase_order', function () {
                        handle.onClickPurchaseOrder($(this))
                        return false
                    })
                    .on('click', '.js_inventory_btn_purchase_return', function () {
                        handle.onClickPurchaseReturn($(this))
                        return false
                    })
                    .on('click', '.js_inventory_btn_edit', function () {
                        handle.clickButtonDetail($(this))
                        return false
                    })
                    .on('submit', '#js_inventories_detail_info_form', function () {
                        handle.saveProduct($(this))
                        return false
                    })
                    .on('click', '.js_inventories_detail_tabs .nav-link', function () {

                        let tabId = $(this).attr('id');

                        // Xóa class active khỏi tất cả các tab
                        $('.js_inventories_detail_tabs .nav-link').removeClass('active');

                        // Thêm class active cho tab vừa click
                        $(this).addClass('active');

                        // Ẩn tất cả các tab content
                        $('.js_inventories_detail_tab_content').hide();

                        // Hiển thị tab content tương ứng với data-tab khớp với id
                        $('.js_inventories_detail_tab_content[data-tab="' + tabId + '"]').show();

                        // Cập nhật aria-selected
                        $('.nav-link').attr('aria-selected', 'false');

                        $(this).attr('aria-selected', 'true');

                        if(tabId === 'history')
                        {
                            handle.loadHistories()
                        }

                        if(tabId === 'onHand')
                        {
                            handle.loadOnHand()
                        }

                        return false
                    })
            }
        }

        const handler = new InventoriesIndexHandle()

        handler.events()
	})
</script>