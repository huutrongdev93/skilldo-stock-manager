<?php
Class Stock_Manager_Admin_Product {
    static public function stockProduct($object): void {
        $variation = [];
        $product_id = 0;
        if(have_posts($object)) {
            $product_id = $object->id;
            $variation = Product::get(Qr::set('parent_id', $object->id)->where('type', 'variations'));
        }
        if(!have_posts($variation)) {
            $branches = Branch::gets();
            include 'views/html-metabox.php';
        }
        else {
            echo '<style>';
            echo '#admin_product_metabox_stock { display:none; }';
            echo '#js_btn_collapse_admin_product_metabox_stock { display:none; }';
            echo '</style>';
        }
    }
    static public function stockProductVariation($variation_id, $variation) {
        $branches = Branch::gets();
        include 'views/html-metabox-variation.php';
    }
    static public function save($product_id, $module) {

        if($module == 'products' && Auth::hasCap('inventory_edit')) {

            $product = Product::get($product_id);

            $variable_stock = Request::Post('variable_stock');

            if(have_posts($variable_stock)) {

                foreach ($variable_stock as $variation_id => $stocks) {

                    $variation = Variation::get(Qr::set($variation_id));

                    $attr_name = '';

                    foreach ($variation->items as $key => $attr_id) {
                        $attribute = Attributes::getItem($attr_id);
                        $attr_name .= ' - '.$attribute->title;
                    }

                    if(have_posts($variation)) {
                        $inventory = [
                            'product_name'  => $product->title.$attr_name,
                            'product_code'  => $variation->code,
                            'product_id'    => $variation->id,
                            'parent_id'     => $product->id,
                        ];
                        foreach ($stocks as $branch_id => $stock) {
                            $branch = Branch::get($branch_id);
                            $inventory['stock']         = $stock;
                            $inventory['branch_id']     = $branch_id;
                            $inventory['branch_name']   = $branch->name;
                            Inventory::update($inventory, Qr::set('product_id', $variation->id)->where('branch_id', $branch_id));
                        }
                    }
                }

                $inventoryMainProduct = Inventory::gets(Qr::set('product_id', $product->id));

                if(have_posts($inventoryMainProduct)) {
                    foreach ($inventoryMainProduct as $item) {
                        Inventory::delete($item->id);
                    }
                }
            }
            else {
                $product_stock = Request::Post('product_stock');

                if(have_posts($product_stock)) {

                    foreach ($product_stock as $branch_id => $stock) {

                        $branch = Branch::get($branch_id);

                        $inventory = [
                            'product_name'  => $product->title,
                            'product_id'    => $product->id,
                            'product_code'  => $product->code,
                            'stock'         => $stock,
                            'branch_id'     => $branch_id,
                            'branch_name'   => $branch->name,
                        ];

                        Inventory::update($inventory, Qr::set('product_id', $product->id)->where('branch_id', $branch_id));
                    }
                }

            }
        }
    }
}
Metabox::add('admin_product_metabox_stock', 'Kho hàng', 'Stock_Manager_Admin_Product::stockProduct', ['module' => 'products']);
add_action('product_variation_html', 'Stock_Manager_Admin_Product::stockProductVariation', 10, 2);
add_action('save_object', 'Stock_Manager_Admin_Product::save', 10, 2);
