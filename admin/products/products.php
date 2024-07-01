<?php
use Illuminate\Database\Capsule\Manager as DB;

class AdminStockProduct {
    static function productTableHeader($column): array
    {
        $newColumn = [];

        foreach ($column as $key => $col) {

            if($key == 'order') {
                $newColumn['stock'] = [
                    'label' => 'Tồn kho',
                    'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnBadge::make('stock_status', $item, $args)
                        ->color(function (string $status) {
                            return InventoryHelper::status($status,'color');
                        })
                        ->label(function (string $status) {
                            return InventoryHelper::status($status,'label').' <i class="fa-thin fa-pen"></i>';
                        })
                        ->attributes(fn ($item): array => [
                            'data-id' => $item->id,
                        ])
                        ->class(['js_product_quick_edit_stock'])
                ];
            }

            $newColumn[$key] = $col;
        }

        return $newColumn;
    }

    static function productStatusCreated(): void
    {
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
                        $attribute = AttributesItem::get($attr_id);
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

    static function variationsAdd($variations): void
    {
        $branches = Branch::gets();

        $inventories = [];

        foreach ($branches as $branch) {
            foreach ($variations as $variation) {
                $inventories[] = [
                    'product_name'  => $variation->title,
                    'product_code'  => $variation->code,
                    'product_id'    => $variation->id,
                    'parent_id'     => $variation->parent_id,
                    'status'        => 'outstock',
                    'stock'         => 0,
                    'branch_id'     => $branch->id,
                    'branch_name'   => $branch->name,
                ];
            }
        }

        DB::table('inventories')->insert($inventories);
    }

    static function variationAddOrUp($variation): void
    {
        $branches = Branch::gets();

        $inventoriesCheck = Inventory::where('parent_id', $variation->parent_id)->fetch();

        $inventories = [];

        $status = 'outstock';

        foreach ($branches as $branch) {

            $inventoryAddOrUp = [
                'product_code'  => $variation->code,
                'product_id'    => $variation->id,
                'parent_id'     => $variation->parent_id,
                'status'        => 'outstock',
                'stock'         => 0,
                'branch_id'     => $branch->id,
                'branch_name'   => $branch->name,
            ];

            if(have_posts($inventoriesCheck)) {
                foreach ($inventoriesCheck as $inventory) {
                    if($inventory->product_id == $variation->id && $inventory->branch_id == $branch->id) {
                        $inventoryAddOrUp['id']     = $inventory->id;
                        $inventoryAddOrUp['status'] = $inventory->status;
                        $inventoryAddOrUp['stock']  = $inventory->stock;
                        break;
                    }
                }
            }

            $inventories[] = $inventoryAddOrUp;
        }

        foreach ($inventories as $inventory) {

            if($inventory['status'] == 'instock') {
                $status = 'instock';
            }

            Inventory::insert($inventory);
        }

        model('products')::where('id', $variation->parent_id)->update(['stock_status' => $status]);

        model('inventories')::where('product_id', $variation->parent_id)->remove();
    }

    static function variationDelete($id, $variations): void
    {
        Inventory::where('product_id', $id)->remove();

        $status = 'outstock';

        $productId = 0;

        $variationDelete = [];

        foreach ($variations as $key => $variation) {
            if($variation->id == $id) {
                $productId = $variation->parent_id;
                $variationDelete = $variation;
                unset($variations[$key]);
                continue;
            }
            if($variation->stock_status == 'instock') {
                $status = 'instock';
            }
        }

        if(!empty($productId)) {
            //nếu còn biến thể cập nhật trạng thái sản phẩm
            if(have_posts($variations)) {
                if($status == 'outstock') {
                    model('products')::where('id', $productId)->update(['stock_status', $status]);
                }
            }
            else {
                if($variationDelete->stock_status == 'instock') {
                    model('products')::where('id', $productId)->update(['stock_status', 'outstock']);
                }

                $branches = Branch::gets();

                $count = Inventory::where('id', $productId)->amount();

                if($count == 0) {
                    foreach ($branches as $branch) {
                        $inventory = [
                            'product_name'  => $variationDelete->title,
                            'product_code'  => '',
                            'product_id'    => $productId,
                            'parent_id'     => 0,
                            'status'        => 'outstock',
                            'stock'         => 0,
                            'branch_id'     => $branch->id,
                            'branch_name'   => $branch->name,
                        ];
                        Inventory::insert($inventory);
                    }
                }
            }
        }
    }

    static function productDelete($module, $productID): void
    {
        if($module == 'products') {

            if(is_numeric($productID)) {
                $productID = [$productID];
            }

            if(have_posts($productID)) {
                Inventory::whereIn('parent_id', $productID)->remove();
                Inventory::whereIn('product_id', $productID)->remove();
            }
        }
    }

    static function productAdd($product): void
    {
        if($product->hasVariation == 0) {
            Inventory::insert([
                'productId'    => $product->id,
                'product_name' => $product->title,
                'product_code' => $product->code,
                'stock' => 0,
                'status' => 'outstock'
            ]);
        }
    }

    static function productEdit($product): void
    {
        if(have_posts($product)) {
            if($product->hasVariation == 0) {

                $count = Inventory::where('product_id', $product->id)->where('parent_id', 0)->amount();

                if($count == 1) {
                    Inventory::where('product_id', $product->id)->update([
                        'product_name' => $product->title,
                        'product_code' => $product->code,
                    ]);
                }
                else {
                    Inventory::insert([
                        'productId'    => $product->id,
                        'product_name' => $product->title,
                        'product_code' => $product->code,
                        'stock' => 0,
                        'status' => 'outstock'
                    ]);
                }
            }
            else {
                Inventory::where('parent_id', $product->id)->update([
                    'product_name' => $product->title,
                ]);
            }
        }
    }
}
add_filter('manage_product_columns', 'AdminStockProduct::productTableHeader');
add_action('admin_product_add_success', 'AdminStockProduct::productAdd', 10);
add_action('admin_product_edit_success', 'AdminStockProduct::productEdit', 10);
add_action('admin_product_variation_delete', 'AdminStockProduct::variationDelete', 10, 2);
add_action('admin_product_variations_add', 'AdminStockProduct::variationsAdd');
add_action('admin_product_variation_save', 'AdminStockProduct::variationAddOrUp');
add_action('ajax_delete_before_success', 'AdminStockProduct::productDelete', 10, 2);
