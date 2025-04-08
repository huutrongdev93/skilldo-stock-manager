class OrderReturnIndexHandle extends WarehouseIndexHandle
{
    constructor() {
        super({
            module: 'order_return',
            ajax: {
                loadProducts: 'OrderReturnAdminAjax::loadProductsDetail',
                print: 'OrderReturnAdminAjax::print',
                histories: 'OrderReturnAdminAjax::histories'
            }
        });

        this.detail.history = {
            tbody: $(`.js_order_return_detail_tab_content[data-tab="history"] tbody`),
            loading: $(`.js_order_return_detail_tab_content[data-tab="history"] .loading`),
            templateName: `#js_order_return_detail_history_template`,
        }
    }

    loadHistories()
    {
        this.detail.history.tbody.html('');

        let self = this

        this.detail.history.loading.show();

        let data = {
            action    : this.ajax.histories,
            id : this.id,
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

                    this.detail.history.tbody.html(historyContent);
                }

                this.detail.history.loading.hide();

            }.bind(this))
    }

    events() {

        let handle = this;

        $(document)
            .on('click', '.js_order_return_btn_detail', function () {
                handle.clickButtonDetail($(this))
            })
            .on('click', '#js_order_return_modal_detail .pagination .page-link', function () {
                handle.clickPaginationDetail($(this))
                return false
            })
            .on('click', '.js_order_return_btn_print', function () {
                handle.clickButtonPrint($(this))
                return false
            })
            .on('click', '.js_order_return_detail_tabs .nav-link', function () {

                let tabId = $(this).attr('id');

                // Xóa class active khỏi tất cả các tab
                $('.js_order_return_detail_tabs .nav-link').removeClass('active');

                // Thêm class active cho tab vừa click
                $(this).addClass('active');

                // Ẩn tất cả các tab content
                $('.js_order_return_detail_tab_content').hide();

                // Hiển thị tab content tương ứng với data-tab khớp với id
                $('.js_order_return_detail_tab_content[data-tab="' + tabId + '"]').show();

                // Cập nhật aria-selected
                $('.nav-link').attr('aria-selected', 'false');

                $(this).attr('aria-selected', 'true');

                if(tabId === 'history')
                {
                    handle.loadHistories()
                }

                return false
            })
    }
}

class OrderReturnNewHandle
{
    constructor() {

        const module = 'order_return'

        this.elements = {
            form: $(`#js_${module}_form`),
            table: $(`.js_${module}_table`),
            tableBody: $(`#js_${module}_products table tbody`),
            templateTableItems: $(`#${module}_new_product_table_item_template`),

            inputDiscount: $(`#js_${module}_form input[name="discount"]`),
            inputSurcharge: $(`#js_${module}_form input[name="surcharge"]`),
            inputPaid: $(`#js_${module}_form input[name="totalPaid"]`),

            totalQuantitySell: $(`.js_${module}_total_quantity_sell`),
            totalPriceSell: $(`.js_${module}_total_price_sell`),
            totalQuantity: $(`.js_${module}_total_quantity`),
            totalPrice: $(`.js_${module}_total_price`),
            totalPayment: $(`.js_${module}_total_payment`)
        }

        this.isChangePaid = false;

        this.isChangeDiscount = false;

        this.products = SkilldoUtil.reducer();

        this.order = this.elements.form.data('order')

        let items = this.elements.form.data('order-items')

        this.elements.tableBody.find('.no-items').remove()

        for (const [key, item] of Object.entries(items))
        {
            let itemN = this.productItem(item)

            this.products.add(itemN)

            this.elements.tableBody.append([itemN].map(function(item) {
                return this.elements.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
            }.bind(this)));

            this.calculate()
        }
    }

    productItem(item)
    {
        return {
            id: item.id,
            product_id: item.product_id,
            code: item.code,
            fullname: item.fullname,
            title: item.title,
            attribute_label: item.attributes,
            discount: item.discount,
            cost: item.cost,
            quantity_sell: item.quantity,
            price_sell: parseInt(item.price.replace(/,/g, '')),
            quantity: 0,
            price: parseInt(item.price.replace(/,/g, '')),
            subtotal: 0,
        }
    }

    payloadData(data)
    {
        data.discount = parseFloat(data.discount.replace(/,/g, ''))

        data.surcharge = parseFloat(data.surcharge.replace(/,/g, ''))

        data.totalPaid = parseFloat(data.totalPaid.replace(/,/g, ''))

        data.products = [];

        for (const [key, item] of Object.entries(this.products.items)) {
            data.products.push({
                id: item.id,
                product_id: item.product_id,
                code: item.code,
                title: item.title,
                attribute_label: item?.attribute_label,
                cost: item.cost,
                quantity_sell: item.quantity_sell,
                price_sell: item.price_sell,
                quantity: item.quantity,
                price: item.price
            })
        }

        return data;
    }

