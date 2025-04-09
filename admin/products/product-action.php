<?php
use SkillDo\DB;

class AdminStockProductAction {

    /*
     * Thêm mới sản phẩm không có biến thể
     */
    static function productAdd($product): void
    {
        if($product->hasVariation == 0)
        {
            $branches = \Skdepot\Helper::getBranchAll();

            foreach ($branches as $branch)
            {
                $priceCost = (int)Str::price(request()->input('price_cost'));

                \Skdepot\Model\Inventory::create([
                    'product_id'   => $product->id,
                    'product_name' => $product->title,
                    'product_code' => $product->code,
                    'branch_id'    => $branch->id,
                    'branch_name'  => $branch->name,
                    'stock'        => 0,
                    'status'       => \Skdepot\Status\Inventory::out->value,
                    'price_cost'   => $priceCost
                ]);
            }
        }
    }

    /*
     * Cập nhật sản phẩm
     */
    static function productEdit($product): void
    {
        if(have_posts($product)) {

            if($product->hasVariation == 0)
            {
                $branches = \Skdepot\Helper::getBranchAll();

                $branchCurrent = \Skdepot\Helper::getBranchCurrent();

                $priceCost = Str::price(request()->input('price_cost'));

                $upName = false;

                $upPriceCost = false;

                foreach ($branches as $branch)
                {
                    $count = \Skdepot\Model\Inventory::where('product_id', $product->id)
                        ->where('parent_id', 0)
                        ->where('branch_id', $branch->id)
                        ->count();

                    if($count == 0)
                    {
                        \Skdepot\Model\Inventory::create([
                            'product_id'   => $product->id,
                            'product_name' => $product->title,
                            'product_code' => $product->code,
                            'branch_id'    => $branch->id,
                            'branch_name'  => $branch->name,
                            'price_cost'  => $priceCost,
                            'stock'     => 0,
                            'status'    => \Skdepot\Status\Inventory::out->value
                        ]);
                    }
                    else
                    {
                        $upName = true;

                        if($branch->id == $branchCurrent->id)
                        {
                            $upPriceCost = true;
                        }
                    }
                }

                if($upName)
                {
                    \Skdepot\Model\Inventory::where('product_id', $product->id)->update([
                        'product_name' => $product->title,
                        'product_code' => $product->code,
                    ]);
                }
                if($upPriceCost)
                {
                    \Skdepot\Model\Inventory::where('product_id', $product->id)
                        ->where('branch_id', $branchCurrent->id)
                        ->update(['price_cost' => $priceCost]);
                }
            }
            else
            {
                \Skdepot\Model\Inventory::where('parent_id', $product->id)->update([
                    'product_name' => $product->title,
                ]);
            }
        }
    }

    static function variationsAdd($variations): void
    {
        $branches = \Skdepot\Helper::getBranchAll();

        $inventories = [];

        foreach ($branches as $branch) {
            foreach ($variations as $variation) {
                $inventories[] = [
                    'product_name'  => $variation->title,
                    'product_code'  => $variation->code,
                    'product_id'    => $variation->id,
                    'parent_id'     => $variation->parent_id,
                    'status'        => \Skdepot\Status\Inventory::out->value,
                    'stock'         => 0,
                    'branch_id'     => $branch->id,
                    'branch_name'   => $branch->name,
                    'price_cost'    => $variation->price_cost ?? 0,
                ];
            }
        }

        \Skdepot\Model\Inventory::inserts($inventories);
    }

    static function variationAddOrUp($variation): void
    {
        $branches = \Skdepot\Helper::getBranchAll();

        $branchCurrent = \Skdepot\Helper::getBranchCurrent();

        $inventoriesCheck = \Skdepot\Model\Inventory::where('parent_id', $variation->parent_id)->get();

        $inventoriesAdd = [];

        $inventoriesUp = [];

        $priceCost = Str::price(request()->input('variation.price_cost'));

        foreach ($branches as $branch) {

            $inventoryChange = [
                'product_name'  => $variation->title,
                'product_code'  => $variation->code,
                'branch_name'   => $branch->name,
            ];

            if(have_posts($inventoriesCheck)) {
                foreach ($inventoriesCheck as $inventory) {
                    if($inventory->product_id == $variation->id && $inventory->branch_id == $branch->id) {
                        $inventoryChange['id'] = $inventory->id;
                        break;
                    }
                }
            }

            if(!empty($inventoryChange['id']))
            {
                if($branch->id  == $branchCurrent->id)
                {
                    $inventoryChange['price_cost'] = $priceCost;
                }

                $inventoriesUp[] = $inventoryChange;
            }
            else
            {
                $inventoryChange['status']      = \Skdepot\Status\Inventory::out->value;
                $inventoryChange['stock']       = 0;
                $inventoryChange['branch_id']   = $branch->id;
                $inventoryChange['product_id']  = $variation->id;
                $inventoryChange['parent_id']   = $variation->parent_id;
                $inventoryChange['price_cost']  = $priceCost;

                $inventoriesAdd[] = $inventoryChange;
            }
        }

        if(have_posts($inventoriesAdd))
        {
            \Skdepot\Model\Inventory::inserts($inventoriesAdd);
        }

        if(have_posts($inventoriesUp))
        {
            \Skdepot\Model\Inventory::updateBatch($inventoriesUp, 'id');
        }

        \Skdepot\Model\Inventory::where('product_id', $variation->parent_id)->delete();
    }

    static function variationDelete($id, $variations): void
    {
        \Skdepot\Model\Inventory::where('product_id', $id)->delete();

        $productId = 0;

        foreach ($variations as $key => $variation)
        {
            if($variation->id == $id)
            {
                $productId = $variation->parent_id;
                unset($variations[$key]);
                break;
            }
        }

        //nếu đã xóa hết biến thể
        if(!empty($productId) && !have_posts($variations))
        {
            $branches = \Skdepot\Helper::getBranchAll();

            $product =\Ecommerce\Model\Product::select('id', 'code', 'title')->whereKey($productId)->first();

            foreach ($branches as $branch)
            {
                $count = \Skdepot\Model\Inventory::where('product_id', $productId)
                    ->where('branch_id', $branch->id)
                    ->count();

                if($count == 0) {

                    $inventory = [
                        'product_name'  => $product->title,
                        'product_code'  => $product->code,
                        'product_id'    => $productId,
                        'parent_id'     => 0,
                        'status'        => \Skdepot\Status\Inventory::out->value,
                        'stock'         => 0,
                        'branch_id'     => $branch->id,
                        'branch_name'   => $branch->name,
                        'price_cost'    => 0,
                    ];

                    \Skdepot\Model\Inventory::create($inventory);
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

            if(have_posts($productID))
            {
                \Skdepot\Model\Inventory::whereIn('parent_id', $productID)->delete();
                \Skdepot\Model\Inventory::whereIn('product_id', $productID)->delete();
            }
        }
    }
}


add_action('admin_product_add_success', 'AdminStockProductAction::productAdd', 10);
add_action('admin_product_edit_success', 'AdminStockProductAction::productEdit', 10);
add_action('admin_product_variation_delete', 'AdminStockProductAction::variationDelete', 10, 2);
add_action('admin_product_variations_add', 'AdminStockProductAction::variationsAdd');
add_action('admin_product_variation_save', 'AdminStockProductAction::variationAddOrUp');
add_action('ajax_delete_before_success', 'AdminStockProductAction::productDelete', 10, 2);
