<?php
include 'buttons.php';
include 'table.php';

Class AdminInventoriesPage {
    static public function navigation(): void
    {
        if(Auth::hasCap('inventory_list')) {
            AdminMenu::addSub('products', 'stock_inventory', 'Kho hàng', 'plugins/stock_inventory', [
                'callback' => 'AdminInventoriesPage::page',
                'position' => 'products_categories'
            ]);
        }
    }

    static function page(\SkillDo\Http\Request $request, $params): void {
        self::pageList($request);
    }

    static function pageList(\SkillDo\Http\Request $request): void
    {
        $table = new AdminInventoriesTable([
            'items' => [],
            'table' => 'inventories',
            'model' => model('inventories'),
            'module'=> 'inventories',
        ]);

        Admin::view('components/page-default/page-index', [
            'module'    => 'inventories',
            'name'      => trans('inventories.title'),
            'table'     => $table,
            'tableId'   => 'admin_table_inventories_list',
            'limitKey'  => 'admin_inventories_limit',
            'ajax'      => 'Stock_Manager_Ajax::inventoryLoad',
        ]);

        Plugin::view('stock-manager', 'admin/inventories/modal');
    }
}
add_action('admin_init', 'AdminInventoriesPage::navigation', 10);
add_filter('manage_inventories_input', 'AdminInventoriesPage::form');