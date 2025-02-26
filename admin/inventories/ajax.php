<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

Class StockInventoryAdminAjax
{
    static function purchaseOrder(SkillDo\Http\Request $request): void
    {
        if($request->input()) {

            if(!Auth::hasCap('inventory_edit')) {
                response()->error(trans('Bạn không có quyền sử dụng chức năng này'));
            }

            $validate = $request->validate([
                'stock'     => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(0)->errorMessage([
                    'notEmpty' => 'Bạn chưa nhập :attribute',
                    'min' => [
                        'numeric' => ':attribute phải lớn hơn :min'
                    ]
                ]),
                'productId' => Rule::make('Id sản phẩm')->notEmpty()->integer()->errorMessage([
                    'notEmpty' => 'Bạn chưa chọn :attribute',
                ]),
                'branchId'  => Rule::make('chi nhánh')->notEmpty()->integer()->errorMessage([
                    'notEmpty' => 'Bạn chưa chọn :attribute',
                ]),
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            $stock      = (int)$request->input('stock');

            $productId  = (int)$request->input('productId');

            $branchId   = (int)$request->input('branchId');

            $inventory  = Inventory::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->first();

            if(!have_posts($inventory))
            {
                response()->error(trans('Tồn kho chưa tồn tại'));
            }

            $branch = Branch::find($branchId);

            if(!have_posts($branch))
            {
                response()->error(trans('Kho hàng chứa sản phẩm không tồn tại hoặc không còn được sử dụng'));
            }

            $stockUp = $inventory->stock + $stock;

            $result = Inventory::whereKey($inventory->id)->update([
                'stock' => $stockUp,
                'status' => \Stock\Status\Inventory::in->value,
                'branch_name' => $branch->name,
            ]);

            if(is_skd_error($result)) {
                response()->error($result);
            }

            DB::table('products')
                ->where('id', $inventory->product_id)
                ->update(['stock_status' => \Stock\Status\Inventory::in->value]);

            if(!empty($inventory->parent_id)) {
                DB::table('products')
                    ->where('id', $inventory->parent_id)
                    ->update(['stock_status' => \Stock\Status\Inventory::in->value]);
            }

            \Stock\Model\History::insert([
                'inventory_id'  => $inventory->id,
                'product_id'  => $inventory->product_id,
                'branch_id'  => $inventory->branch_id,
                'message'       => \Stock\Model\History::message('inventory_update', [
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
                    'status' => \Stock\Status\Inventory::in->value,
                    'color'  => 'text-bg-'.\Stock\Status\Inventory::in->color(),
                    'label'  => \Stock\Status\Inventory::in->label()
                ],
            ]);
        }

        response()->error(trans('ajax.save.error'));
    }

    static function purchaseReturn(SkillDo\Http\Request $request): void
    {
        if($request->input()) {

            if(!Auth::hasCap('inventory_edit'))
            {
                response()->error(trans('Bạn không có quyền sử dụng chức năng này'));
            }

            $validate = $request->validate([
                'stock'     => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(0)->errorMessage([
                    'notEmpty' => 'Bạn chưa nhập :attribute',
                    'min' => [
                        'numeric' => ':attribute phải lớn hơn :min'
                    ]
                ]),
                'productId' => Rule::make('Id sản phẩm')->notEmpty()->integer()->errorMessage([
                    'notEmpty' => 'Bạn chưa chọn :attribute',
                ]),
                'branchId'  => Rule::make('chi nhánh')->notEmpty()->integer()->errorMessage([
                    'notEmpty' => 'Bạn chưa chọn :attribute',
                ]),
            ]);

            if ($validate->fails())
            {
                response()->error($validate->errors());
            }

            $stock      = (int)$request->input('stock');

            $productId  = (int)$request->input('productId');

            $branchId   = (int)$request->input('branchId');

            $inventory  = Inventory::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->first();

            if(!have_posts($inventory)) {
                response()->error(trans('Tồn kho chưa tồn tại'));
            }

            $branch = Branch::find($branchId);

            if(!have_posts($branch))
            {
                response()->error(trans('Kho hàng chứa sản phẩm không tồn tại hoặc không còn được sử dụng'));
            }

            if($stock > $inventory->stock) {
                response()->error(trans('Số lượng xuất kho của sản phẩm lớn hơn số lượng còn lại của sản phẩm'));
            }

            $stockUp = $inventory->stock - $stock;

            $status = ($stockUp == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value;

            $result = Inventory::whereKey($inventory->id)->update([
                'stock'         => $stockUp,
                'status'        => $status,
                'branch_name'   => $branch->name,
            ]);

            if(is_skd_error($result))
            {
                response()->error($result);
            }

            if($status == \Stock\Status\Inventory::in->value) {

                DB::table('products')
                    ->where('id', $inventory->product_id)
                    ->update(['stock_status' => \Stock\Status\Inventory::in->value]);

                if(!empty($inventory->parent_id)) {
                    DB::table('products')
                        ->where('id', $inventory->parent_id)
                        ->update(['stock_status' => \Stock\Status\Inventory::in->value]);
                }
            }
            else {
                if(empty($inventory->parent_id)) {
                    DB::table('products')
                        ->where('id', $inventory->product_id)
                        ->update(['stock_status' => \Stock\Status\Inventory::in->value]);
                }
                else {
                    $count = DB::table('products')
                        ->where('parent_id', $inventory->parent_id)
                        ->where('stock_status', \Stock\Status\Inventory::in->value)
                        ->where('id', '<>', $inventory->product_id)
                        ->count();

                    if($count == 0) {
                        DB::table('products')
                            ->where('id', $inventory->parent_id)
                            ->update(['stock_status' => \Stock\Status\Inventory::out->value]);
                    }
                }
            }

            \Stock\Model\History::create([
                'inventory_id'  => $inventory->id,
                'product_id'  => $inventory->product_id,
                'branch_id'  => $inventory->branch_id,
                'message'       => \Stock\Model\History::message('inventory_update', [
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
                    'color'  => 'text-bg-'.\Stock\Status\Inventory::tryFrom($status)->badge(),
                    'label'  => \Stock\Status\Inventory::tryFrom($status)->label()
                ],
            ]);
        }

        response()->error(trans('ajax.save.error'));
    }

    static function inventoryHistory(SkillDo\Http\Request $request, $model): void
    {
        if($request->input()) {

            $productId = (int)$request->input('productId');

            $branchId = (int)$request->input('branchId');

            $stock = \Stock\Model\History::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->where('type', 'stock')
                ->limit(100)
                ->orderByDesc('created')
                ->get();

            $reserved = \Stock\Model\History::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->where('type', 'reserved')
                ->limit(100)
                ->orderByDesc('created')
                ->get();

            response()->success(trans('ajax.load.success'), [
                'stock' => SkillDo\Utils::toArray($stock),
                'reserved' => SkillDo\Utils::toArray($reserved)
            ]);
        }

        response()->error(trans('ajax.load.error'));
    }
}
Ajax::admin('StockInventoryAdminAjax::purchaseOrder');
Ajax::admin('StockInventoryAdminAjax::purchaseReturn');
Ajax::admin('StockInventoryAdminAjax::inventoryHistory');