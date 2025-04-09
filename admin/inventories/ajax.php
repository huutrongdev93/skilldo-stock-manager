<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

Class InventoryAdminAjax
{
    static function histories(SkillDo\Http\Request $request): void
    {
        $page   = $request->input('page');

        $page   = (empty($page) || !is_numeric($page)) ? 1 : (int)$page;

        $limit  = $request->input('limit');

        $limit  = (empty($limit)  || !is_numeric($page)) ? 10 : (int)$limit;

        $productId = (int)$request->input('productId');

        $branch = \Skdepot\Helper::getBranchCurrent();

        $total = \Skdepot\Model\History::where('product_id', $productId)
            ->where('branch_id', $branch->id)
            ->orderByDesc('created')
            ->count();

        $histories = \Skdepot\Model\History::where('product_id', $productId)
            ->where('branch_id', $branch->id)
            ->orderByDesc('created')
            ->limit($limit)
            ->offset(($page - 1)*$limit)
            ->get();

        if(have_posts($histories))
        {
            foreach($histories as $key => $history)
            {
                $history = $history->toObject();

                if(!empty($history->target_id))
                {
                    $attributes = [
                        'data-target-id' => $history->target_id,
                    ];

                    $attributes['data-target'] = match ($history->target_type)
                    {
                        \Skdepot\Prefix::adjustment->value => 'adjustment',
                        \Skdepot\Prefix::purchaseOrder->value => 'purchase-order',
                        \Skdepot\Prefix::purchaseReturn->value => 'purchase-return',
                        \Skdepot\Prefix::damageItem->value => 'damage-item',
                        \Skdepot\Prefix::transfer->value => 'transfer',
                        default => 'cash-flow',
                    };

                    if($history->target_type == \Skdepot\Prefix::purchaseOrder->value)
                    {
                        $attributes['data-target-cash-flow'] = 0;
                    }

                    $attributesStr = '';

                    foreach ($attributes as $attKey => $attValue)
                    {
                        $attributesStr .= $attKey.'="'.$attValue.'" ';
                    }

                    $history->target_code = '<a href="#" class="js_btn_target" '.$attributesStr.'>'.$history->target_code.'</a>';
                }

                $history->created = date('d/m/Y H:i', strtotime($history->created));

                $history->cost = Prd::price($history->cost);

                $history->price = Prd::price($history->price);

                $histories[$key] = $history;
            }
        }

        response()->success(trans('ajax.load.success'), [
            'items' => $histories,
            'pagination' => [
                'limit' => $limit,
                'total' => $total,
                'page'  => $page,
            ]
        ]);
    }

    static function onHand(SkillDo\Http\Request $request): void
    {
        $productId = (int)$request->input('productId');

        $inventories = \Skdepot\Model\Inventory::where('product_id', $productId)
            ->orderByDesc('created')
            ->get();

        response()->success(trans('ajax.load.success'), [
            'items' => $inventories,
        ]);
    }

    static function saveProduct(SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'productId' => Rule::make('Sản phẩm')->notEmpty()->integer()->min(1),
            'code' => Rule::make('Mã sản phẩm')->notEmpty(),
            'title' => Rule::make('Tên sản phẩm')->notEmpty(),
            'stock' => Rule::make('Tồn kho sản phẩm')->notEmpty()->integer()->min(0),
            'cost' => Rule::make('Giá vốn sản phẩm')->notEmpty()->integer()->min(0),
            'cost_scope' => Rule::make('Phạm vi cập nhật')->notEmpty()->in([1,2]),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = $request->input('productId');

        $product = \Ecommerce\Model\Product::widthVariation()->whereKey($id)->first();

        if(!have_posts($product))
        {
            response()->error('Sản phẩm đã bị xóa hoặc chưa được thêm vào');
        }

        $branch = \Skdepot\Helper::getBranchCurrent();

        $inventory = \Skdepot\Model\Inventory::where('product_id', $id)
            ->where('branch_id', $branch->id)
            ->first();

        if(!have_posts($inventory))
        {
            response()->error('Không tìm thấy tồn kho của sản phẩm này');
        }

        $title  = $request->input('title');

        $code   = $request->input('code');

        $stock  = (int)$request->input('stock');

        $cost   = (int)$request->input('cost');

        $cost_scope   = (int)$request->input('cost_scope');

        //Thay đổi tên sản phẩm
        $productUpdate = [];

        if($title !== $product->title)
        {
            $productUpdate['title'] = $title;
        }

        if($code !== $product->code)
        {
            $count = \Ecommerce\Model\Product::widthVariation()->where('code', $code)->count();

            if($count > 0)
            {
                response()->error('Mã sản phẩm '. $code .' đã được sử dụng');
            }

            $productUpdate['code'] = $code;
        }

        $inventoryUp = [];

        if($stock !== $inventory->stock)
        {
            $inventoryUp['stock'] = $stock;
        }

        if($cost !== $inventory->price_cost)
        {
            $inventoryUp['price_cost'] = $cost;
        }

        try {

            DB::beginTransaction();

            //Cập nhật sản phẩm
            if(!empty($productUpdate))
            {
                \Ecommerce\Model\Product::widthVariation()
                    ->where('id', $product->id)
                    ->update($productUpdate);

                //Kho hàng
                \Skdepot\Model\Inventory::where('product_id', $product->id)->update([
                    'product_name' => $productUpdate['title'] ?? $product->title,
                    'product_code' => $productUpdate['code'] ?? $product->code,
                ]);

                if(!empty($product->parent_id))
                {
                    if(!empty($productUpdate['title']))
                    {
                        \Ecommerce\Model\Product::where('id', $product->parent_id)->update([
                            'title' => $title,
                        ]);

                        \Ecommerce\Model\Product::widthVariation()->where('parent_id', $product->parent_id)->update([
                            'title' => $title,
                        ]);
                    }

                    \Skdepot\Model\Inventory::where('parent_id', $product->parent_id)->update([
                        'product_name' => $title,
                    ]);
                }

                if(!empty($productUpdate['code']))
                {
                    //Nhập hàng
                    \Skdepot\Model\PurchaseOrderDetail::where('product_code', $product->code)->update([
                        'product_code' => $code,
                    ]);
                    //Trả hàng hhập
                    \Skdepot\Model\PurchaseReturnDetail::where('product_code', $product->code)->update([
                        'product_code' => $code,
                    ]);
                    //Xuất hủy hàng
                    \Skdepot\Model\DamageItemDetail::where('product_code', $product->code)->update([
                        'product_code' => $code,
                    ]);
                    //Kiểm kho
                    \Skdepot\Model\StockTakeDetail::where('product_code', $product->code)->update([
                        'product_code' => $code,
                    ]);
                }
            }

            //Cập nhật kho hàng
            if(!empty($inventoryUp))
            {
                //Cập nhật giá vốn
                if(isset($inventoryUp['price_cost']))
                {
                    $modelInventory = \Skdepot\Model\Inventory::where('product_id', $product->id);

                    if($cost_scope == 1)
                    {
                        $modelInventory->where('branch_id', $branch->id);
                    }

                    $modelInventory->update([
                        'price_cost' => $cost
                    ]);

                    $inventory->price_cost = $cost;
                }

                //Cập nhật tồn kho
                if(isset($inventoryUp['stock']))
                {
                    $user = Auth::user();

                    $adjustment_quantity = $stock - $inventory->stock;

                    $adjustment_price    = ($stock - $inventory->stock)*$inventory->price_cost;

                    //Tạo phiếu kiểm kho
                    $stockTake = [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'user_id' => $user->id,
                        'user_name' => $user->firstname.' '.$user->lastname,

                        'balance_date' => time(),
                        'status' => \Skdepot\Status\StockTake::success->value,
                        // Tổng số lượng hàng thực tế
                        'total_actual_quantity' => $stock,
                        'total_actual_price' => $stock*$inventory->price_cost,
                        'total_increase_quantity' => 0,
                        'total_increase_price' => 0,
                        'total_reduced_quantity' => 0,
                        'total_reduced_price' => 0,
                    ];

                    // Tổng hàng tăng
                    if($adjustment_quantity > 0)
                    {
                        $stockTake['total_increase_quantity'] = $adjustment_quantity;
                        $stockTake['total_increase_price'] = $adjustment_price;
                    }

                    // Tổng hàng giảm
                    if($adjustment_quantity < 0)
                    {
                        $stockTake['total_reduced_quantity']= $adjustment_quantity;
                        $stockTake['total_reduced_price'] = $adjustment_price;
                    }

                    $stockTake['total_adjustment_quantity'] = $stockTake['total_increase_quantity'] + $stockTake['total_reduced_quantity'];

                    $stockTake['total_adjustment_price'] = $stockTake['total_increase_price'] + $stockTake['total_reduced_price'];

                    $stockTakeDetail = [
                        'product_id'         => $inventory->product_id,
                        'product_code'       => $productUpdate['code'] ?? $inventory->product_code,
                        'product_name'       => $productUpdate['title'] ?? $inventory->product_name,
                        'product_attribute'  => $product->attribute_label ?? '',
                        'stock'              => $inventory->stock,
                        'price'              => $inventory->price_cost,
                        'actual_quantity'    => $stock,
                        'adjustment_quantity'=> $adjustment_quantity,
                        'adjustment_price'   => $adjustment_price,
                        'status'             => \Skdepot\Status\StockTake::success->value,
                    ];

                    //Tạo phiếu kiểm kho hàng
                    $stockTakeId = \Skdepot\Model\StockTake::create($stockTake);

                    if(empty($stockTakeId) || is_skd_error($stockTakeId))
                    {
                        throw new \Exception('Tạo phiếu kiểm kho hàng thất bại');
                    }

                    if(empty($stockTake['code']))
                    {
                        $stockTake['code'] = \Skdepot\Helper::code(\Skdepot\Prefix::stockTake->value, $stockTakeId);
                    }

                    //Tạo chi tiết phiếu kiểm kho hàng
                    \Skdepot\Model\StockTakeDetail::create([
                        ...$stockTakeDetail,
                        'stock_take_id' => $stockTakeId
                    ]);

                    //Cập nhật kho
                    \Skdepot\Model\Inventory::where('product_id', $product->id)
                        ->where('branch_id', $branch->id)
                        ->update([
                            'stock' => $stock,
                            'status' => ($stock > 0) ? \Skdepot\Status\Inventory::in->value : \Skdepot\Status\Inventory::out->value
                        ]);

                    //Cập nhật lịch sử
                    \Skdepot\Model\Inventory::create([
                        'inventory_id'  => $inventory->id,
                        'product_id'    => $inventory->product_id,
                        'branch_id'     => $inventory->branch_id,
                        //Đối tác
                        'partner_id'   => $user->id ?? 0,
                        'partner_code' => $user->username ?? '',
                        'partner_name' => $user->firstname.' '.$user->lastname,
                        'partner_type' => !empty($user->id) ? 'C' : '',
                        //Đối tượng
                        'target_id'   => $stockTakeId,
                        'target_code' => $stockTake['code'],
                        'target_name' => 'Kiểm hàng',
                        'target_type' => \Skdepot\Prefix::stockTake->value,
                        //Thông tin
                        'cost'          => $inventory->price_cost,
                        'price'         => $stockTakeDetail['adjustment_price'],
                        'quantity'      => $stockTakeDetail['adjustment_quantity'],
                        'start_stock'   => $inventory->stock,
                        'end_stock'     => $stock,
                    ]);
                }
            }

            DB::commit();

            response()->success('Cập nhật thông tin thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Inventory update: '. $e->getMessage());

            response()->error($e->getMessage());
        }

    }
}
Ajax::admin('InventoryAdminAjax::histories');
Ajax::admin('InventoryAdminAjax::saveProduct');
Ajax::admin('InventoryAdminAjax::onHand');