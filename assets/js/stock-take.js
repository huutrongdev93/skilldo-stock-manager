class StockTakeIndexHandle extends WarehouseIndexHandle
{
    constructor() {
        super({
            module: 'stock_take',
            ajax: {
                loadProducts: 'StockTakeAdminAjax::loadProductsDetail',
                print: 'StockTakeAdminAjax::print'
            }
        });
    }

    events() {

        let handle = this;

        $(document)
            .on('click', '.js_stock_take_btn_detail', function () {
                handle.clickButtonDetail($(this))
            })
            .on('click', '#js_stock_take_modal_detail .pagination .page-link', function () {
                handle.clickPaginationDetail($(this))
                return false
            })
            .on('click', '.js_stock_take_btn_print', function () {
                handle.clickButtonPrint($(this))
                return false
            })
    }
}

class StockTakeNewHandle extends WarehouseNewHandle
{
    constructor()
    {
        const module = 'stock_take'

        super({
            module: module,
            ajax: {
                loadProducts: 'StockTakeAdminAjax::loadProductsEdit',
                addDraft: 'StockTakeAdminAjax::addDraft',
                saveDraft: 'StockTakeAdminAjax::saveDraft',
                add: 'StockTakeAdminAjax::add',
                save: 'StockTakeAdminAjax::save',
                import: 'StockTakeAdminAjax::import',
            }
        });

        this.elements.totalActualQuantity = $(`.js_${module}_total_actual_quantity`);

        this.elements.tabs = $(`.js_stock_take_tab`);

        this.tab = 'all';

        this.totalActualQuantity = 0;
    }

    productItem(item) {

        if(!item?.quantity)
        {
            item.quantity = item.stock;
        }

        if(!item?.price)
        {
            item.price = item.price_cost;
        }

        item.adjustment_quantity = item.quantity - item.stock;

        item.adjustment_price = SkilldoUtil.formatNumber(item.quantity*item.price - item.stock*item.price);

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
                stock: item.stock.replace(/,/g, '')*1,
                price: item.price,
                quantity: item.quantity
            })
        }

        return data;
    }

    calculate()
    {
        this.totalActualQuantity = 0;

        for (const [key, item] of Object.entries(this.products.items)) {

            this.totalActualQuantity += item.quantity*1;
        }

        this.elements.totalActualQuantity.html(SkilldoUtil.formatNumber(this.totalActualQuantity))

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

            if(item.quantity == item.stock)
            {
                match++;

                if(this.tab == 'notMatch')
                {
                    $('.stock-take-column-' + item.id).hide()
                }
            }
            else
            {
                if(this.tab == 'match')
                {
                    $('.stock-take-column-' + item.id).hide()
                }
            }
        }

        this.elements.tabs.find('#all .js_stock_take_tab_count').html(all)

        this.elements.tabs.find('#match .js_stock_take_tab_count').html(match)

        this.elements.tabs.find('#notMatch .js_stock_take_tab_count').html(all -match)
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
            quantity = 0;

            element.val(quantity)
        }

        let item = this.products.get(id)

        if(item)
        {
            if(item.quantity !== quantity)
            {
                item.quantity = quantity;

                item.adjustment_quantity = item.quantity - item.stock;

                item.adjustment_price = item.quantity*item.price - item.stock*item.price;

                this.products.update(item)

                column.find('.js_input_adjustment_quantity').html(SkilldoUtil.formatNumber(item.adjustment_quantity))

                column.find('.js_input_adjustment_price').html(SkilldoUtil.formatNumber(item.adjustment_price))

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
        window.location.href = 'admin/stock-take'
    }

    events() {

        let handler = this

        $(document)
            .on('click', '.autocomplete-suggestions-results .product-item', function () {
                handler.clickAddProduct($(this)) //function search
            })
            .on('click', '.js_stock_take_tab .nav-link', function () {
                handler.clickTab($(this)) //function search
            })
            .on('click', '.js_stock_take_btn_delete', function () {
                handler.clickDeleteProduct($(this)) //function search
            })
            .on('keyup', '.js_input_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('keydown', '.js_input_quantity', function (event) {
                handler.tabQuantity(event, this) //function search
            })
            .on('click', '#js_stock_take_btn_draft', function () {
                handler.clickSaveDraft($(this))
                return false
            })
            .on('click', '#js_stock_take_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
            .on('change', '#js_stock_take_import_input', function () {
                $('#js_stock_take_import_form').trigger('submit')
                return false
            })
            .on('submit', '#js_stock_take_import_form', function () {
                handler.import($(this))
                return false
            })
    }
}