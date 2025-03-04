<p class="product-detail-code product-detail-inventory-status" style="margin-top: 10px;">
    <span class="stock_inventory_status badge text-bg-{{ \Stock\Status\Inventory::tryFrom($object->stock_status)->badge() }} {!! $object->stock_status !!}">
        {!! \Stock\Status\Inventory::tryFrom($object->stock_status)->label() !!}
    </span>
</p>
<style>
    .stock_inventory_status {
    }
    .stock_inventory_status.instock {
        color: #fff!important;
        background-color: rgba(2,171,101, 1)!important;
    }
    .stock_inventory_status.outstock {
        color: #fff!important;
        background-color: RGBA(var(--bs-danger-rgb),var(--bs-bg-opacity,1))!important;
    }
</style>