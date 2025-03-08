class SuppliersIndex
{
    constructor() {
        this.elements = {
            status: {
                modal: $('#js_supplier_modal_status'),
                modalAction: new bootstrap.Modal('#js_supplier_modal_status', {backdrop: "static", keyboard: false}),
                inputStatus: $('#js_supplier_modal_status select[name="supplierStatus"]')
            }
        }

        this.data = {
            status: {
                id: 0
            }
        }
    }

    clickStatus(element) {

        this.data.status.id = element.attr('data-id');

        let status = element.attr('data-status');

        this.elements.status.inputStatus.val(status).trigger('change')

        this.elements.status.modalAction.show();
    }

    clickSaveStatus(element) {

        let loading = SkilldoUtil.buttonLoading(element)

        let data = {
            action: 'SuppliersAdminAjax::status',
            status: this.elements.status.inputStatus.val(),
            id: this.data.status.id
        }

        loading.start()

        request
            .post(ajax, data)
            .then(function (response) {

                SkilldoMessage.response(response);

                loading.stop();

                if(response.status === 'success') {

                    $('.tr_' + this.data.status.id).find('.column-status').html(response.data);

                    this.elements.status.modalAction.hide();
                }
            }.bind(this))
            .catch(function (error) {
                loading.stop();
            });

        return false;
    }

    events()
    {
        let handler = this;

        $(document)
            .on('click', '.js_supplier_btn_status', function () {
                handler.clickStatus($(this))
                return false;
            })
            .on('click', '.js_supplier_btn_status_save', function () {
                handler.clickSaveStatus($(this))
                return false;
            })
    }
}

class SuppliersPayment
{
    constructor() {

        this.ajax = {
            load: 'SuppliersAdminAjax::loadDebtPayment',
            add: 'SuppliersAdminAjax::addCashFlow',
            updateBalance: 'SuppliersAdminAjax::updateBalance'
        }

        this.data = {
            id: 0,
            supplier: $('#js_supplier_detail_data').data('supplier'),
            payment: {
                debt: 0,
                payment: 0,
                balance: 0,
            },
        }

        this.elements = {
            divTotalDebt: $('.js_supplier_detail_debt_total'),
            payment: {
                modalId: '#js_supplier_debt_modal_payment',
                tableBody: $('#js_supplier_debt_modal_payment table tbody'),
                templateTableItems: $('#supplier_debt_purchase_order_template'),
                divTotalDebt: $('#js_supplier_debt_modal_payment .js_supplier_debt_total'),
                divBalance: $('#js_supplier_debt_modal_payment .js_supplier_debt_balance'),
                inputPayment: $('#js_supplier_debt_modal_payment .js_input_supplier_total_payment'),
                divTotalPaymentPurchase: $('#js_supplier_debt_modal_payment .js_total_payment_purchase'),
                divTotalPaymentSupplier: $('#js_supplier_debt_modal_payment .js_total_payment_supplier'),
            },
            balance: {
                modalId: '#js_supplier_update_balance_modal',
                divTotalDebt: $('#js_supplier_update_balance_modal .js_supplier_update_balance_total'),
                inputBalance: $('#js_supplier_update_balance_modal .js_input_supplier_update_balance_number'),
                inputNote: $('#js_supplier_update_balance_modal .js_input_supplier_update_balance_note'),
            }
        }

        this.elements.payment.modal = $(this.elements.payment.modalId)

        this.elements.payment.modalAction = new bootstrap.Modal(this.elements.payment.modalId, {backdrop: "static", keyboard: false})

        this.elements.balance.modal = $(this.elements.balance.modalId)

        this.elements.balance.modalAction = new bootstrap.Modal(this.elements.balance.modalId, {backdrop: "static", keyboard: false})

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

        this.elements.payment.tableBody.html('')

        this.purchaseOrders.empty()

        this.elements.payment.modal.find('.js_supplier_debt_modal_name').html(this.data.supplier.code + ' - ' + this.data.supplier.name)

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                this.data.payment.debt = response.data.item.debt;

                this.data.payment.balance = response.data.item.debt;

                this.elements.payment.divTotalDebt.html(SkilldoUtil.formatNumber(this.data.payment.debt))

                this.elements.payment.divBalance.html(SkilldoUtil.formatNumber(this.data.payment.balance))

                this.elements.payment.inputPayment.val(0)

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

                    this.elements.payment.tableBody.append(function() {
                        return self.elements.payment.templateTableItems.html().split(/\$\{(.+?)\}/g).map(render(itemN)).join('');
                    });
                }

