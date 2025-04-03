<?php
Class StockAsset
{
    static public function admin(): void
    {
        $pluginPath = Path::plugin(STOCK_NAME);
        Admin::asset()->location('header')->add(STOCK_NAME, $pluginPath.'/assets/css/purchase-order.css');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/script.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/purchase-order.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/purchase-return.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/damage-items.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/stock-take.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/cash-flow.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/suppliers.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/transfers.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/order-return.js');
        Admin::asset()->location('footer')->add(STOCK_NAME, $pluginPath.'/assets/js/customer.js');
    }
}

add_action('admin_init', [StockAsset::class, 'admin']);
