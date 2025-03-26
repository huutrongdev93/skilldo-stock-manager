class TransferIndexHandle extends WarehouseIndexHandle
{
    constructor() {
        super({
            module: 'transfer',
            ajax: {
                loadProducts: 'TransferAdminAjax::loadProductsDetail',
                print: 'TransferAdminAjax::print'
            }
        });
    }

    clickCopy(button)
    {
        let formBranch = button.data('form-branch');

        if(formBranch !== 1)
        {
            SkilldoMessage.error('Không thể sao chép phiếu chuyển hàng thuộc chi nhánh khác')

            return false
        }

        return true;
    }

    events() {

        let handle = this;

        $(document)
            .on('click', '.js_transfer_btn_detail', function () {
                handle.clickButtonDetail($(this))
            })
            .on('click', '#js_transfer_modal_detail .pagination .page-link', function () {
                handle.clickPaginationDetail($(this))
                return false
            })
            .on('click', '.js_transfer_btn_print', function () {
                handle.clickButtonPrint($(this))
                return false
            })
            .on('click', '.js_transfer_btn_clone', function () {
                return handle.clickCopy($(this))
            })
    }
}

class TransferSendNewHandle extends WarehouseNewHandle
{
    constructor()
    {
        const module = 'transfer'

        super({
            module: module,
            ajax: {
                loadProducts: 'TransferAdminAjax::loadProductsEdit',
                addDraft: 'TransferAdminAjax::addDraft',
                saveDraft: 'TransferAdminAjax::saveDraft',
                add: 'TransferAdminAjax::sendAdd',
                save: 'TransferAdminAjax::sendSave',
                import: 'TransferAdminAjax::import',
            }
        });

        this.elements.totalQuantity = $(`.js_${module}_total_quantity`);

        this.totalQuantity = 0;
    }

    productItem(item) {

        if(!item?.send_quantity || item.send_quantity === 0 || item.send_quantity === undefined)
        {
            item.send_quantity = 1;
        }

        if(!item?.price)
        {
            item.price = item.price_cost;
        }

        item.send_price = SkilldoUtil.formatNumber(item.price * item.send_quantity);

        return item
    }

    payloadData(data)
    {
        data.products = [];

        for (const [key, item] of Object.entries(this.products.items)) {
            data.products.push({
                id: item.id,
                code: item.code,
                title: item.title,
                attribute_label: item?.attribute_label,
                send_quantity: item.send_quantity,
                price: item.price,
            })
        }

        return data;
    }

    calculate()
    {
        this.totalQuantity = 0;

        for (const [key, item] of Object.entries(this.products.items)) {
            this.totalQuantity += item.send_quantity;
        }

        this.elements.totalQuantity.html(SkilldoUtil.formatNumber(this.totalQuantity))
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
                item.send_quantity = quantity;

                item.send_price = quantity * item.price;

                this.products.update(item)

                element.closest('.js_column').find('.js_input_send_price').html(SkilldoUtil.formatNumber(item.send_price))

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

                item.send_price = price * item.send_quantity;

                this.products.update(item)

                element.closest('.js_column').find('.js_input_send_price').html(SkilldoUtil.formatNumber(item.send_price))

                this.calculate()
            }
        }
    }

    saveSuccess() {
        window.location.href = 'admin/transfers'
    }

    events() {

        let handler = this

        $(document)
            .on('click', '.autocomplete-suggestions-results .product-item', function () {
                handler.clickAddProduct($(this)) //function search
            })
            .on('click', '.js_transfer_btn_delete', function () {
                handler.clickDeleteProduct($(this)) //function search
            })
            .on('keyup', '.js_input_send_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('keyup', '.js_input_price', function () {
                handler.changePrice($(this)) //function search
            })
            .on('click', '#js_transfer_btn_draft', function () {
                handler.clickSaveDraft($(this))
                return false
            })
            .on('click', '#js_transfer_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
            .on('change', '#js_transfer_import_input', function () {
                $('#js_transfer_import_form').trigger('submit')
                return false
            })
            .on('submit', '#js_transfer_import_form', function () {
                handler.import($(this))
                return false
            })
    }
}