                this.purchaseOrders.items = this.purchaseOrders.items.reverse();

                this.elements.payment.modalAction.show()
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

        if(this.data.payment.payment > 0)
        {
            let paymentCalculator = 0

            for (const [key, purchase] of Object.entries(this.purchaseOrders.items))
            {
                if(purchase.id !== item.id)
                {
                    paymentCalculator += purchase.paid

                    if(paymentCalculator > this.data.payment.payment)
                    {
                        purchase.paid = purchase.paid - (paymentCalculator - this.data.payment.payment)

                        $('.js_input_payment[data-id="'+purchase.id+'"]').val(SkilldoUtil.formatNumber(purchase.paid))

                        this.purchaseOrders.update(purchase)
                    }
                }
            }

            paymentCalculator += payment;

            if(paymentCalculator > this.data.payment.payment)
            {
                payment = payment - (paymentCalculator - this.data.payment.payment)
            }
        }

        item.paid = payment

        this.purchaseOrders.update(item)

        $('.js_input_payment[data-id="'+item.id+'"]').val(SkilldoUtil.formatNumber(item.paid))

        this.calculatePayment()
    }

    changeTotalPayment(input)
    {
        let payment = input.val().replace(/,/g, '')

        payment = parseInt(payment)

        this.data.payment.payment = payment;

        this.data.payment.balance = this.data.payment.debt - payment;

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

        this.calculatePayment()
    }

    calculatePayment()
    {
        let paymentPurchase = 0;

        for (const [key, purchase] of Object.entries(this.purchaseOrders.items))
        {
            paymentPurchase += purchase.paid
        }

        let paymentSupplier = (paymentPurchase >= this.data.payment.payment) ? 0 : this.data.payment.payment - paymentPurchase

        this.data.payment.balance = this.data.payment.debt - this.data.payment.payment;

        this.elements.payment.divTotalPaymentPurchase.html(SkilldoUtil.formatNumber(paymentPurchase))

        this.elements.payment.divTotalPaymentSupplier.html(SkilldoUtil.formatNumber(paymentSupplier))

        this.elements.payment.divBalance.html(SkilldoUtil.formatNumber(this.data.payment.balance))
    }

    clickAddPayment(button)
    {
        let self = this;

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        let data = {
            action: this.ajax.add,
            id: this.data.id,
            payment: this.data.payment.payment
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

                this.elements.payment.modalAction.hide()
            }

            loading.stop()

        }.bind(this))
    }

    clickBalance(button)
    {
        this.elements.balance.modal.find('.js_supplier_update_balance_modal_name').html(this.data.supplier.code + ' - ' + this.data.supplier.name)
        this.elements.balance.divTotalDebt.html(SkilldoUtil.formatNumber(this.data.supplier.debt))
        this.elements.balance.inputBalance.html('')
        this.elements.balance.modalAction.show()
    }

    clickUpdateBalance(button)
    {
        let self = this;

        let balance = this.elements.balance.inputBalance.val()

        balance = parseInt(balance.replace(/,/g, ''))

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        let data = {
            action: this.ajax.updateBalance,
            id: this.data.supplier.id,
            balance: balance,
            note: this.elements.balance.inputNote.val()
        }

        request.post(ajax, data).then(function(response)
        {
            SkilldoMessage.response(response)

            if (response.status === 'success')
            {
                this.data.supplier.debt = response.data.debt;

                this.elements.divTotalDebt.html(SkilldoUtil.formatNumber(this.data.supplier.debt))

                $('#admin_table_suppliers_debt_list #table-form-search button[type="submit"]').trigger('click')

                this.elements.balance.modalAction.hide()
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
                handler.clickAddPayment($(this))
                return false;
            })
            //Update Balance
            .on('click', '.js_supplier_btn_update_balance', function () {
                handler.clickBalance($(this))
                return false;
            })
            .on('click', '#js_supplier_update_balance_modal .js_supplier_update_balance_btn_submit', function () {
                handler.clickUpdateBalance($(this))
                return false;
            })
    }
}