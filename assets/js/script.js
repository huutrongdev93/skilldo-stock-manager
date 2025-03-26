class StockExportHandel
{
    constructor() {
        this.modal = {
            table: null,
            detail: null,
        }
        this.elementsTable = {
            modal: $('#js_export_list_modal'),
            form: $('#js_export_list_form'),
            result: $('#js_export_list_result'),
            loading: $('#js_export_list_form .loading')
        }

        this.elementsDetail = {
            modal: $('#js_export_detail_modal'),
            download: $('#js_export_detail_modal .btn-download'),
        }
    }
    clickExportTable(element) {
        if(this.modal.table === null)
        {
            this.modal.table = new bootstrap.Modal('#js_export_list_modal')
        }
        this.elementsTable.form.show();
        this.elementsTable.result.hide();
        this.modal.table.show()
        return false;
    }
    exportTable(element) {

        let action = this.elementsTable.modal.data('action');

        let data;

        let type = this.elementsTable.modal.find('input[name="type"]:checked').val();

        if(type === 'page') {

            data = {};

            data.items = [];

            let divElements = document.querySelectorAll('tr[class*="tr_"]');

            divElements.forEach(function(element) {
                let classList = element.classList;
                for (let i = 0; i < classList.length; i++) {
                    if (classList[i].startsWith("tr_")) {
                        let number = classList[i].substr(3); // Cắt bỏ phần "tr_"
                        data.items.push(number)
                    }
                }
            });

            if(data.items.length === 0)
            {
                SkilldoMessage.error('Bạn chưa chọn dữ liệu để xuất nào');
                return false;
            }
        }

        if(type === 'checked') {

            data = {};

            data.items = []; let i = 0;

            $('.select:checked').each(function () { data.items[i++] = $(this).val(); });

            if(data.items.length === 0) {
                SkilldoMessage.error('Bạn chưa chọn sản phẩm nào');
                return false;
            }
        }

        if(type === 'search') {

            data = {};

            let filter  = $(':input', $('#table-form-filter')).serializeJSON();

            let search = $(':input', $('#table-form-search')).serializeJSON();

            data.search = {...search, ...filter}
        }

        if(typeof data === "undefined" || data === undefined)
        {
            SkilldoMessage.error('Kiểu xuất dữ liệu không hợp lệ');
            return false;
        }

        this.elementsTable.loading.show();

        data.action = action;

        data.export = type

        request.post(ajax, data, {
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function (response) {
            if (response.status === 'success') {
                this.elementsTable.loading.hide();
                this.elementsTable.form.hide();
                this.elementsTable.result.find('a').attr('href', response.data);
                this.elementsTable.result.show();
            }
        }.bind(this));

        return false;
    }
    eventsTable()
    {
        let self = this;

        $(document)
            .on('click', '#js_btn_export_list', function () {
                return self.clickExportTable($(this))
            })
            .on('click', '#js_export_list_btn_submit', function () {
                return self.exportTable($(this))
            })
    }

    clickExportDetail(button) {

        let action = this.elementsDetail.modal.data('action');

        if(this.modal.detail === null)
        {
            this.modal.detail = new bootstrap.Modal('#js_export_detail_modal', {backdrop: "static", keyboard: false})
        }

        let loading = SkilldoUtil.buttonLoading(this.elementsDetail.download)

        loading.start()

        this.modal.detail.show()

        let data = {
            action    : action,
            id        : button.data('id'),
        }

        request.post(ajax, data).then(function (response) {

            loading.stop()

            if (response.status === 'success')
            {
                this.elementsDetail.download.attr('href', response.data);
            }
            else
            {
                SkilldoMessage.response(response);
            }
        }.bind(this));

        return false;
    }
    eventsDetail()
    {
        let self = this;

        $(document)
            .on('click', '.js_btn_export_detail', function () {
                return self.clickExportDetail($(this))
            })
    }
}

class WarehouseSearchProductsHandler
{
    constructor() {

        this.elements = {
            inputKeyword: $('.js_warehouse_input_search'),
            autocomplete: $('.autocomplete-suggestions-results'),
            itemTemplate: $('#warehouse_product_search_item_template'),
            inputCategory: $('#warehouse_product_category_search_modal select[name="category_id"]'),
            modalCategory: $('#warehouse_product_category_search_modal'),
        }

        this.productsSearch = SkilldoUtil.reducer();
    }

    events(handler)
    {
        let self = this

        $(document)
            .on('keyup', '.js_warehouse_input_search', SkilldoUtil.debounce(function () {
                self.search() //function search
            }, 500))
            .on('click', '.js_warehouse_input_search', function () {
                self.focusInput() //function search
            })
            .on("mousedown", function(event) {
                if (!$(event.target).closest(".autocomplete-suggestions-results").length) {
                    self.autocomplete.removeClass("open");
                }
            })
            .on('click', '#js_warehouse_product_category_btn_add', function () {
                self.searchWithCategory($(this), handler) //function search
            })
    }

    search()
    {
        this.elements.autocomplete.html('')

        let keyword = this.elements.inputKeyword.val()

        if(keyword === undefined || keyword.length === 0)
        {
            return false
        }

        this.productsSearch.empty()

        request.post(ajax, {
            action: 'Stock_Manager_Ajax::searchProducts',
            keyword: keyword
        }).then(function (response) {

            if(!this.elements.autocomplete.hasClass('open'))
            {
                this.elements.autocomplete.addClass('open')
            }

            if (response.status == 'success')
            {
                for (const [key, item] of Object.entries(response.data))
                {
                    this.productsSearch.add(item)
                    this.elements.autocomplete.append(this.elements.itemTemplate
                        .html()
                        .split(/\$\{(.+?)\}/g)
                        .map(render(item)).join(''));
                }
            }
        }.bind(this))
    }

    searchWithCategory(button, callback)
    {
        let id = this.elements.inputCategory.val()

        if(id === undefined || id.length === 0)
        {
            SkilldoMessage.error('Bạn chưa chọn danh mục');
            return false
        }

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        request.post(ajax, {
            action: 'Stock_Manager_Ajax::searchProductsByCategory',
            id: id
        }).then(function (response) {

            loading.stop()

            if (response.status == 'success')
            {
                this.elements
                    .modalCategory
                    .find('#js_warehouse_product_category_btn_close')
                    .trigger('click')
                callback.clickAddProductCategory(response)
            }
        }.bind(this))
    }

    focusInput()
    {
        if(this.productsSearch.items.length > 0)
        {
            this.elements.autocomplete.addClass('open')
        }
    }

    get autocomplete() {
        return this.elements.autocomplete;
    }

    get inputKeyword() {
        return this.elements.inputKeyword;
    }
}

class WarehouseIndexHandle
{
    constructor({ module, ajax }) {

        this.ajax = ajax

        this.detail = {
            modal: {
                id: `#js_${module}_modal_detail`,
                element: $(`#js_${module}_modal_detail`),
                handel: null,
                loading: $(`#js_${module}_modal_detail .loading`),
                tbody: $(`#js_${module}_modal_detail tbody`),
                pagination: $(`#js_${module}_modal_detail .pagination`),
                info: $(`#js_${module}_modal_detail .js_detail_content`),
                __templateInfo: `#${module}_detail_template`
            },
            pagination: null
        }

        this.print = {
            elements : {
                content: $(`#js_${module}_print_content`),
                _template: `#${module}_print_template`,
                _templateItem: `#${module}_item_print_template`,
            }
        }

        this.data = {
            id : 0
        }

        this.export = new StockExportHandel();

        this.export.eventsTable();

        this.export.eventsDetail();
    }

    loadProductDetail()
    {
        this.detail.modal.loading.show();

        let data = {
            action    : this.ajax.loadProducts,
            page      : this.detail.pagination.page,
            limit     : this.detail.pagination.limit,
            id        : this.id,
        }

        request.post(ajax, data)
            .then(function(response) {

                if (response.status === 'error') SkilldoMessage.response(response);

                if (response.status === 'success') {

                    response.data.html = decodeURIComponent(atob(response.data.html).split('').map(function (c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));

                    this.detail.pagination.setLimit(response.pagination.limit);

                    this.detail.pagination.setTotal(response.pagination.total);

                    this.detail.pagination.setCurrentPage(response.pagination.page);

                    this.detail.modal.tbody.html(response.data.html);

                    this.detail.modal.pagination.html(this.detail.pagination.render());

                }

                this.detail.modal.loading.hide();

            }.bind(this))
    }

    loadDataDetail() {}

    clickButtonDetail(button) {

        if(this.detail.modal.handel === null)
        {
            this.detail.modal.handel = new bootstrap.Modal(this.detail.modal.id, {backdrop: "static", keyboard: false})
        }
        if(this.detail.pagination === null)
        {
            this.detail.pagination = new Pagination({
                limit: 10,
                page : 1
            })
        }

        let id = button.data('id')

        if(this.id != id)
        {
            this.id = id

            let object = button.data('bill')

            this.detail.modal.info.html(() => {
                return $(this.detail.modal.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(object)).join('');
            });

            this.detail.modal.tbody.html('');

            this.detail.modal.pagination.html('');

            this.loadProductDetail()

            this.loadDataDetail()
        }

        this.detail.modal.handel.show()
    }

    clickPaginationDetail(button)
    {
        let page = button.data('number');
        if(page !== undefined)
        {
            this.detail.pagination.setCurrentPage(page);
            this.loadProductDetail();
        }
        return false;
    }

    clickButtonPrint(button) {

        this.id = button.data('id')

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        let data = {
            action    : this.ajax.print,
            id        : this.id,
        }

        let self = this;

        request.post(ajax, data).then(function (response) {

            loading.stop()

            if (response.status === 'success')
            {
                let purchase = response.data.purchase;

                purchase.details = '';

                for (const [key, item] of Object.entries(response.data.items)) {
                    purchase.details += $(self.print.elements._templateItem)
                        .html()
                        .split(/\$\{(.+?)\}/g)
                        .map(render(item))
                        .join('');
                }

                this.print.elements.content.html(() => {
                    return $(this.print.elements._template).html().split(/\$\{(.+?)\}/g).map(render(purchase)).join('');
                });

                window.print()
            }
            else {
                SkilldoMessage.response(response);
            }
        }.bind(this));

        return false;
    }

    cancelSuccess(response, button)
    {
        $(button.button).closest('tr.js_column').find('td.column-status').html(response.data.status);
        $(button.button).remove()
    }

    get id() {
        return this.data.id
    }

    set id(id) {
        this.data.id = id
    }
}

class WarehouseNewHandle
{
    constructor({ module, ajax }) {

        this.ajax = ajax

        this.elements = {
            form: $(`#js_${module}_form`),
            table: $(`.js_${module}_table`),
            tableBody: $(`#js_${module}_products table tbody`),
            inputId: $(`#${module}_input_id`),
            inputAction: $(`#${module}_action`),
            import: $(`#js_${module}_import_form`),
            templateTableItems: $(`#${module}_new_product_table_item_template`)
        }

        this.action = this.elements.inputAction.val()

        this.idEdit = 0

        this.isEdit = false;

        if(this.action !== 'add')
        {
            this.idEdit = this.elements.inputId.val()

            this.isEdit = (this.action === 'edit');

            this.loadProduct()
        }

        this.products = SkilldoUtil.reducer();

        this.search = new WarehouseSearchProductsHandler();

        this.search.events(this)

        this.elements.table.hide();

        this.elements.import.show();
    }

    loadProduct()
    {
        let self = this

        let payload = {
            action: this.ajax.loadProducts,
            type: this.action,
            id: this.idEdit
        }

        $('.loading').show();

        request.post(ajax, payload).then(function (response) {

            if (response.status == 'success')
            {
                this.elements.tableBody.find('.no-items').remove()

                for (const [key, item] of Object.entries(response.data))
                {
                    let itemN = this.productItem({...item})

                    this.products.add(itemN)

                    this.elements.tableBody.append([itemN].map(function(item) {
                        return self.elements.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                    }));
                }

                this.elements.table.show();

                this.elements.import.hide();

                this.calculate()
            }

            $('.loading').hide();

        }.bind(this))
    }

    calculate() {}

    productItem(item)
    {
        return item
    }

    payloadData(data)
    {
        return data;
    }

    saveSuccess() {}

    clickAddProduct(element)
    {
        let self = this

        let id = element.data('id');

        this.elements.tableBody.find('.no-items').remove()

        if(!this.products.has(id))
        {
            let item = this.search.productsSearch.get(id);

            if(item)
            {
                item = this.productItem({...item})

                this.products.add(item);

                this.elements.tableBody.append([item].map(function(item) {
                    return self.elements.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                }));

                this.elements.table.show();

                this.elements.import.hide();

                this.calculate()
            }
        }

        this.search.autocomplete.removeClass('open')

        this.search.inputKeyword.focus().select();

        return false;
    }

    clickAddProductCategory(response)
    {
        let self = this

        this.elements.tableBody.find('.no-items').remove()

        for (const [key, item] of Object.entries(response.data))
        {
            if(!this.products.has(item.id))
            {
                let itemN = this.productItem({...item})

                this.products.add(itemN);

                this.elements.tableBody.append([itemN].map(function(item) {
                    return self.elements.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                }));
            }
        }

        this.elements.table.show();

        this.elements.import.hide();

        this.calculate()
    }

    clickDeleteProduct(element) {

        let id = element.data('id');

        this.products.delete(id)

        this.elements.tableBody.find('.js_column[data-id="' + id + '"]').remove()

        if(this.products.items.length === 0)
        {
            this.elements.table.hide();

            this.elements.import.show();
        }

        this.calculate()

        return false;
    }

    clickSaveDraft(element)
    {
        if(this.products.items.length === 0)
        {
            SkilldoMessage.error('Bạn chưa chọn sản phẩm nào')
        }

        let loader = SkilldoUtil.buttonLoading(element)

        loader.start()

        let data = this.payloadData(this.elements.form.serializeJSON())

        data.action = this.ajax.addDraft

        if(this.isEdit)
        {
            data.action = this.ajax.saveDraft

            data.id = this.idEdit
        }

        request.post(ajax, data, {
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function(response)
        {
            SkilldoMessage.response(response)

            if(response.status == 'success')
            {
                this.saveSuccess()
            }
            else
            {
                loader.stop()
            }
        }.bind(this))

        return false
    }

    clickSave(element)
    {
        if(this.products.items.length === 0)
        {
            SkilldoMessage.error('Bạn chưa chọn sản phẩm nào')
        }

        let loader = SkilldoUtil.buttonLoading(element)

        loader.start()

        let data = this.payloadData(this.elements.form.serializeJSON())

        data.action = this.ajax.add

        if(this.isEdit)
        {
            data.action = this.ajax.save

            data.id = this.idEdit
        }

        request.post(ajax, data, {
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function(response)
        {
            SkilldoMessage.response(response)

            if(response.status == 'success')
            {
                this.saveSuccess()
            }
            else
            {
                loader.stop()
            }
        }.bind(this))

        return false
    }

    import(element)
    {
        let self = this

        $('.file-upload-text').html(element.find('input[type="file"]').val());

        element.find('.loading').show();

        let formData = new FormData(element[0]);

        formData.append('action', this.ajax.import);

        formData.append('csrf_test_name', encodeURIComponent(getCookie('csrf_cookie_name')));

        request.post(ajax, formData).then(function (response)
        {
            SkilldoMessage.response(response);

            if(response.status === 'success')
            {
                if(response.data.length > 0)
                {
                    this.elements.tableBody.html('');

                    for (let [key, item] of Object.entries(response.data))
                    {
                        item = this.productItem({...item})

                        this.products.add(item)

                        this.elements.tableBody.append([item].map(function(item) {
                            return self.elements.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                        }));
                    }

                    this.elements.table.show();

                    this.elements.import.hide();

                    this.calculate()
                }
                else
                {
                    SkilldoMessage.error('File không có sản phẩm nào hợp lệ');
                }
            }

            $('.file-upload-text').html('Chọn File upload');

            element.trigger('reset');

            element.find('.loading').hide();

        }.bind(this));

        return false;
    }
}

class WarehouseLocation {

    constructor()
    {
        let self = this;

        $(document)
            .on('change', 'select[data-input-address="city"]', function(event) {
                self.changeInputCity($(this))
            })
            .on('change', 'select[data-input-address="district"]', function(event) {
                self.changeInputDistrict($(this))
            })

    }

    changeInputCity(input) {

        let form = input.closest('form');

        let district = form.find('select[data-input-address="district"]')

        let ward = form.find('select[data-input-address="ward"]')

        let data = {
            province_id : input.val(),
            action: 'Cart_Ajax::loadDistricts'
        };

        request.post(ajax, data).then(function(response) {
            if(response.status === 'success') {
                district.html(response.data);
                ward.html('');
            }
        });
    }

    changeInputDistrict(input) {

        let ward = input.closest('form').find('select[data-input-address="ward"]')

        let data = {
            district_id : input.val(),
            action: 'Cart_Ajax::loadWard'
        };

        request.post(ajax, data).then(function(response) {
            if(response.status === 'success') {
                ward.html(response.data);
            }
        });
    }

}

class CashFlowModalDetail
{
    constructor()
    {
        this.ajax = 'CashFlowAdminAjax::detail'

        this.elements = {
            modalId: `#js_cash_flow_modal_detail`,
            modal: $(`#js_cash_flow_modal_detail`),
            modelAction: new bootstrap.Modal('#js_cash_flow_modal_detail', {backdrop: "static", keyboard: false}),
            loading: $(`#js_cash_flow_modal_detail .loading`),
            info: $(`#js_cash_flow_modal_detail .js_detail_content`),
            __templateInfo: `#cash_flow_detail_template`,
            __templateTable: `#cash_flow_detail_table_template`,
            __templateTableItem: `#cash_flow_detail_table_item_template`,
        }

        this.data = {
            id: 0,
            type: undefined,
        }
    }

    handle(button)
    {
        this.elements.info.html('')

        let data = {
            action: this.ajax,
            id: this.data.id,
        }

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                response.data.item.target_table = ''

                if(response.data.item?.targets && response.data.item?.targets.length > 0)
                {
                    let targetItems = ''

                    for (const [key, target] of Object.entries(response.data.item?.targets)) {
                        targetItems += ([target].map(() => {

                            target.need_pay_value = SkilldoUtil.formatNumber(target.need_pay_value)

                            target.paid_value = SkilldoUtil.formatNumber(target.paid_value)

                            if(target.amount < 0)
                            {
                                target.amount = target.amount*-1;
                            }

                            target.amount = SkilldoUtil.formatNumber(target.amount)

                            return $(this.elements.__templateTableItem)
                                .html()
                                .split(/\$\{(.+?)\}/g)
                                .map(render(target))
                                .join('');
                        }))
                    }

                    response.data.item.target_table = ([{}].map(() => {
                        return $(this.elements.__templateTable).html().split(/\$\{(.+?)\}/g).map(render({
                            items : targetItems
                        })).join('');
                    }))
                }

                this.elements.info.html(() => {
                    response.data.item.amount = SkilldoUtil.formatNumber(response.data.item.amount)
                    return $(this.elements.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(response.data.item)).join('');
                });

                this.elements.modelAction.show()
            }
        }.bind(this))

        return false
    }
}

class PurchaseOrderModalDetail
{
    constructor()
    {
        this.ajax = {
            detail: 'StockPurchaseOrderAdminAjax::detail',
            products: 'StockPurchaseOrderAdminAjax::loadProductsDetail',
            cashFlow: 'StockPurchaseOrderAdminAjax::loadCashFlowDetail',
        }

        let modalId = '#js_purchase_order_modal_detail'

        this.elements = {
            modalId: modalId,
            modal: $(modalId),
            modelAction: new bootstrap.Modal(modalId, {backdrop: "static", keyboard: false}),
            loading: $(`${modalId} .loading`),
            info: $(`${modalId} .js_detail_content`),
            __templateInfo: `#purchase_order_detail_template`,
            products: {
                tbody: $(`#js_purchase_order_detail_products tbody`),
                pagination: $(`${modalId} .pagination`),
                __templateTable: `#purchase_order_detail_table_template`,
                __templateTableItem: `#purchase_order_detail_table_item_template`,
            },
            cashFlow: {
                tbody: $(`#js_purchase_order_detail_cash_flow tbody`),
                __templateTableItem: `#purchase_order_detail_cash_flow_table_item_template`,
            },

        }

        this.data = {
            id: 0,
            pagination: new Pagination({
                limit: 10,
                page : 1
            }),
        }
    }

    handle(button)
    {
        let loadCashFlow = button.data('target-cash-flow')

        let object = button.data('bill')

        this.elements.info.html('')

        this.elements.modelAction.show()

        if(object !== undefined && object?.code)
        {
            this.elements.info.html(() => {
                return $(this.elements.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(object)).join('');
            });

            this.loadProductDetail()

            if(loadCashFlow == 0)
            {
                this.elements.modal.find('.nav-tabs').hide()
            }
            else
            {
                this.elements.modal.find('.nav-tabs').show()
                this.loadCashFlow()
            }
        }
        else
        {
            let data = {
                action: this.ajax.detail,
                id: this.data.id,
            }

            request.post(ajax, data).then(function(response)
            {
                if (response.status === 'success')
                {
                    this.elements.info.html(() => {
                        return $(this.elements.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(response.data.item)).join('');
                    });

                    this.loadProductDetail()

                    if(loadCashFlow == 0)
                    {
                        this.elements.modal.find('.nav-tabs').hide()
                    }
                    else
                    {
                        this.elements.modal.find('.nav-tabs').show()
                        this.loadCashFlow()
                    }
                }
            }.bind(this))
        }

        return false
    }

    loadProductDetail()
    {
        this.elements.products.tbody.html('');

        this.elements.products.pagination.html('');

        this.elements.loading.show();

        let data = {
            action    : this.ajax.products,
            page      : this.elements.products.pagination.page,
            limit     : this.elements.products.pagination.limit,
            id        : this.data.id,
        }

        request.post(ajax, data)
            .then(function(response) {

                if (response.status === 'error') SkilldoMessage.response(response);

                if (response.status === 'success')
                {
                    response.data.html = decodeURIComponent(atob(response.data.html).split('').map(function (c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));

                    this.data.pagination.setLimit(response.pagination.limit);

                    this.data.pagination.setTotal(response.pagination.total);

                    this.data.pagination.setCurrentPage(response.pagination.page);

                    this.elements.products.tbody.html(response.data.html);

                    this.elements.products.pagination.html(this.data.pagination.render());

                }

                this.elements.loading.hide();

            }.bind(this))
    }

    loadCashFlow()
    {
        this.elements.cashFlow.tbody.html('')

        let data = {
            action: this.ajax.cashFlow,
            id: this.data.id,
        }

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                if(response.data.length > 0)
                {
                    let targetItems = ''

                    for (const [key, target] of Object.entries(response.data)) {

                        targetItems += (([target].map(() => {

                            target.need_pay_value = SkilldoUtil.formatNumber(target.need_pay_value)

                            target.paid_value = SkilldoUtil.formatNumber(target.paid_value)

                            target.amount = SkilldoUtil.formatNumber(target.amount)

                            return $(this.elements.cashFlow.__templateItem)
                                .html()
                                .split(/\$\{(.+?)\}/g)
                                .map(render(target))
                                .join('');
                        })))
                    }

                    this.elements.cashFlow.tbody.html(targetItems)
                }
            }
        }.bind(this))

        return false
    }

    clickPaginationDetail(button)
    {
        let page = button.data('number');

        if(page !== undefined)
        {
            this.data.pagination.setCurrentPage(page);
            this.loadProductDetail();
        }

        return false;
    }

    events() {

        let handler = this;

        $(document)
            .on('click', '#js_purchase_order_modal_detail .pagination .page-link', function () {
                handler.clickPaginationDetail($(this))
                return false
            })
    }
}

