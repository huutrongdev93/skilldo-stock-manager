<?php
Class StockAsset
{
    static public function admin(): void
    {
        Admin::asset()->location('header')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/css/purchase-order.css');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/script.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/purchase-order.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/purchase-return.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/damage-items.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/stock-take.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/cash-flow.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, Path::plugin(STOCK_NAME).'/assets/js/suppliers.js');
    }
}

add_action('admin_init', [StockAsset::class, 'admin']);