class TransferReceiveNewHandle extends WarehouseNewHandle
{
    constructor()
    {
        const module = 'transfer_receive'

        super({
            module: module,
            ajax: {
                loadProducts: 'TransferAdminAjax::loadProductsEdit',
                saveDraft: 'TransferAdminAjax::saveReceiveDraft',
                save: 'TransferAdminAjax::saveReceive',
            }
        });

        this.elements.tabs = $(`.js_transfer_receive_tab`);

        this.tab = 'all';

        this.elements.totalQuantity = $(`.js_${module}_total_quantity`);

        this.totalQuantity = 0;
    }

    productItem(item) {

        if(!item?.receive_quantity || item.receive_quantity === 0 || item.receive_quantity === undefined)
        {
            item.receive_quantity = item.send_quantity;
        }

        item.receive_price = SkilldoUtil.formatNumber(item.price * item.receive_quantity);

        return item
    }

    payloadData(data)
    {
        data.products = [];

        for (const [key, item] of Object.entries(this.products.items)) {
            data.products.push({
                id: item.id,
                code: item.code,
                title: item.title,
                attribute_label: item?.attribute_label,
                send_quantity: item.send_quantity,
                receive_quantity: item.receive_quantity,
                receive_price: item.receive_quantity*item.price,
                price: item.price,
            })
        }

        return data;
    }

    calculate()
    {
        this.totalQuantity = 0;

        for (const [key, item] of Object.entries(this.products.items)) {

            this.totalQuantity += item.receive_quantity*1;
        }

        this.elements.totalQuantity.html(SkilldoUtil.formatNumber(this.totalQuantity))

        this.calculateTab()
    }

    calculateTab()
    {
        let all = 0;

        let match = 0;

        $('.js_column').show()

        for (const [key, item] of Object.entries(this.products.items))
        {
            all++;

            if(item.receive_quantity == item.send_quantity)
            {
                match++;

                if(this.tab == 'notMatch')
                {
                    $('.transfer-receive-column-' + item.id).hide()
                }
            }
            else
            {
                if(this.tab == 'match')
                {
                    $('.transfer-receive-column-' + item.id).hide()
                }
            }
        }

        this.elements.tabs.find('#all .js_transfer_receive_tab_count').html(all)

        this.elements.tabs.find('#match .js_transfer_receive_tab_count').html(match)

        this.elements.tabs.find('#notMatch .js_transfer_receive_tab_count').html(all -match)
    }

    clickTab(element)
    {
        this.elements.tabs.find('.nav-link').removeClass('active')

        element.addClass('active')

        this.tab = element.attr('id')

        this.calculateTab()

        return false
    }

    changeQuantity(element)
    {
        let column = element.closest('.js_column');

        let id = column.data('id')

        let quantity = element.val().replace(/,/g, '')

        quantity = parseInt(quantity)

        if(quantity < 0)
        {
            quantity = 1;
            element.val(quantity)
        }

        let item = this.products.get(id)

        if(item)
        {
            if(quantity > item.send_quantity)
            {
                quantity = item.send_quantity;
                element.val(quantity)
            }

            if(item.receive_quantity !== quantity)
            {
                item.receive_quantity = quantity;

                item.receive_price = item.receive_quantity * item.price;

                this.products.update(item)

                column.find('.js_input_receive_price').html(SkilldoUtil.formatNumber(item.receive_price))

                this.calculate()
            }
        }
    }

    tabQuantity(event, element)
    {
        if(event.key === 'Tab')
        {
            event.preventDefault();

            let inputs = this.elements.table.find('.js_input_quantity');

            let index = inputs.index(element);

            if (index !== -1 && index < inputs.length - 1)
            {
                inputs.eq(index + 1).focus(); // Chuyển focus đến input kế tiếp
            }
        }
    }

    saveSuccess() {
        window.location.href = 'admin/transfers'
    }

    events() {

        let handler = this

        $(document)
            .on('click', '.js_transfer_receive_tab .nav-link', function () {
                handler.clickTab($(this)) //function search
            })
            .on('keyup', '.js_input_receive_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('keydown', '.js_input_receive_quantity', function (event) {
                handler.tabQuantity(event, this) //function search
            })
            .on('click', '#js_transfer_receive_btn_draft', function () {
                handler.clickSaveDraft($(this))
                return false
            })
            .on('click', '#js_transfer_receive_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
    }
}