class PurchaseReturnModalDetail
{
    constructor()
    {
        this.ajax = {
            detail: 'StockPurchaseReturnAdminAjax::detail',
            products: 'StockPurchaseReturnAdminAjax::loadProductsDetail',
        }

        let modalId = '#js_purchase_return_modal_detail'

        this.elements = {
            modalId: modalId,
            modal: $(modalId),
            modelAction: new bootstrap.Modal(modalId, {backdrop: "static", keyboard: false}),
            loading: $(`${modalId} .loading`),
            info: $(`${modalId} .js_detail_content`),
            __templateInfo: `#purchase_return_detail_template`,
            products: {
                tbody: $(`#js_purchase_return_detail_products tbody`),
                pagination: $(`${modalId} .pagination`),
                __templateTable: `#purchase_return_detail_table_template`,
                __templateTableItem: `#purchase_return_detail_table_item_template`,
            },
        }

        this.data = {
            id: 0,
            pagination: new Pagination({
                limit: 10,
                page : 1
            }),
        }
    }

    handle(button)
    {
        let object = button.data('bill')

        this.elements.info.html('')

        this.elements.modelAction.show()

        if(object !== undefined && object?.code)
        {
            this.elements.info.html(() => {
                return $(this.elements.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(object)).join('');
            });

            this.loadProductDetail()
        }
        else
        {
            let data = {
                action: this.ajax.detail,
                id: this.data.id,
            }

            request.post(ajax, data).then(function(response)
            {
                if (response.status === 'success')
                {
                    this.elements.info.html(() => {
                        return $(this.elements.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(response.data.item)).join('');
                    });

                    this.loadProductDetail()
                }
            }.bind(this))
        }

        return false
    }

