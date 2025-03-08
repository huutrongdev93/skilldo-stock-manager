class PurchaseOrderIndexHandle extends WarehouseIndexHandle
{
    constructor() {
        super({
            module: 'purchase_order',
            ajax: {
                loadProducts: 'StockPurchaseOrderAdminAjax::loadProductsDetail',
                print: 'StockPurchaseOrderAdminAjax::print',
                cashFlow: 'StockPurchaseOrderAdminAjax::loadCashFlowDetail'
            }
        });
    }

    events() {

        let handler = this;

        $(document)
            .on('click', '.js_purchase_order_btn_print', function () {
                handler.clickButtonPrint($(this))
                return false
            })
    }
}

class PurchaseOrderNewHandle extends WarehouseNewHandle
{
    constructor()
    {
        const module = 'purchase_order'

        super({
            module: module,
            ajax: {
                loadProducts: 'StockPurchaseOrderAdminAjax::loadProductsEdit',
                addDraft: 'StockPurchaseOrderAdminAjax::addDraft',
                saveDraft: 'StockPurchaseOrderAdminAjax::saveDraft',
                add: 'StockPurchaseOrderAdminAjax::add',
                save: 'StockPurchaseOrderAdminAjax::save',
                import: 'StockPurchaseOrderAdminAjax::import',
            }
        });

        this.elements.inputDiscount = $(`#js_${module}_form input[name="discount"]`);
        this.elements.inputPayment = $(`#js_${module}_form input[name="total_payment"]`);
        this.elements.subTotal = $(`.js_${module}_cost_total`);
        this.elements.paymentTotal = $(`.js_${module}_total_payment`)

        this.subTotal = 0;
    }

    productItem(item)
    {
        if(!item?.quantity || item.quantity === 0 || item.quantity === undefined)
        {
            item.quantity = 1;
        }

        if(!item?.price)
        {
            item.price = item.price_cost;
        }

        return item
    }

    payloadData(data)
    {
        data.discount = parseFloat(data.discount.replace(/,/g, ''))

        data.total_payment = parseFloat(data.total_payment.replace(/,/g, ''))

        data.products = [];

        for (const [key, item] of Object.entries(this.products.items)) {
            data.products.push({
                id: item.id,
                code: item.code,
                title: item.title,
                attribute_label: item?.attribute_label,
                quantity: item.quantity,
                price: item.price
            })
        }

        return data;
    }

    calculate()
    {
        this.subTotal = 0;

        for (const [key, item] of Object.entries(this.products.items)) {
            this.subTotal += item.quantity * item.price;
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

    changePayment(element)
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

                this.products.update(item)

                this.calculate()
            }
        }
    }

    changePrice(element)
    {
        let id = element.closest('.js_column').data('id')

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

                this.products.update(item)

                this.calculate()
            }
        }
    }

    saveSuccess() {
        window.location.href = 'admin/purchase-order'
    }

    events() {

        let handler = this

        $(document)
            .on('click', '.autocomplete-suggestions-results .product-item', function () {
                handler.clickAddProduct($(this)) //function search
            })
            .on('click', '.js_purchase_order_btn_delete', function () {
                handler.clickDeleteProduct($(this)) //function search
            })
            .on('keyup', '#js_purchase_order_form input[name="discount"]', function () {
                handler.changeDiscount($(this)) //function search
            })
            .on('keyup', '#js_purchase_order_form input[name="total_payment"]', function () {
                handler.changePayment($(this)) //function search
            })
            .on('keyup', '.js_input_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('keyup', '.js_input_price', function () {
                handler.changePrice($(this)) //function search
            })
            .on('click', '#js_purchase_order_btn_draft', function () {
                handler.clickSaveDraft($(this))
                return false
            })
            .on('click', '#js_purchase_order_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
            .on('change', '#js_purchase_order_import_input', function () {
                $('#js_purchase_order_import_form').trigger('submit')
                return false
            })
            .on('submit', '#js_purchase_order_import_form', function () {
                handler.import($(this))
                return false
            })
    }
}