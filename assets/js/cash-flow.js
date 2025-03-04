class CashFlowIndexHandle
{
    constructor() {

        this.data = {
            id: 0
        }

        this.ajax = {
            partnerAdd: 'CashFlowAdminAjax::partnerAdd',
            add: 'CashFlowAdminAjax::add',
            detail: 'CashFlowAdminAjax::detail',
        }

        this.elements = {
            addReceipt: {
                modalId: '#js_cash_flow_receipt_modal_add',
                modal: $('#js_cash_flow_receipt_modal_add'),
                modelAction: new bootstrap.Modal('#js_cash_flow_receipt_modal_add', {backdrop: "static", keyboard: false})
            },
            addPayment: {
                modalId: '#js_cash_flow_payment_modal_add',
                modal: $('#js_cash_flow_payment_modal_add'),
                modelAction: new bootstrap.Modal('#js_cash_flow_payment_modal_add', {backdrop: "static", keyboard: false})
            },
            add: null,
            partner: {
                modalId: '#js_cash_flow_partner_modal_add',
                modal: $('#js_cash_flow_partner_modal_add'),
                modelAction: new bootstrap.Modal('#js_cash_flow_partner_modal_add', {backdrop: "static", keyboard: false}),
                form: $('#js_cash_flow_partner_form')
            },
            detail: {
                modalId: `#js_cash_flow_modal_detail`,
                modal: $(`#js_cash_flow_modal_detail`),
                modelAction: new bootstrap.Modal('#js_cash_flow_modal_detail', {backdrop: "static", keyboard: false}),
                loading: $(`#js_cash_flow_modal_detail .loading`),
                info: $(`#js_cash_flow_modal_detail .js_detail_content`),
                __templateInfo: `#cash_flow_detail_template`,
            }
        }

        this.type = null;

    }

    events()
    {
        const handler = this;

        $(document)
            .on('click', '.js_cash_flow_btn_add', function () {
                handler.clickBtnAdd($(this))
                return false;
            })
            .on('click', '.js_partner_add', function () {
                handler.clickBtnAddPartner($(this))
                return false;
            })
            .on('submit', '#js_cash_flow_partner_form', function () {
                handler.addPartner($(this))
                return false;
            })
            .on('change', 'select[name="partner_type"]', function () {
                handler.changePartnerType($(this))
                return false;
            })
            .on('submit', '.js_cash_flow_modal_add', function () {
                handler.add($(this))
                return false;
            })
            .on('click', '.js_cash_flow_btn_detail', function () {
                handler.clickButtonDetail($(this))
            })

    }

    clickBtnAdd(button) {

        this.type = button.data('type')

        if(this.type === 'receipt')
        {
            this.elements.add = this.elements.addReceipt
        }

        if(this.type === 'payment')
        {
            this.elements.add = this.elements.addPayment
        }

        if(this.elements.add === null)
        {
            SkilldoMessage.error('Không xác định được loại phiếu')

            return false;
        }

        this.elements.add.modelAction.show()
    }

    clickBtnAddPartner(button)
    {
        this.elements.partner.modelAction.show()
    }

    addPartner(form)
    {
        let loading = SkilldoUtil.buttonLoading(form.find('button[type="submit"]'))

        loading.start()

        let data = form.serializeJSON();

        data.action = this.ajax.partnerAdd;

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                this.elements.partner.modelAction.hide()

                let item = `<div data-id="${response.data.id}" class="popover_advance__item_result popover_advance__item_result_${response.data.id} clearfix">
                    <div class="item">
                        <label><input type="checkbox" name="user" value="${response.data.id}" class="input-popover-advance-value" checked></label>
                        <div class="item__info">
                            <div class="item__name"><strong>${response.data.name}</strong></div>
                            <div class="item__name">${response.data.phone}</div>
                        </div>
                        <div class="item__action">
                            <button class="btn btn-red item__btn_delete" type="button"><i class="fal fa-times"></i></button>
                        </div>
                    </div>
                </div>`

                this.elements.add.modal.find('#partner_value_other .popover_advance__list').html(item)
            }

            loading.stop()

        }.bind(this))
    }

    changePartnerType(element)
    {
        let partnerType = element.val()

        this.elements.add.modal.find('.js_partner_value_input').hide()

        this.elements.add.modal.find('.js_partner_value_' + partnerType).show()

        if(partnerType == 'O')
        {
            this.elements.add.modal.find('.js_partner_add').show()
        }
        else
        {
            this.elements.add.modal.find('.js_partner_add').hide()
        }
    }

    add(form)
    {
        let loading = SkilldoUtil.buttonLoading(form.find('button[type="submit"]'))

        loading.start()

        let data = form.serializeJSON();

        data.action = this.ajax.add;

        data.type = this.type;

        data.amount = parseFloat(data.amount.replace(/,/g, ''))

        request.post(ajax, data).then(function(response)
        {
            if (response.status === 'success')
            {
                this.elements.add.modelAction.hide()

                $('button.reload').trigger('click')
            }
            else {
                SkilldoMessage.response(response)
            }

            loading.stop()

        }.bind(this))
    }

    clickButtonDetail(button) {

        let id = button.data('id')

        if(this.id != id)
        {
            this.id = id

            let data = {
                action: this.ajax.detail,
                id: id
            }

            request.post(ajax, data).then(function(response)
            {
                if (response.status === 'success')
                {
                    this.elements.detail.info.html(() => {
                        return $(this.elements.detail.__templateInfo).html().split(/\$\{(.+?)\}/g).map(render(response.data.item)).join('');
                    });
                }
            }.bind(this))
        }
        this.elements.detail.modelAction.show()
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