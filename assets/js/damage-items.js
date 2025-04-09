class DamageItemsIndexHandle extends SkdepotIndexHandle
{
    constructor() {
        super({
            module: 'damage_items',
            ajax: {
                loadProducts: 'DamageItemsAdminAjax::loadProductsDetail',
                print: 'DamageItemsAdminAjax::print'
            }
        });
    }

    events() {

        let handle = this;

        $(document)
            .on('click', '.js_damage_items_btn_detail', function () {
                handle.clickButtonDetail($(this))
            })
            .on('click', '#js_damage_items_modal_detail .pagination .page-link', function () {
                handle.clickPaginationDetail($(this))
                return false
            })
            .on('click', '.js_damage_items_btn_print', function () {
                handle.clickButtonPrint($(this))
                return false
            })
    }
}

class DamageItemsNewHandle extends SkdepotNewHandle
{
    constructor()
    {
        const module = 'damage_items'

        super({
            module: module,
            ajax: {
                loadProducts: 'DamageItemsAdminAjax::loadProductsEdit',
                addDraft: 'DamageItemsAdminAjax::addDraft',
                saveDraft: 'DamageItemsAdminAjax::saveDraft',
                add: 'DamageItemsAdminAjax::add',
                save: 'DamageItemsAdminAjax::save',
                import: 'DamageItemsAdminAjax::import',
            }
        });

        this.elements.subTotal = $(`.js_${module}_cost_total`);

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

        if(!item?.cost)
        {
            item.cost = SkilldoUtil.formatNumber(item.price_cost);
        }

        item.subtotal = item.price*item.quantity;

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
            this.subTotal += item.price * item.quantity;
        }

        this.elements.subTotal.html(SkilldoUtil.formatNumber(this.subTotal))
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

                item.subtotal = quantity*item.price;

                this.products.update(item)

                element.closest('.js_column').find('.js_subtotal').html(SkilldoUtil.formatNumber(item.subtotal))

                this.calculate()
            }
        }
    }

    saveSuccess() {
        window.location.href = 'admin/damage-items'
    }

    events() {

        let handler = this

        $(document)
            .on('click', '.autocomplete-suggestions-results .product-item', function () {
                handler.clickAddProduct($(this)) //function search
            })
            .on('click', '.js_damage_items_btn_delete', function () {
                handler.clickDeleteProduct($(this)) //function search
            })
            .on('keyup', '#js_damage_items_form input[name="return_discount"]', function () {
                handler.changeDiscount($(this)) //function search
            })
            .on('keyup', '.js_input_quantity', function () {
                handler.changeQuantity($(this)) //function search
            })
            .on('click', '#js_damage_items_btn_draft', function () {
                handler.clickSaveDraft($(this))
                return false
            })
            .on('click', '#js_damage_items_btn_save', function () {
                handler.clickSave($(this))
                return false
            })
            .on('change', '#js_damage_items_import_input', function () {
                $('#js_damage_items_import_form').trigger('submit')
                return false
            })
            .on('submit', '#js_damage_items_import_form', function () {
                handler.import($(this))
                return false
            })
    }
}