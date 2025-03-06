class SuppliersPayment
{
    constructor() {

        this.ajax = {
            load: 'SuppliersAdminAjax::loadDebtPayment',
            add: 'SuppliersAdminAjax::addCashFlow'
        }

        this.data = {
            id: 0,
            debt: 0,
            payment: 0,
            balance: 0
        }

        this.elements = {
            modalId: '#js_supplier_debt_modal_payment',
            tableBody: $('#js_supplier_debt_modal_payment table tbody'),
            templateTableItems: $('#supplier_debt_purchase_order_template'),
            divTotalDebt: $('#js_supplier_debt_modal_payment .js_supplier_debt_total'),
            divBalance: $('#js_supplier_debt_modal_payment .js_supplier_debt_balance'),
            inputPayment: $('#js_supplier_debt_modal_payment .js_input_supplier_total_payment'),
            divTotalPaymentPurchase: $('#js_supplier_debt_modal_payment .js_total_payment_purchase'),
            divTotalPaymentSupplier: $('#js_supplier_debt_modal_payment .js_total_payment_supplier'),
        }

        this.elements.modal = $(this.elements.modalId)

        this.elements.modalAction = new bootstrap.Modal(this.elements.modalId, {backdrop: "static", keyboard: false})

        this.purchaseOrders = SkilldoUtil.reducer()
    }

    clickPayment(button)
    {
        let self = this;

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        let id = button.data('id')

        this.data.id = id

        let data = {
            action: this.ajax.load,
            id: id
        }

        this.elements.tableBody.html('')

        this.purchaseOrders.empty()

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                this.data.debt = response.data.item.debt;

                this.data.balance = response.data.item.debt;

                this.elements.divTotalDebt.html(SkilldoUtil.formatNumber(this.data.debt))

                this.elements.divBalance.html(SkilldoUtil.formatNumber(this.data.balance))

                this.elements.inputPayment.val(0)

                for (const [key, item] of Object.entries(response.data.purchaseOrders))
                {
                    item.sub_total = item.sub_total - item.discount;

                    item.payment = item.sub_total - item.total_payment;

                    item.paid = 0;

                    this.purchaseOrders.add(item)

                    let itemN = {...item}

                    itemN.sub_total = SkilldoUtil.formatNumber(itemN.sub_total);

                    itemN.total_payment = SkilldoUtil.formatNumber(itemN.total_payment);

                    itemN.payment = SkilldoUtil.formatNumber(itemN.payment);

                    this.elements.tableBody.append(function() {
                        return self.elements.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(itemN)).join('');
                    });
                }

                this.purchaseOrders.items = this.purchaseOrders.items.reverse();

                this.elements.modalAction.show()
            }

            loading.stop()

        }.bind(this))
    }

    changePayment(input)
    {
        let id = input.data('id')

        let payment = input.val().replace(/,/g, '')

        payment = parseInt(payment)

        if(!this.purchaseOrders.has(id))
        {
            SkilldoMessage.error('Không tìm thấy phiếu nhập bạn đang cập nhật')
            return false
        }

        let item = this.purchaseOrders.get(id)

        if(payment > item.payment)
        {
            payment = item.payment
        }

        if(this.data.payment > 0)
        {
            let paymentCalculator = 0

            for (const [key, purchase] of Object.entries(this.purchaseOrders.items))
            {
                if(purchase.id !== item.id)
                {
                    paymentCalculator += purchase.paid

                    if(paymentCalculator > this.data.payment)
                    {
                        purchase.paid = purchase.paid - (paymentCalculator - this.data.payment)

                        $('.js_input_payment[data-id="'+purchase.id+'"]').val(SkilldoUtil.formatNumber(purchase.paid))

                        this.purchaseOrders.update(purchase)
                    }
                }
            }

            paymentCalculator += payment;

            if(paymentCalculator > this.data.payment)
            {
                payment = payment - (paymentCalculator - this.data.payment)
            }
        }

        item.paid = payment

        this.purchaseOrders.update(item)

        $('.js_input_payment[data-id="'+item.id+'"]').val(SkilldoUtil.formatNumber(item.paid))

        this.calculate()
    }

    changeTotalPayment(input)
    {
        let payment = input.val().replace(/,/g, '')

        payment = parseInt(payment)

        this.data.payment = payment;

        this.data.balance = this.data.debt - payment;

        let paymentCalculator = payment;

        for (const [key, purchase] of Object.entries(this.purchaseOrders.items))
        {
            let paid = 0;

            if(paymentCalculator >= purchase.payment)
            {
                paid = purchase.payment

                paymentCalculator = paymentCalculator - purchase.payment
            }
            else
            {
                paid = paymentCalculator

                paymentCalculator = 0;
            }

            purchase.paid = paid

            $('.js_input_payment[data-id="'+purchase.id+'"]').val(SkilldoUtil.formatNumber(purchase.paid))

            this.purchaseOrders.update(purchase)
        }

        this.calculate()
    }

    calculate()
    {
        let paymentPurchase = 0;

        for (const [key, purchase] of Object.entries(this.purchaseOrders.items))
        {
            paymentPurchase += purchase.paid
        }

        let paymentSupplier = (paymentPurchase >= this.data.payment) ? 0 : this.data.payment - paymentPurchase

        this.data.balance = this.data.debt - this.data.payment;

        this.elements.divTotalPaymentPurchase.html(SkilldoUtil.formatNumber(paymentPurchase))

        this.elements.divTotalPaymentSupplier.html(SkilldoUtil.formatNumber(paymentSupplier))

        this.elements.divBalance.html(SkilldoUtil.formatNumber(this.data.balance))
    }

    clickAdd(button)
    {
        let self = this;

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        let data = {
            action: this.ajax.add,
            id: this.data.id,
            payment: this.data.payment
        }

        data.purchaseOrders = [];

        for (const [key, item] of Object.entries(this.purchaseOrders.items)) {
            if(item.paid > 0)
            {
                data.purchaseOrders.push({
                    id: item.id,
                    payment: item.paid
                })
            }
        }

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                $('#admin_table_suppliers_debt_list #table-form-search button[type="submit"]').trigger('click')

                this.elements.modalAction.hide()
            }

            loading.stop()

        }.bind(this))
    }

    events() {
        let handler = this;

        $(document)
            .on('click', '.js_supplier_debt_btn_payment', function () {
                handler.clickPayment($(this))
                return false;
            })
            .on('keyup', '#js_supplier_debt_modal_payment .js_input_payment', function () {
                handler.changePayment($(this))
                return false;
            })
            .on('keyup', '#js_supplier_debt_modal_payment .js_input_supplier_total_payment', function () {
                handler.changeTotalPayment($(this))
                return false;
            })
            .on('click', '#js_supplier_debt_modal_payment .js_supplier_debt_btn_submit', function () {
                handler.clickAdd($(this))
                return false;
            })
    }
}