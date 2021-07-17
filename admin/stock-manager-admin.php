<?php
Class Stock_Manager_Admin {
    static public function navigation() {
        if(Auth::hasCap('inventory_list')) {
            AdminMenu::addSub('products', 'stock_inventory', 'Kho hàng', 'plugins?page=stock_inventory', ['callback' => 'Stock_Manager_Admin::page', 'position' => 'products_categories']);
        }
    }
    static public function page() {
        $branch_id = (int)InputBuilder::Get('branch');
        if($branch_id == 0) $branch_id = 1;
        $stock_status = InputBuilder::Get('status');
        $branchs = Branch::gets();
        $where = [];
        if(!empty($branch_id)) $where['branch_id'] = $branch_id;
        if(!empty($stock_status)) $where['status'] = $stock_status;
        $inventories = Inventory::gets([
            'where' => $where,
            'params' => ['limit' => 20]
        ]);
        $config  = [
            'current_page'  => 1,
            'total_rows'    => Inventory::count(['where' => $where]),
            'number'		=> 20,
            'url'           => '',
        ];
        $pagination = new paging($config);
        include_once 'views/inventory.php';
    }
    static public function productTableHeader($column) {
        $newcolumn = array();
        foreach ($column as $key => $col) {
            $newcolumn[$key] = $col;
            if($key == 'price_sale') {
                $newcolumn['stock_status'] = 'Kho hàng';
            }
        }
        return $newcolumn;
    }
    static public function productTableData($column_name, $item) {
        switch ( $column_name ) {
            case 'stock_status':
                if(!empty($item->stock_status)) echo '<span style="background-color:'.Inventory::status($item->stock_status, 'color').'; border-radius:20px; padding:3px 15px; font-size:12px; display:inline-block;color:#000;">'.Inventory::status($item->stock_status,'label').'</span>';
                break;
        }
    }
    static public function productDelete($module, $productID) {
        if($module == 'products') {
            if(is_numeric($productID)) $productID = [$productID];
            if(have_posts($productID)) {
                foreach ($productID as $id) {
                    $inventory = Inventory::get(['where' => ['product_id' => $id]]);
                    if (have_posts($inventory)) {
                        $inventoryVariations = Inventory::get(['where' => ['parent_id' => $id]]);
                        if(have_posts($inventoryVariations)) {
                            foreach ($inventoryVariations as $inventoryVariation) {
                                Inventory::delete($inventoryVariation->id);
                            }
                        }
                        Inventory::delete($inventory->id);
                    }
                }
            }
        }
    }
    static public function productStatusCreated() {

        $products = Product::gets();

        $branchs = Branch::gets();

        foreach ($products as $product) {

            $inventory = Inventory::get(['where' => ['product_id' => $product->id]]);

            if(have_posts($inventory)) continue;

            $variations = Product::gets(['where' => ['type' => 'variations', 'parent_id' => $product->id]]);

            if(have_posts($variations)) {
                $stock = 'outstock';
                foreach ($variations as $variation) {

                    $inventory = Inventory::get(['where' => ['product_id' => $variation->id]]);

                    if(have_posts($inventory)) {
                        if($inventory->status != 'outstock') $stock = 'instock';
                        continue;
                    }

                    $variation = Variation::get(['where' => array('id' => $variation->id)]);

                    $attr_name = '';

                    foreach ($variation->items as $key => $attr_id) {
                        $attribute = Attribute::getItem($attr_id);
                        $attr_name .= ' - '.$attribute->title;
                    }

                    foreach ($branchs as $branch) {
                        $inventory = [
                            'product_name'  => $product->title.$attr_name,
                            'product_code'  => $variation->code,
                            'product_id'    => $variation->id,
                            'parent_id'     => $product->id,
                            'status'        => 'outstock',
                            'stock'         => 0,
                            'branch_id'     => $branch->id,
                            'branch_name'   => $branch->name,
                        ];
                        Inventory::insert($inventory);
                        Product::insert(['id' => $variation->id, 'stock_status' => 'outstock']);
                    }
                }
                Product::insert(['id' => $product->id, 'stock_status' => $stock]);
            }
            else {
                foreach ($branchs as $branch) {
                    $inventory = [
                        'product_name'  => $product->title,
                        'product_id'    => $product->id,
                        'product_code'  => $product->code,
                        'status'        => 'outstock',
                        'stock'         => 0,
                        'branch_id'     => $branch->id,
                        'branch_name'   => $branch->name,
                    ];
                    Inventory::insert($inventory);
                    Product::insert(['id' => $product->id, 'stock_status' => 'outstock']);
                }
            }
        }
    }
}
add_action('admin_init', 'Stock_Manager_Admin::navigation', 10);
add_action('ajax_delete_before_success', 'Stock_Manager_Admin::productDelete', 10, 2);
add_filter('manage_product_columns', 'Stock_Manager_Admin::productTableHeader' );
add_action('manage_product_custom_column', 'Stock_Manager_Admin::productTableData',10,2);

//Stock_Manager_Admin::productStatusCreated();