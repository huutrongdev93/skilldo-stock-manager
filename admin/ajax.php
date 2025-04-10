<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

Class SkdepotAjax
{
    static function changeUserBrand(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Id chi nhánh')->notEmpty()->integer()->min(1),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = (int)$request->input('id');

        $object = Branch::whereKey($id)->first();

        if(!have_posts($object)) {
            response()->error('Chi nhánh đã dừng hoạt động hoặc không tồn tại');
        }

        $user = Auth::user();

        $user->branch_id = $object->id;

        $user->save();

        \SkillDo\Cache::delete('branch_user_'.$user->id);

        response()->success('Thay đổi chi nhánh thành công');
    }

    static function searchProducts(\SkillDo\Http\Request $request): void
    {
        $branch = \Skdepot\Helper::getBranchCurrent();

        $selected = [
            'products.id',
            'products.code',
            'products.title',
            'products.attribute_label',
            'products.image',
            'products.hasVariation',
            'products.parent_id',
            DB::raw("MAX(cle_i.price_cost) AS price_cost")
        ];

        $query = Qr::select($selected);

        $query->leftJoin('inventories as i', function ($join) use ($branch) {
            $join->on('i.product_id', '=', 'products.id')->orOn('i.parent_id', '=', 'products.id');
            $join->where('i.branch_id', $branch->id);
        });

        $keyword = trim($request->input('keyword'));

        if(!empty($keyword))
        {
            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%'.$keyword.'%');
                $query->orWhere('code', 'like', '%'.$keyword.'%');
            });
        }

        $query
            ->limit(100)
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc')
            ->groupBy(['products.id', 'products.code',]);

        $products = \Ecommerce\Model\Product::widthVariation($query)->get();

        if(have_posts($products))
        {
            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $products
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
        $id = trim($request->input('id'));

        $selected = [
            'products.id',
            'products.code',
            'products.title',
            'products.attribute_label',
            'products.image',
            'products.hasVariation',
            'products.parent_id',
        ];

        $query = Qr::select($selected);

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
            $branch = \Skdepot\Helper::getBranchCurrent();

            $productsId = $products
                ->pluck('id')
                ->toArray();

            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
                ->where('branch_id', $branch->id)
                ->select('id', 'product_id', 'stock', 'reserved', 'price_cost')
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

                $item->price_cost = $inventory->price_cost;

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

            if(empty($productId))
            {
                response()->error(trans('Không xác định được id sản phẩm cần điều chỉnh số lượng kho hàng'));
            }

            $product = Product::find($productId);

            if(!have_posts($product))
            {
                response()->error(trans('Không xác định được sản phẩm cần điều chỉnh số lượng kho hàng'));
            }

            $branch = \Skdepot\Helper::getBranchCurrent();

            $inventories = [];

            if($product->hasVariation == 0)
            {
                $inventories = \Skdepot\Model\Inventory::where('product_id', $product->id)->where('branch_id', $branch->id)->get();

                foreach ($inventories as $inventory)
                {
                    $inventory->optionName = '';
                }
            }
            else
            {
                $variations = \Ecommerce\Model\Variation::where('parent_id', $productId)->select('id', 'title', 'attribute_label')->get();

                $inventories = \Skdepot\Model\Inventory::where('parent_id', $product->id)->where('branch_id', $branch->id)->get();

                foreach ($inventories as $inventory)
                {
                    $inventory->optionName = '';

                    foreach ($variations as $variation)
                    {
                        if ($variation->id === $inventory->product_ic)
                        {
                            $inventory->optionName = '<span style="font-weight: bold;">' . $variation->attribute_label . '</span>';
                            break;
                        }
                    }
                }
            }

            response()->success(trans('ajax.load.success'), [
                'branch' => \SkillDo\Utils::toArray($branch),
                'inventories' => \SkillDo\Utils::toArray($inventories),
            ]);
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

                $inventories = \Skdepot\Model\Inventory::whereIn('id', $inventoriesId)->fetch();

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
                                    'status'    => ($stock == 0) ? \Skdepot\Status\Inventory::out->value : \Skdepot\Status\Inventory::in->value
                                ];

                                if($stock == 0) {
                                    $productUpOutStock[] = $inventory->product_id;
                                }
                                else {
                                    $productUpInStock[] = $inventory->product_id;
                                }

                                $result = \Skdepot\Model\Inventory::insert($inventoriesUp, $inventory);

                                if(!is_skd_error($result)) {

                                    $stockUp = $stock - $inventory->stock;

                                    $inventoriesHistory[] = [
                                        'inventory_id'  => $inventoryId,
                                        'product_id'    => $inventory->product_id,
                                        'branch_id'     => $inventory->branch_id,
                                        'message'       => \Skdepot\Model\History::message('product_update_quick', [
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
                    \Skdepot\Model\History::inserts($inventoriesHistory);
                }

                if(have_posts($productUpInStock))
                {
                    DB::table('products')
                        ->whereIn('id', $productUpInStock)
                        ->update(['stock_status' => \Skdepot\Status\Inventory::in->value]);
                }

                if(have_posts($productUpOutStock))
                {
                    DB::table('products')
                        ->whereIn('id', $productUpOutStock)
                        ->update(['stock_status' => \Skdepot\Status\Inventory::out->value]);
                }

                $status = ($stockTotal > 0) ? \Skdepot\Status\Inventory::in->value : \Skdepot\Status\Inventory::out->value;

                DB::table('products')
                    ->where('id', $productId)
                    ->update(['stock_status' => $status]);

                response()->success(trans('ajax.update.success'), [
                    'productId' => $productId,
                    'status'    => $status,
                    'color'     => 'text-bg-'.\Skdepot\Status\Inventory::tryFrom($status)->badge(),
                    'label'     => \Skdepot\Status\Inventory::tryFrom($status)->label(). ' <i class="fa-thin fa-pen"></i>',
                ]);
            }
        }

        response()->error(trans('ajax.update.error'));
    }
}
Ajax::admin('SkdepotAjax::changeUserBrand');
Ajax::admin('SkdepotAjax::searchProducts');
Ajax::admin('SkdepotAjax::searchProductsByCategory');
Ajax::admin('SkdepotAjax::quickEditLoad');
Ajax::admin('SkdepotAjax::quickEditSave');