    loadProductDetail()
    {
        this.elements.products.tbody.html('');

        this.elements.products.pagination.html('');

        this.elements.loading.show();

        let data = {
            action    : this.ajax.products,
            page      : this.elements.products.pagination.page,
            limit     : this.elements.products.pagination.limit,
            id        : this.data.id,
        }

        request.post(ajax, data)
            .then(function(response) {

                if (response.status === 'error') SkilldoMessage.response(response);

                if (response.status === 'success')
                {
                    response.data.html = decodeURIComponent(atob(response.data.html).split('').map(function (c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));

                    this.data.pagination.setLimit(response.pagination.limit);

                    this.data.pagination.setTotal(response.pagination.total);

                    this.data.pagination.setCurrentPage(response.pagination.page);

                    this.elements.products.tbody.html(response.data.html);

                    this.elements.products.pagination.html(this.data.pagination.render());

                }

                this.elements.loading.hide();

            }.bind(this))
    }

    clickPaginationDetail(button)
    {
        let page = button.data('number');

        if(page !== undefined)
        {
            this.data.pagination.setCurrentPage(page);
            this.loadProductDetail();
        }

        return false;
    }

    events() {

        let handler = this;

        $(document)
            .on('click', '#js_purchase_return_modal_detail .pagination .page-link', function () {
                handler.clickPaginationDetail($(this))
                return false
            })
    }
}

class WarehouseDetail {

