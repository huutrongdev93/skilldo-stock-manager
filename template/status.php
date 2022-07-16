<p class="product-detail-code product-detail-inventory-status" style="margin-top: 10px;">
    <?php echo __('Tình trạng', 'stock_product_detail_status');?>:
    <span class="stock_inventory_status <?php echo $object->stock_status;?>">
        <?php echo Inventory::status($object->stock_status,'label');?>
    </span>
</p>
<style>
    .stock_inventory_status {
        border-radius:20px; padding:0 15px; font-size:12px; display:inline-block;
    }
    .stock_inventory_status.instock {
        color:var(--btn-green)!important;
        background-color:<?php echo Inventory::status('instock','color');?>;
    }
    .stock_inventory_status.outstock {
        color:var(--btn-red)!important;
        background-color:<?php echo Inventory::status('outstock','color');?>;
    }
</style>