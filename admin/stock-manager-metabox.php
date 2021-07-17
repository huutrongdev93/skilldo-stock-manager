<?php
Class Stock_Manager_Admin_Product {
    static public function stockProduct($object) {
        $variation = [];
        $product_id = 0;
        if(have_posts($object)) {
            $product_id = $object->id;
            $variation = Product::get(['where' => array('parent_id' => $object->id, 'type' => 'variations')]);
        }
        if(!have_posts($variation)) {
            $branchs = Branch::gets();
            include 'views/html-metabox.php';
        }
        else {
            echo '<div class="col-md-12">';
            echo notice('warning', 'Sản phẩm có biển thể không sử dụng kho hàng ở đây.');
            echo '</div>';
        }
    }
    static public function stockProductVariation($variation_id, $variation) {
        $branchs = Branch::gets();
        include 'views/html-metabox-variation.php';
    }
    static public function save($product_id, $module) {

        if($module == 'products' && Auth::hasCap('inventory_edit')) {

            $product = Product::get($product_id);

            $variable_stock = InputBuilder::Post('variable_stock');

            if(have_posts($variable_stock)) {

                foreach ($variable_stock as $variation_id => $stocks) {

                    $variation = Variation::get(['where' => array('id' => $variation_id)]);

                    $attr_name = '';

                    foreach ($variation->items as $key => $attr_id) {
                        $attribute = Attribute::getItem($attr_id);
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
                            Inventory::update($inventory);
                        }
                    }
                }
            }
            else {
                $product_stock = InputBuilder::Post('product_stock');

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

                        Inventory::update($inventory);
                    }
                }

            }
        }
    }
}
Metabox::add('admin_product_metabox_stock', 'Kho hàng', 'Stock_Manager_Admin_Product::stockProduct', ['module' => 'products']);
add_action('product_variation_html', 'Stock_Manager_Admin_Product::stockProductVariation', 10, 2);
add_action('save_object', 'Stock_Manager_Admin_Product::save', 10, 2);