    constructor()
    {
        this.ajax = {
            cashFlow: 'CashFlowAdminAjax::detail',
            purchaseOrder: 'CashFlowAdminAjax::detail',
        }

        this.cashFlow = new CashFlowModalDetail()

        this.purchaseOrder = new PurchaseOrderModalDetail()

        this.purchaseReturn = new PurchaseReturnModalDetail()

        this.data = {
            id: 0,
            type: undefined,
        }
    }

    onClickTarget(button)
    {
        this.data.type = button.data('target')

        this.data.id = button.data('target-id')

        //Xem chi tiết phiếu thu/chi
        if(this.data.type == 'cash-flow')
        {
            this.cashFlow.data.id = this.data.id
            this.cashFlow.handle(button)
        }
        //Xem chi tiết phiếu nhập hàng
        if(this.data.type == 'purchase-order')
        {
            this.purchaseOrder.data.id = this.data.id
            this.purchaseOrder.handle(button)
        }
        //Xem chi tiết phiếu trả hàng nhập
        if(this.data.type == 'purchase-return')
        {
            this.purchaseReturn.data.id = this.data.id
            this.purchaseReturn.handle(button)
        }

        return false
    }

    events() {
        let self = this;

        $(document)
            .on('click', '.js_btn_target', function () {
                return self.onClickTarget($(this))
            })
    }

}