    changeQuantity(element)
    {
        let id = element.closest('.js_column').data('id')

        let quantity = element.val().replace(/,/g, '')

        quantity = parseInt(quantity)

        if(quantity < 1)
        {
            quantity = 0;

            element.val(quantity)
        }

        let item = this.products.get(id)

        if(item)
        {
            if(quantity > item.quantity_sell)
            {
                quantity = item.quantity_sell;

                element.val(quantity)
            }

            if(item.quantity !== quantity)
            {
                item.quantity = quantity;

                item.subtotal = quantity*item.price;

                this.products.update(item)

                this.calculate()
            }
        }
    }

    changePrice(element)
    {
        let column = element.closest('.js_column')

        let id = column.data('id')

        let price = element.val().replace(/,/g, '')

        price = parseInt(price)

        if(price < 1)
        {
            price = 1;

            element.val(price)
        }

        let item = this.products.get(id)

        if(item)
        {
            if(item.price !== price)
            {
                item.price = price;

                item.subtotal = price*item.quantity;

                this.products.update(item)

                column.find('.js_input_subtotal').html(SkilldoUtil.formatNumber(item.subtotal))

                this.calculate()
            }
        }
    }

    calculate() {

        let totalQuantitySell = 0;

        let totalPriceSell = 0;

        let totalQuantity = 0;

        let totalPrice = 0;

        let discount = 0;

        for (const [key, item] of Object.entries(this.products.items)) {
            totalQuantitySell += item.quantity_sell;
            totalPriceSell += item.quantity_sell * item.price_sell;
            totalQuantity += item.quantity;
            totalPrice += item.quantity * item.price;
            discount += item.discount*item.quantity;
        }

        if(this.isChangeDiscount)
        {
            discount = parseFloat(this.elements.inputDiscount.val().replace(/,/g, ''));
        }

        let surcharge = parseFloat(this.elements.inputSurcharge.val().replace(/,/g, ''));

        let paid = parseFloat(this.elements.inputPaid.val().replace(/,/g, ''));

        if(discount > (totalPrice + surcharge))
        {
            discount = totalPrice + surcharge;
        }

        this.elements.inputDiscount.val(SkilldoUtil.formatNumber(discount))

        let totalPayment = totalPrice - discount + surcharge;

        if(this.isChangePaid === false)
        {
            this.elements.inputPaid.val(SkilldoUtil.formatNumber(totalPayment))
        }

        this.elements.totalQuantitySell.html(SkilldoUtil.formatNumber(totalQuantitySell))
        this.elements.totalPriceSell.html(SkilldoUtil.formatNumber(totalPriceSell))
        this.elements.totalQuantity.html(SkilldoUtil.formatNumber(totalQuantity))
        this.elements.totalPrice.html(SkilldoUtil.formatNumber(totalPrice))
        this.elements.totalPayment.html(SkilldoUtil.formatNumber(totalPayment))
    }

    clickSave(element)
    {
        if(this.products.items.length === 0)
        {
            SkilldoMessage.error('Không có sản phẩm nào')
        }

        let loader = SkilldoUtil.buttonLoading(element)

        loader.start()

        let data = this.payloadData(this.elements.form.serializeJSON())

        data.action = 'OrderReturnAdminAjax::save'

        data.orderId = this.order.id

        request.post(ajax, data, {
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function(response)
        {
            SkilldoMessage.response(response)

            if(response.status == 'success')
            {
                window.location.href = 'admin/order-return'
            }
            else
            {
                loader.stop()
            }
        }.bind(this))

        return false
    }

    events() {

        let handler = this

        $(document)
            .on('keyup', '#js_order_return_form input[name="discount"]', function () {
                handler.isChangeDiscount = true;
                handler.calculate() //function search
            })
            .on('keyup', '#js_order_return_form input[name="surcharge"]', function () {
                handler.calculate() //function search
            })
            .on('keyup', '#js_order_return_form input[name="totalPaid"]', function () {
                handler.isChangePaid = true;
                handler.calculate() //function search
            })
            .on('keyup', '.js_input_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('keyup', '.js_input_price', function () {
                handler.changePrice($(this)) //function search
            })
            .on('click', '#js_order_return_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
    }
}