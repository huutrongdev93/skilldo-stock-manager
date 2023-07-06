<?php
Class Stock_Manager_Admin {
    static public function navigation() {
        if(Auth::hasCap('inventory_list')) {
            AdminMenu::addSub('products', 'stock_inventory', 'Kho hàng', 'plugins?page=stock_inventory', ['callback' => 'Stock_Manager_Admin::page', 'position' => 'products_categories']);
        }
    }
    static public function page() {

        $branch_id = (int)Request::get('branch');

        if($branch_id == 0) $branch_id = 1;

        $stock_status = Request::get('status');

        $branches = Branch::gets();

        $args = Qr::set();

        if(!empty($branch_id)) $args->where('branch_id', $branch_id);

        if(!empty($stock_status)) $args->where('status', $stock_status);

        $config  = array (
            'currentPage'   => 1,
            'totalRecords'  => Inventory::count($args),
            'limit'		    => 20,
            'url'           => '#',
        );

        $pagination = new Pagination($config);

        $inventories = Inventory::gets($args->limit(20));

        include_once 'views/inventory.php';
    }
}
add_action('admin_init', 'Stock_Manager_Admin::navigation', 10);

class AdminStockProduct {
    static public function productInsert($columnsTable) {
        $columnsTable['stock_status'] = ['string', 'outstock'];
        return $columnsTable;
    }
    static public function productTableHeader($column) {
        $newColumn = array();
        foreach ($column as $key => $col) {
            if($key == 'collection') $newColumn['stock'] = 'Tồn kho';
            $newColumn[$key] = $col;
        }
        return $newColumn;
    }
    static public function setBranchToTable($args) {
        $args['global']['branch'] = Branch::gets(Qr::set()->select('id', 'name'));
        return $args;
    }
    static public function productTableData($column_name, $item, $global) {
        switch ($column_name) {
            case 'stock':
                $branches = clone $global['branch'];
                if(!empty($item->variations)) {
                    $listId = [];
                    foreach ($item->variations as $variable) {
                        $listId[] = $variable->id;
                    }
                    $inventory = Inventory::gets(Qr::set()->whereIn('product_id', $listId));
                    $branchesTemp = [];
                    foreach ($branches as $branch) {
                        $branchesTemp[$branch->id] = clone $branch;
                        foreach ($item->variations as $variable) {
                            $branchesTemp[$branch->id]->inventory[$variable->id] = [];
                            $branchesTemp[$branch->id]->inventory[$variable->id]['optionName'] = $variable->optionName;
                            $branchesTemp[$branch->id]->inventory[$variable->id]['id'] = $variable->id;
                            $branchesTemp[$branch->id]->inventory[$variable->id]['branch_id'] = $branch->id;
                            $branchesTemp[$branch->id]->inventory[$variable->id]['stock'] = 0;
                            foreach ($inventory as $stock) {
                                if($stock->branch_id == $branch->id && $stock->product_id == $variable->id) {
                                    $branchesTemp[$branch->id]->inventory[$variable->id]['stock'] = $stock->stock;
                                    break;
                                }
                            }
                        }
                    }
                    $branches = $branchesTemp;

                    $countKey = 0;
                    foreach ($item->variations as $key => $variable) {
                        $inventory = 0;
                        foreach ($branches as $branch) {
                            if(!empty($branch->inventory[$variable->id])) $inventory += $branch->inventory[$variable->id]['stock'];
                        }
                        $class = ($countKey++ > 2) ? 'd-hidden' : '';
                        echo '<div class="product-variations-model '.$class.'">
                        <p class="quick-edit-box d-flex gap-3">
                        <span class="product_stock_'.$variable->id.'">'.$inventory.'</span>
                        <span class="quick-edit js_product_quick_edit_stock" data-id="'.$item->id.'" data-inventory="'.htmlentities(json_encode($branches)).'"><i class="fa-thin fa-pen"></i></span>
                        </p>
                        </div>';
                    }
                    echo (count($item->variations) > 3) ? '<p>...</p>' : '';
                }
                else {
                    $inventory = Inventory::gets(Qr::set('product_id', $item->id));
                    $branchesTemp = [];
                    $inventoryTotal = 0;
                    foreach ($branches as $branch) {
                        $branchesTemp[$branch->id] = clone $branch;
                        $branchesTemp[$branch->id]->inventory[$item->id] = [];
                        $branchesTemp[$branch->id]->inventory[$item->id]['optionName'] = $item->title;
                        $branchesTemp[$branch->id]->inventory[$item->id]['id'] = $item->id;
                        $branchesTemp[$branch->id]->inventory[$item->id]['branch_id'] = $branch->id;
                        $branchesTemp[$branch->id]->inventory[$item->id]['stock'] = 0;
                        foreach ($inventory as $stock) {
                            if($stock->branch_id == $branch->id && $stock->product_id == $item->id) {
                                $branchesTemp[$branch->id]->inventory[$item->id]['stock'] = $stock->stock;
                                $inventoryTotal += $stock->stock;
                                break;
                            }
                        }
                    }
                    $branches = $branchesTemp;
                    echo '<p class="quick-edit-box d-flex gap-3">
                        <span class="product_stock_'.$item->id.'">'.$inventoryTotal.'</span>
                        <span class="quick-edit js_product_quick_edit_stock" data-id="'.$item->id.'" data-inventory="'.htmlentities(json_encode($branches)).'"><i class="fa-thin fa-pen"></i></span>
                    </p>';
                }
                break;
        }
    }
    static public function productTableStatus($item) {
        if(!empty($item->stock_status)) echo '<span style="background-color:'.Inventory::status($item->stock_status, 'color').'; border-radius:20px; padding:3px 15px; font-size:12px; display:inline-block;color:#000;">'.Inventory::status($item->stock_status,'label').'</span>';
    }
    static public function productDelete($productID) {
        if(is_numeric($productID)) $productID = [$productID];
        if(have_posts($productID)) {
            foreach ($productID as $id) {
                $inventory = Inventory::get(Qr::set('product_id', $id));
                if (have_posts($inventory)) {
                    $inventoryVariations = Inventory::get(Qr::set('parent_id', $id));
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
    static public function quickEdit() {
        Plugin::partial(STOCK_NAME, 'admin/views/quick-edit');
    }
    static public function productStatusCreated() {

        $products = Product::gets();

        $branches = Branch::gets();

        foreach ($products as $product) {

            $inventory = Inventory::get(Qr::set('product_id', $product->id));

            if(have_posts($inventory)) continue;

            $variations = Product::gets(Qr::set('parent_id', $product->id)->where('type', 'variations'));

            if(have_posts($variations)) {

                $stock = 'outstock';

                foreach ($variations as $variation) {

                    $inventory = Inventory::get(Qr::set('product_id', $variation->id));

                    if(have_posts($inventory)) {
                        if($inventory->status != 'outstock') $stock = 'instock';
                        continue;
                    }

                    $variation = Variation::get(Qr::set('id', $variation->id));

                    $attr_name = '';

                    foreach ($variation->items as $key => $attr_id) {
                        $attribute = Attributes::getItem($attr_id);
                        $attr_name .= ' - '.$attribute->title;
                    }

                    foreach ($branches as $branch) {
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
                foreach ($branches as $branch) {
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

add_action('delete_product_success', 'AdminStockProduct::productDelete', 10);
add_action('delete_products_list_success', 'AdminStockProduct::productDelete', 10);
add_filter('columns_db_products', 'AdminStockProduct::productInsert');
add_filter('admin_product_table_data', 'AdminStockProduct::setBranchToTable');
add_filter('manage_product_columns', 'AdminStockProduct::productTableHeader');
add_action('manage_product_custom_column', 'AdminStockProduct::productTableData',10,3);
add_action('admin_product_table_column_title', 'AdminStockProduct::productTableStatus',10);
add_action('admin_footer', 'AdminStockProduct::quickEdit',10);