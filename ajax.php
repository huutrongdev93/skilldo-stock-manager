<?php
use Illuminate\Database\Capsule\Manager as DB;
use JetBrains\PhpStorm\NoReturn;

Class Stock_Manager_Ajax {
    #[NoReturn]
    static function inventoryLoad(SkillDo\Http\Request $request): void
    {
        if($request->isMethod('post')) {

            $page    = $request->input('page');

            $page   = (is_null($page) || empty($page)) ? 1 : (int)$page;

            $limit  = $request->input('limit');

            $limit   = (is_null($limit) || empty($limit)) ? 10 : (int)$limit;

            $recordsTotal   = $request->input('recordsTotal');

            $args = Qr::set();

            $keyword = trim($request->input('keyword'));

            if(!empty($keyword)) {
                $args->where(function ($query) use ($keyword) {
                    $query->where('product_name', 'like', '%'.$keyword.'%');
                    $query->orWhere('product_code', 'like', '%'.$keyword.'%');
                });
            }

            $branch_id = (int)$request->input('branch');

            if($branch_id == 0) $branch_id = 1;

            $args->where('branch_id', $branch_id);

            $stock_status = $request->input('status');

            if(!empty($stock_status)) {
                $args->where('status', $stock_status);
            }
            /**
             * @since 7.0.0
             */
            $args = apply_filters('admin_inventories_controllers_index_args_before_count', $args);

            if(!is_numeric($recordsTotal)) {
                $recordsTotal = apply_filters('admin_inventories_controllers_index_count', Inventory::count($args), $args);
            }

            # [List data]
            $args->limit($limit)
                ->offset(($page - 1)*$limit)
                ->orderBy('order')
                ->orderBy('created', 'desc');

            $args = apply_filters('admin_inventories_controllers_index_args', $args);

            $objects = Inventory::gets($args);

            $variationsId = [];

            foreach ($objects as $object) {

                $object->optionName = '';

                if($object->parent_id != 0) {
                    $variationsId[] = $object->product_id;
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

                foreach ($objects as $item) {

                    foreach ($attributes_items_relationship as $attribute_item_relationship) {

                        if ($item->product_id == $attribute_item_relationship->variation_id) {

                            foreach ($attributesItem as $attributeItem) {

                                if ($attributeItem->id == $attribute_item_relationship->item_id) {

                                    $item->optionName .= '<span style="font-weight: bold;">' . $attributeItem->title . '</span>' . ' - ';

                                    break;
                                }
                            }
                        }
                    }

                    $item->optionName = trim($item->optionName, ' - ');
                }
            }

            $objects = apply_filters('admin_inventories_controllers_index_objects', $objects);

            $args = [
                'items' => $objects,
                'table' => 'inventories',
                'model' => model('inventories'),
                'module'=> 'inventories',
            ];

            $table = new AdminInventoriesTable($args);
            $table->get_columns();
            ob_start();
            $table->display_rows_or_message();
            $html = ob_get_contents();
            ob_end_clean();

            /**
             * Bulk Actions
             * @hook table_*_bulk_action_buttons Hook mới phiên bản 7.0.0
             */
            $buttonsBulkAction = apply_filters('table_inventories_bulk_action_buttons', []);

            $bulkAction = Admin::partial('include/table/header/bulk-action-buttons', [
                'actionList' => $buttonsBulkAction
            ]);

            $result['data'] = [
                'html'          => base64_encode($html),
                'bulkAction'    => base64_encode($bulkAction),
            ];
            $result['pagination']   = [
                'limit' => $limit,
                'total' => $recordsTotal,
                'page'  => (int)$page,
            ];

            response()->success(trans('ajax.load.success'), $result);
        }

        response()->error(trans('ajax.load.error'));
    }
    #[NoReturn]
    static function purchaseOrder(SkillDo\Http\Request $request, $model): void
    {
        if($request->input()) {

            if(!Auth::hasCap('inventory_edit')) {
                response()->error(trans('Bạn không có quyền sử dụng chức năng này'));
            }

            $stock      = (int)$request->input('stock');

            $id         = (int)$request->input('id');

            $branchId   = (int)$request->input('branchId');

            if(empty($branchId)) {
                response()->error(trans('Bạn chưa chọn kho hàng chứa sản phẩm'));
            }

            $inventory  = Inventory::where('id', $id)->where('branch_id', $branchId)->first();

            if(!have_posts($inventory)) {
                response()->error(trans('Tồn kho chưa tồn tại'));
            }

            if($stock <= 0) {
                response()->error(trans('Số lượng nhập thêm sản phẩm không được nhỏ hơn 1'));
            }

            $branch = Branch::get($branchId);

            if(!have_posts($branch)) {
                response()->error(trans('Kho hàng chứa sản phẩm không tồn tại hoặc không còn được sử dụng'));
            }

            $stockUp = $inventory->stock + $stock;

            $result = Inventory::where('id', $id)->update([
                'stock' => $stockUp,
                'status' => 'instock',
                'branch_name' => $branch->name,
            ]);

            if(is_skd_error($result)) {
                response()->error($result);
            }

            model('products')::where('id', $inventory->product_id)->update(['stock_status' => 'instock']);

            if(!empty($inventory->parent_id)) {
                model('products')::where('id', $inventory->parent_id)->update(['stock_status' => 'instock']);
            }

            InventoryHistory::insert([
                'inventory_id'  => $inventory->id,
                'message'       => InventoryHistory::message('inventory_update', [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $stockUp,
                ]),
                'action'        => 'cong',
                'type'          => 'stock',
            ]);

            response()->success(trans('ajax.save.success'), [
                'data' => [
                    'id'     => $inventory->id,
                    'stock'  => $stockUp,
                    'status' => 'instock',
                    'color'  => 'text-bg-'.InventoryHelper::status('instock', 'color'),
                    'label'  => InventoryHelper::status('instock', 'label')
                ],
            ]);
        }

        response()->error(trans('ajax.save.error'));
    }
    #[NoReturn]
    static function purchaseReturn(SkillDo\Http\Request $request, $model): void
    {
        if($request->input()) {

            if(!Auth::hasCap('inventory_edit')) {
                response()->error(trans('Bạn không có quyền sử dụng chức năng này'));
            }

            $stock      = (int)$request->input('stock');

            $id         = (int)$request->input('id');

            $branchId   = (int)$request->input('branchId');

            if(empty($branchId)) {
                response()->error(trans('Bạn chưa chọn kho hàng chứa sản phẩm'));
            }

            $inventory  = Inventory::where('id', $id)->where('branch_id', $branchId)->first();

            if(!have_posts($inventory)) {
                response()->error(trans('Tồn kho chưa tồn tại'));
            }

            if($stock <= 0) {
                response()->error(trans('Số lượng xuất kho của sản phẩm không được nhỏ hơn 1'));
            }

            $branch = Branch::get($branchId);

            if(!have_posts($branch)) {
                response()->error(trans('Kho hàng chứa sản phẩm không tồn tại hoặc không còn được sử dụng'));
            }

            if($stock > $inventory->stock) {
                response()->error(trans('Số lượng xuất kho của sản phẩm lớn hơn số lượng còn lại của sản phẩm'));
            }

            $stockUp = $inventory->stock - $stock;

            $status = ($stockUp == 0) ? 'outstock' : 'instock';

            $result = Inventory::where('id', $id)->update([
                'stock'         => $stockUp,
                'status'        => $status,
                'branch_name'   => $branch->name,
            ]);

            if(is_skd_error($result)) {
                response()->error($result);
            }

            if($status == 'instock') {

                model('products')::where('id', $inventory->product_id)->update(['stock_status' => 'instock']);

                if(!empty($inventory->parent_id)) {
                    model('products')::where('id', $inventory->parent_id)->update(['stock_status' => 'instock']);
                }
            }
            else {
                if(empty($inventory->parent_id)) {
                    model('products')::where('id', $inventory->product_id)->update(['stock_status' => 'instock']);
                }
                else {
                    $count = model('products')::where('parent_id', $inventory->parent_id)
                        ->where('stock_status', 'instock')
                        ->where('id', '<>', $inventory->product_id)
                        ->amount();

                    if($count == 0) {
                        model('products')::where('id', $inventory->parent_id)->update(['stock_status' => 'outstock']);
                    }
                }
            }

            InventoryHistory::insert([
                'inventory_id'  => $inventory->id,
                'message'       => InventoryHistory::message('inventory_update', [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $stockUp,
                ]),
                'action'        => 'tru',
                'type'          => 'stock',
            ]);

            response()->success(trans('ajax.save.success'), [
                'data' => [
                    'id'     => $inventory->id,
                    'stock'  => $stockUp,
                    'status' => $status,
                    'color'  => 'text-bg-'.InventoryHelper::status($status, 'color'),
                    'label'  => InventoryHelper::status($status, 'label')
                ],
            ]);
        }

        response()->error(trans('ajax.save.error'));
    }
    #[NoReturn]
    static function inventoryHistory(SkillDo\Http\Request $request, $model): void
    {
        if($request->input()) {

            $id = (int)$request->input('id');

            $stock = InventoryHistory::where('inventory_id', $id)->where('type', 'stock')->limit(100)->orderByDesc('created')->fetch();

            $reserved = InventoryHistory::where('inventory_id', $id)->where('type', 'reserved')->limit(100)->orderByDesc('created')->fetch();

            response()->success(trans('ajax.load.success'), [
                'stock' => SkillDo\Utils::toArray($stock),
                'reserved' => SkillDo\Utils::toArray($reserved)
            ]);
        }

        response()->error(trans('ajax.load.error'));
    }
    #[NoReturn]
    static function quickEditLoad(SkillDo\Http\Request $request): void
    {
        if($request->isMethod('post')) {

            $productId = (int)$request->input('productId');

            if(empty($productId)) {
                response()->error(trans('Không xác định được id sản phẩm cần điều chỉnh số lượng kho hàng'));
            }

            $product = Product::get($productId);

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
    #[NoReturn]
    static function quickEditSave(SkillDo\Http\Request $request, $model): void
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
                                    'status'    => ($stock == 0) ? 'outstock' : 'instock'
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
                                        'message'       => InventoryHistory::message('product_update_quick', [
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

                if(have_posts($inventoriesHistory)) {
                    DB::table('inventories_history')->insert($inventoriesHistory);
                }

                if(have_posts($productUpInStock)) {
                    model('products')::whereIn('id', $productUpInStock)->update(['stock_status' => 'instock']);
                }

                if(have_posts($productUpOutStock)) {
                    model('products')::whereIn('id', $productUpOutStock)->update(['stock_status' => 'outstock']);
                }

                $status = ($stockTotal > 0) ? 'instock' : 'outstock';

                model('products')::where('id', $productId)->update(['stock_status' => $status]);

                response()->success(trans('ajax.update.success'), [
                    'productId' => $productId,
                    'status'    => $status,
                    'color'     => 'text-bg-'.InventoryHelper::status($status, 'color'),
                    'label'     => InventoryHelper::status($status, 'label'). ' <i class="fa-thin fa-pen"></i>',
                ]);
            }
        }

        response()->error(trans('ajax.update.error'));
    }
}
Ajax::admin('Stock_Manager_Ajax::inventoryLoad');
Ajax::admin('Stock_Manager_Ajax::purchaseOrder');
Ajax::admin('Stock_Manager_Ajax::purchaseReturn');
Ajax::admin('Stock_Manager_Ajax::inventoryHistory');
Ajax::admin('Stock_Manager_Ajax::quickEditLoad');
Ajax::admin('Stock_Manager_Ajax::quickEditSave');

