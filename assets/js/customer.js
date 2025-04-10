class CustomerPayment
{
    constructor() {

        this.ajax = {
            updateBalance: 'SkdepotCustomerAdminAjax::updateBalance'
        }

        this.data = {
            id: 0,
            debt: $('#admin_table_user_detail_debt_list').data('debt'),
        }

        this.elements = {
            divTotalDebt: $('.js_user_debt_detail_debt_total'),
            balance: {
                modalId: '#js_user_debt_update_balance_modal',
                divTotalDebt: $('#js_user_debt_update_balance_modal .js_user_debt_update_balance_total'),
                inputBalance: $('#js_user_debt_update_balance_modal .js_input_user_debt_update_balance_number'),
                inputNote: $('#js_user_debt_update_balance_modal .js_input_user_debt_update_balance_note'),
            }
        }

        this.elements.balance.modal = $(this.elements.balance.modalId)

        this.elements.balance.modalAction = new bootstrap.Modal(this.elements.balance.modalId, {backdrop: "static", keyboard: false})

        this.events()
    }

    clickBalance(button)
    {
        this.data.id = button.data('id')
        this.elements.balance.divTotalDebt.html(SkilldoUtil.formatNumber(this.data.debt))
        this.elements.balance.inputBalance.html('')
        this.elements.balance.modalAction.show()
    }

    clickUpdateBalance(button)
    {
        let balance = this.elements.balance.inputBalance.val()

        balance = parseInt(balance.replace(/,/g, ''))

        let loading = SkilldoUtil.buttonLoading(button)

        loading.start()

        let data = {
            action: this.ajax.updateBalance,
            id: this.data.id,
            balance: balance,
            note: this.elements.balance.inputNote.val()
        }

        request.post(ajax, data).then(function(response)
        {
            SkilldoMessage.response(response)

            if (response.status === 'success')
            {
                this.data.debt = response.data.debt;

                this.elements.divTotalDebt.html(SkilldoUtil.formatNumber(this.data.debt))

                $('#admin_table_user_detail_debt_list #table-form-search button[type="submit"]').trigger('click')

                this.elements.balance.modalAction.hide()
            }

            loading.stop()

        }.bind(this))
    }

    events() {
        let handler = this;

        $(document)
            //Update Balance
            .on('click', '.js_user_debt_btn_update_balance', function () {
                handler.clickBalance($(this))
                return false;
            })
            .on('click', '#js_user_debt_update_balance_modal .js_user_debt_update_balance_btn_submit', function () {
                handler.clickUpdateBalance($(this))
                return false;
            })
    }
}