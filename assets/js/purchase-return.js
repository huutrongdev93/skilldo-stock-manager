class PurchaseReturnIndexHandle extends WarehouseIndexHandle
{
    constructor() {
        super({
            module: 'purchase_return',
            ajax: {
                loadProducts: 'StockPurchaseReturnAdminAjax::loadProductsDetail',
                print: 'StockPurchaseReturnAdminAjax::print'
            }
        });
    }

    events() {

        let handle = this;

        $(document)
            .on('click', '.js_purchase_return_btn_print', function () {
                handle.clickButtonPrint($(this))
                return false
            })
    }
}

class PurchaseReturnNewHandle extends WarehouseNewHandle
{
    constructor()
    {
        const module = 'purchase_return'

        super({
            module: module,
            ajax: {
                loadProducts: 'StockPurchaseReturnAdminAjax::loadProductsEdit',
                addDraft: 'StockPurchaseReturnAdminAjax::addDraft',
                saveDraft: 'StockPurchaseReturnAdminAjax::saveDraft',
                add: 'StockPurchaseReturnAdminAjax::add',
                save: 'StockPurchaseReturnAdminAjax::save',
                import: 'StockPurchaseReturnAdminAjax::import',
            }
        });

        this.elements.inputDiscount = $(`#js_${module}_form input[name="return_discount"]`);
        this.elements.inputPayment = $(`#js_${module}_form input[name="total_payment"]`);
        this.elements.subTotal = $(`.js_${module}_cost_total`);
        this.elements.paymentTotal = $(`.js_${module}_total_payment`)

        this.subTotal = 0;

        this.source = $(`#${module}_source`).val()

        if(this.source == 'products')
        {
            let data = SkilldoUtil.storage.get('stock_purchase_return_add_source')

            if(data?.product_id)
            {
                this.action = 'source-product'

                this.idEdit = data?.product_id

                this.loadProduct()

                //SkilldoUtil.storage.remove('stock_purchase_return_add_source')

                this.action = 'add'

                this.idEdit = 0
            }
        }
    }

    productItem(item) {

        if(!item?.quantity || item.quantity === 0 || item.quantity === undefined)
        {
            item.quantity = 1;
        }

        if(!item?.cost)
        {
            item.cost = SkilldoUtil.formatNumber(item.price_cost);
        }

        if(!item?.price)
        {
            item.price = item.price_cost;
        }

        item.subtotal = SkilldoUtil.formatNumber(item.price * item.quantity);

        return item
    }

    payloadData(data)
    {
        data.return_discount = parseFloat(data.return_discount.replace(/,/g, ''))

        data.total_payment = parseFloat(data.total_payment.replace(/,/g, ''))

        data.products = [];

        for (const [key, item] of Object.entries(this.products.items)) {
            data.products.push({
                id: item.id,
                code: item.code,
                title: item.title,
                attribute_label: item?.attribute_label,
                quantity: item.quantity,
                price: item.price,
                cost: parseFloat(item.cost.replace(/,/g, ''))
            })
        }

        return data;
    }

    calculate()
    {
        this.subTotal = 0;

        for (const [key, item] of Object.entries(this.products.items)) {
            this.subTotal += item.price * item.quantity;
        }

        this.elements.subTotal.html(SkilldoUtil.formatNumber(this.subTotal))

        let discount = parseFloat(this.elements.inputDiscount.val().replace(/,/g, ''));

        if(discount > this.subTotal)
        {
            discount = this.subTotal;

            this.elements.inputDiscount.val(SkilldoUtil.formatNumber(discount))
        }

        let payment = this.subTotal - discount;

        let paid = parseFloat(this.elements.inputPayment.val().replace(/,/g, ''));

        if(paid > payment)
        {
            paid = payment;

            this.elements.inputPayment.val(SkilldoUtil.formatNumber(paid))
        }

        this.elements.paymentTotal.html(SkilldoUtil.formatNumber(payment - paid))
    }

    changeDiscount(element)
    {
        this.calculate()
    }

    changeQuantity(element)
    {
        let id = element.closest('.js_column').data('id')

        let quantity = element.val().replace(/,/g, '')

        quantity = parseInt(quantity)

        if(quantity < 1)
        {
            quantity = 1;

            element.val(quantity)
        }

        let item = this.products.get(id)

        if(item)
        {
            if(item.quantity !== quantity)
            {
                item.quantity = quantity;

                item.subtotal = quantity * item.price;

                this.products.update(item)

                element.closest('.js_column').find('.js_input_subtotal').html(SkilldoUtil.formatNumber(item.subtotal))

                this.calculate()
            }
        }
    }

    changePrice(element)
    {
        let id = element.closest('.js_column').data('id')

        let price = element.val()

        price = parseInt(price.replace(/,/g, ''))

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

                item.subtotal = price * item.quantity;

                this.products.update(item)

                element.closest('.js_column').find('.js_input_subtotal').html(SkilldoUtil.formatNumber(item.subtotal))

                this.calculate()
            }
        }
    }

    saveSuccess() {
        window.location.href = 'admin/purchase-return'
    }

    events() {

        let handler = this

        $(document)
            .on('click', '.autocomplete-suggestions-results .product-item', function () {
                handler.clickAddProduct($(this)) //function search
            })
            .on('click', '.js_purchase_return_btn_delete', function () {
                handler.clickDeleteProduct($(this)) //function search
            })
            .on('keyup', '#js_purchase_return_form input[name="return_discount"]', function () {
                handler.changeDiscount($(this)) //function search
            })
            .on('keyup', '.js_input_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('keyup', '.js_input_price', function () {
                handler.changePrice($(this)) //function search
            })
            .on('click', '#js_purchase_return_btn_draft', function () {
                handler.clickSaveDraft($(this))
                return false
            })
            .on('click', '#js_purchase_return_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
            .on('change', '#js_purchase_return_import_input', function () {
                $('#js_purchase_return_import_form').trigger('submit')
                return false
            })
            .on('submit', '#js_purchase_return_import_form', function () {
                handler.import($(this))
                return false
            })
    }
}