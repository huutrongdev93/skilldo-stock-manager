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
                            return \Stock\Status\Inventory::tryFrom($status)->badge();
                        })
                        ->label(function (string $status) {
                            return \Stock\Status\Inventory::tryFrom($status)->label().' <i class="fa-thin fa-pen"></i>';
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
        $products = Product::all();

        $branches = Branch::gets();

        foreach ($products as $product) {

            if($product->hasVariation === 1)
            {
                $variations = Variation::where('parent_id', $product->id)->fetch();

                if(have_posts($variations)) {

                    $stock = 'outstock';

                    foreach ($variations as $variation) {

                        $inventory = Inventory::where('product_id', $variation->id)->first();

                        if(have_posts($inventory)) {
                            if($inventory->status != \Stock\Status\Inventory::out->value)
                            {
                                $stock = \Stock\Status\Inventory::in->value;
                            }
                            continue;
                        }

                        foreach ($branches as $branch)
                        {
                            $inventory = [
                                'product_name'  => $product->title,
                                'product_code'  => $variation->code,
                                'product_id'    => $variation->id,
                                'parent_id'     => $product->id,
                                'status'        => \Stock\Status\Inventory::out->value,
                                'stock'         => 0,
                                'branch_id'     => $branch->id,
                                'branch_name'   => $branch->name,
                            ];
                            Inventory::insert($inventory);

                            \Ecommerce\Model\Variation::whereKey($variation->id)->update([
                                'stock_status' => \Stock\Status\Inventory::out->value
                            ]);
                        }
                    }

                    \Ecommerce\Model\Product::whereKey($product->id)->update([
                        'stock_status' => $stock
                    ]);
                }
            }
            else
            {
                $inventory = Inventory::where('product_id', $product->id)->first();

                if(have_posts($inventory)) continue;

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

                    \Ecommerce\Model\Product::whereKey($product->id)->update([
                        'stock_status' => 'outstock'
                    ]);
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

        $inventoriesCheck = Inventory::where('parent_id', $variation->parent_id)->get();

        $inventoriesAdd = [];

        $inventoriesUp = [];

        $status = \Stock\Status\Inventory::out->value;

        foreach ($branches as $branch) {

            $inventoryAddOrUp = [
                'product_name'  => $variation->title,
                'product_code'  => $variation->code,
                'product_id'    => $variation->id,
                'parent_id'     => $variation->parent_id,
                'status'        => \Stock\Status\Inventory::out->value,
                'stock'         => 0,
                'branch_id'     => $branch->id,
                'branch_name'   => $branch->name,
            ];

            if(have_posts($inventoriesCheck)) {
                foreach ($inventoriesCheck as $inventory) {
                    if($inventory->stock > 0) {
                        $status = \Stock\Status\Inventory::in->value;
                    }
                    if($inventory->product_id == $variation->id && $inventory->branch_id == $branch->id) {
                        $inventoryAddOrUp['id']     = $inventory->id;
                        $inventoryAddOrUp['status'] = $inventory->status;
                        $inventoryAddOrUp['stock']  = $inventory->stock;
                        break;
                    }
                }
            }

            if(!empty($inventoryAddOrUp['id'])) {
                $inventoriesUp[] = $inventoryAddOrUp;
            }
            else {
                $inventoriesAdd[] = $inventoryAddOrUp;
            }
        }

        if(have_posts($inventoriesAdd)) {
            DB::table('inventories')->insert($inventoriesAdd);
        }

        if(have_posts($inventoriesUp)) {
            Inventory::updateBatch($inventoriesUp, 'id');
        }

        DB::table('products')->where('id', $variation->parent_id)->update(['stock_status' => $status]);

        Inventory::where('product_id', $variation->parent_id)->delete();
    }

    static function variationDelete($id, $variations): void
    {
        Inventory::where('product_id', $id)->delete();

        $status = Stock\Status\Inventory::out->value;

        $productId = 0;

        $variationDelete = [];

        foreach ($variations as $key => $variation) {
            if($variation->id == $id) {
                $productId = $variation->parent_id;
                $variationDelete = $variation;
                unset($variations[$key]);
                continue;
            }
            if($variation->stock_status == \Stock\Status\Inventory::in->value) {
                $status = \Stock\Status\Inventory::in->value;
            }
        }

        if(!empty($productId)) {
            //nếu còn biến thể cập nhật trạng thái sản phẩm
            if(have_posts($variations)) {
                if($status == \Stock\Status\Inventory::out->value)
                {
                    DB::table('products')
                        ->where('id', $productId)
                        ->update(['stock_status' => $status]);
                }
            }
            else {
                if($variationDelete->stock_status == \Stock\Status\Inventory::in->value)
                {
                    DB::table('products')
                        ->where('id', $productId)
                        ->update(['stock_status' => \Stock\Status\Inventory::out->value]);
                }

                $branches = Branch::gets();

                $count = Inventory::where('id', $productId)->count();

                if($count == 0) {
                    foreach ($branches as $branch) {
                        $inventory = [
                            'product_name'  => $variationDelete->title,
                            'product_code'  => '',
                            'product_id'    => $productId,
                            'parent_id'     => 0,
                            'status'        => \Stock\Status\Inventory::out->value,
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
        if($module == 'products' || $module == 'Product') {

            if(is_numeric($productID)) {
                $productID = [$productID];
            }

            if(have_posts($productID)) {
                Inventory::whereIn('parent_id', $productID)->delete();
                Inventory::whereIn('product_id', $productID)->delete();
            }
        }
    }

    static function productAdd($product): void
    {
        if($product->hasVariation == 0) {
            Inventory::insert([
                'product_id'    => $product->id,
                'product_name' => $product->title,
                'product_code' => $product->code,
                'stock'        => 0,
                'status'       => \Stock\Status\Inventory::out->value
            ]);
        }
    }

    static function productEdit($product): void
    {
        if(have_posts($product)) {

            if($product->hasVariation == 0) {

                $count = Inventory::where('product_id', $product->id)
                    ->where('parent_id', 0)
                    ->count();

                if($count == 1) {
                    Inventory::where('product_id', $product->id)->update([
                        'product_name' => $product->title,
                        'product_code' => $product->code,
                    ]);
                }
                else {
                    Inventory::insert([
                        'product_id'    => $product->id,
                        'product_name' => $product->title,
                        'product_code' => $product->code,
                        'stock' => 0,
                        'status' => \Stock\Status\Inventory::out->value
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
