<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

Class Stock_Manager_Ajax
{
    static function searchProducts(\SkillDo\Http\Request $request): void
    {
        $branchId = (int)$request->input('branch');

        if($branchId == 0) $branchId = 1;

        $selected = [
            'products.id',
            'products.code',
            'products.title',
            'products.attribute_label',
            'products.image',
            'products.price_cost',
            'products.hasVariation',
            'products.parent_id',
        ];

        $query = Qr::select($selected);

        $query->leftJoin('inventories as i', function ($join) use ($branchId) {
            $join->on('i.product_id', '=', 'products.id')->orOn('i.parent_id', '=', 'products.id');
            $join->where('i.branch_id', $branchId);
        });

        $keyword = trim($request->input('keyword'));

        if(!empty($keyword))
        {
            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%'.$keyword.'%');
                $query->orWhere('code', $keyword);
            });
        }

        $query
            ->limit(100)
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc')
            ->groupBy($selected);

        $products = \Ecommerce\Model\Product::widthVariation($query)->get();

        if(have_posts($products))
        {
            $inventories = \Stock\Model\Inventory::whereIn('product_id', $products
                ->pluck('id')
                ->toArray())
                ->select('id', 'product_id', 'stock', 'reserved')
                ->get()
                ->keyBy('product_id');


            foreach ($products as $key => $item)
            {
                if(!$inventories->has($item->id))
                {
                    unset($products[$key]);
                    continue;
                }

                $inventory = $inventories->get($item->id);

                $item = $item->toObject();

                $item->stock = $inventory->stock;

                $item->reserved = $inventory->reserved;

                $item->fullname = $item->title;

                if(!empty($item->attribute_label))
                {
                    $item->fullname .= ' <span class="fw-bold sugg-attr">'.$item->attribute_label.'</span>';
                }

                $item->image = Image::medium($item->image)->html();

                $products[$key] = $item;
            }
        }

        response()->success('Load dữ liệu thành công', $products);
    }

    static function searchProductsByCategory(\SkillDo\Http\Request $request): void
    {
        $branchId = (int)$request->input('branch');

        if($branchId == 0) $branchId = 1;

        $id = trim($request->input('id'));

        $selected = [
            'products.id',
            'products.code',
            'products.title',
            'products.attribute_label',
            'products.image',
            'products.price_cost',
            'products.hasVariation',
            'products.parent_id',
        ];

        $query = Qr::select($selected);
//            ->addSelect([
//                'stock' => \SkillDo\DB::raw('IFNULL(SUM(cle_i.stock), 0) as stock'),
//                'reserved' => \SkillDo\DB::raw('IFNULL(SUM(cle_i.reserved), 0) as reserved')
//            ]);

//        $query->leftJoin('inventories as i', function ($join) use ($branchId) {
//            $join->on('i.product_id', '=', 'products.id');
//            $join->where('i.branch_id', $branchId);
//        });

        $query->leftJoin('relationships as rs', function ($join) use ($id) {
            $join->on('rs.object_id', '=', 'products.id');
            $join->orOn('rs.object_id', '=', 'products.parent_id');
            $join->where('rs.category_id', '=', $id);
            $join->where('rs.object_type', 'products');
            $join->where('rs.value', 'products_categories');
        });

        $query
            ->limit(500)
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc')
            ->groupBy($selected);

        $products = \Ecommerce\Model\Product::widthVariation($query)->get();

        if(have_posts($products))
        {
            $productsId = $products
                ->pluck('id')
                ->toArray();

            $inventories = \Stock\Model\Inventory::whereIn('product_id', $productsId)
                ->select('id', 'product_id', 'stock', 'reserved')
                ->get()
                ->keyBy('product_id');


            foreach ($products as $key => $item)
            {
                if(!$inventories->has($item->id))
                {
                    unset($products[$key]);
                    continue;
                }

                $inventory = $inventories->get($item->id);

                $item = $item->toObject();

                $item->stock = $inventory->stock;

                $item->reserved = $inventory->reserved;

                $item->fullname = $item->title;

                if(!empty($item->attribute_label))
                {
                    $item->fullname .= ' <span class="fw-bold sugg-attr">'.$item->attribute_label.'</span>';
                }

                $item->image = Image::medium($item->image)->html();

                $products[$key] = $item;
            }
        }

        response()->success('Load dữ liệu thành công', $products);
    }

    static function quickEditLoad(\SkillDo\Http\Request $request): void
    {
        if($request->isMethod('post')) {

            $productId = (int)$request->input('productId');

            if(empty($productId)) {
                response()->error(trans('Không xác định được id sản phẩm cần điều chỉnh số lượng kho hàng'));
            }

            $product = Product::find($productId);

            if(!have_posts($product)) {
                response()->error(trans('Không xác định được sản phẩm cần điều chỉnh số lượng kho hàng'));
            }

            $branches = Branch::select('name', 'id')->fetch();

            $variationsId = [];

            foreach ($branches as $branch) {

                if($product->hasVariation == 0) {

                    $branch->inventories = Inventory::where('product_id', $product->id)->fetch();

                    foreach ($branch->inventories as $inventory) {

                        $inventory->optionName = '';
                    }
                }
                else {

                    $branch->inventories = Inventory::where('parent_id', $product->id)->fetch();

                    foreach ($branch->inventories as $inventory) {

                        $inventory->optionName = '';

                        $variationsId[] = $inventory->product_id;
                    }
                }
            }

            if (have_posts($variationsId)) {

                //Attributes Item
                $attributes_items_relationship = model('products_attribute_item')->whereIn('variation_id', $variationsId)->fetch();

                $attributes_items_relationship_id = [];

                foreach ($attributes_items_relationship as $item) {
                    $attributes_items_relationship_id[] = $item->item_id;
                }

                $attributes_items_relationship_id = array_unique($attributes_items_relationship_id);

                $attributesItem = AttributesItem::whereIn('id', $attributes_items_relationship_id)->fetch();

                foreach ($branches as $branch) {

                    foreach ($branch->inventories as $inventory) {

                        foreach ($attributes_items_relationship as $attribute_item_relationship) {

                            if ($inventory->product_id == $attribute_item_relationship->variation_id) {

                                foreach ($attributesItem as $attributeItem) {

                                    if ($attributeItem->id == $attribute_item_relationship->item_id) {

                                        $inventory->optionName .= '<span style="font-weight: bold;">' . $attributeItem->title . '</span>' . ' - ';

                                        break;
                                    }
                                }
                            }
                        }

                        $inventory->optionName = trim($inventory->optionName, ' - ');
                    }
                }
            }

            response()->success(trans('ajax.load.success'), SkillDo\Utils::toArray($branches));
        }

        response()->error(trans('ajax.load.error'));
    }

    static function quickEditSave(\SkillDo\Http\Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $productStock = $request->input('productStock');

            if(have_posts($productStock)) {

                $inventoriesId = [];

                foreach ($productStock as $inventoryId => $stock) {

                    $inventoriesId[] = $inventoryId;

                    if(!is_numeric($stock)) {
                        response()->error('Số lượng '.$stock.' không đúng định dạng kiểu số');
                    }

                    if($stock < 0) {
                        response()->error('Số lượng không được nhỏ hơn 0');
                    }
                }

                $inventories = Inventory::whereIn('id', $inventoriesId)->fetch();

                if(count($inventories) != count($inventoriesId)) {
                    response()->error('Không lấy được dữ liệu kho hàng của một trong các id đã truyền lên');
                }

                $productId = 0;

                if(count($inventories) == 1 && $inventories[0]->parent_id == 0) {
                    $productId = $inventories[0]->product_id;
                }
                else {

                    foreach ($inventories as $inventory) {

                        if(empty($productId)) {
                            $productId = $inventory->parent_id;
                            continue;
                        }

                        if($productId != $inventory->parent_id) {
                            response()->error('Id sản phẩm chính không trùng khớp');
                        }
                    }
                }

                $inventoriesHistory = [];

                $stockTotal = 0;

                $productUpOutStock = [];

                $productUpInStock = [];

                foreach ($productStock as $inventoryId => $stock) {

                    foreach ($inventories as $inventory) {

                        if($inventory->id == $inventoryId) {

                            if($stock != $inventory->stock)
                            {
                                $inventoriesUp = [
                                    'id'        => $inventoryId,
                                    'stock'     => $stock,
                                    'status'    => ($stock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value
                                ];

                                if($stock == 0) {
                                    $productUpOutStock[] = $inventory->product_id;
                                }
                                else {
                                    $productUpInStock[] = $inventory->product_id;
                                }

                                $result = Inventory::insert($inventoriesUp, $inventory);

                                if(!is_skd_error($result)) {

                                    $stockUp = $stock - $inventory->stock;

                                    $inventoriesHistory[] = [
                                        'inventory_id'  => $inventoryId,
                                        'product_id'    => $inventory->product_id,
                                        'branch_id'     => $inventory->branch_id,
                                        'message'       => \Stock\Model\History::message('product_update_quick', [
                                            'stockBefore'   => $inventory->stock,
                                            'stockAfter'    => $stock,
                                        ]),
                                        'action'        => ($stockUp > 0) ? 'cong' : 'tru',
                                        'type'          => 'stock',
                                        'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                                    ];

                                    $stockTotal += $stock;
                                }
                            }
                            else
                            {
                                $stockTotal += $inventory->stock;
                            }

                            break;
                        }
                    }
                }

                if(have_posts($inventoriesHistory))
                {
                    DB::table('inventories_history')->insert($inventoriesHistory);
                }

                if(have_posts($productUpInStock))
                {
                    DB::table('products')
                        ->whereIn('id', $productUpInStock)
                        ->update(['stock_status' => \Stock\Status\Inventory::in->value]);
                }

                if(have_posts($productUpOutStock))
                {
                    DB::table('products')
                        ->whereIn('id', $productUpOutStock)
                        ->update(['stock_status' => \Stock\Status\Inventory::out->value]);
                }

                $status = ($stockTotal > 0) ? \Stock\Status\Inventory::in->value : \Stock\Status\Inventory::out->value;

                DB::table('products')
                    ->where('id', $productId)
                    ->update(['stock_status' => $status]);

                response()->success(trans('ajax.update.success'), [
                    'productId' => $productId,
                    'status'    => $status,
                    'color'     => 'text-bg-'.\Stock\Status\Inventory::tryFrom($status)->badge(),
                    'label'     => \Stock\Status\Inventory::tryFrom($status)->label(). ' <i class="fa-thin fa-pen"></i>',
                ]);
            }
        }

        response()->error(trans('ajax.update.error'));
    }
}
Ajax::admin('Stock_Manager_Ajax::searchProducts');
Ajax::admin('Stock_Manager_Ajax::searchProductsByCategory');
Ajax::admin('Stock_Manager_Ajax::quickEditLoad');
Ajax::admin('Stock_Manager_Ajax::quickEditSave');
