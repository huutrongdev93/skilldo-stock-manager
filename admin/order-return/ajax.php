<?php

use SkillDo\DB;
use SkillDo\Validate\Rule;

class OrderReturnAdminAjax
{
    static function loadProductsDetail(\SkillDo\Http\Request $request): void
    {
        $page   = $request->input('page');

        $page   = (empty($page) || !is_numeric($page)) ? 1 : (int)$page;

        $limit  = $request->input('limit');

        $limit  = (empty($limit)  || !is_numeric($page)) ? 10 : (int)$limit;

        $id  = $request->input('id');

        $query = Qr::where('order_return_id', $id);

        $selected = [
            'product_id',
            'product_name',
            'product_code',
            'product_attribute',
            'quantity',
            'price',
            'price_sell',
            'cost',
            'subtotal',
        ];

        $query->select($selected);

        # [Total decoders]
        $total = \Skdepot\Model\OrderReturnDetail::count(clone $query);

        # [List data]
        $query
            ->limit($limit)
            ->offset(($page - 1)*$limit);

        $objects = \Skdepot\Model\OrderReturnDetail::gets($query);

        foreach ($objects as $object)
        {
            $object->product_name .= ' - <span class="fw-bold sugg-attr">'.$object->product_attribute.'</span>';
        }

        # [created table]
        $table = new \Skdepot\Table\OrderReturn\ProductDetail([
            'items' => $objects,
        ]);

        $table->setTrash(true);

        $table->getColumns();

        $html = $table->renderBody();

        $result['data'] = [
            'html'          => base64_encode($html),
            'bulkAction'    => base64_encode(''),
            'recordsPublic' => $publicCount ?? 0,
            'recordsTrash'  => $trashCount ?? 0,
        ];

        $result['pagination']   = [
            'limit' => $limit,
            'total' => $total,
            'page'  => $page,
        ];

        response()->success(trans('ajax.load.success'), $result);
    }

    static function histories(SkillDo\Http\Request $request): void
    {
        $id = (int)$request->input('id');

        $branch = \Skdepot\Helper::getBranchCurrent();

        $histories = \Skdepot\Model\CashFlow::where('target_id', $id)
            ->where('target_type', \Skdepot\Prefix::orderReturn)
            ->where('branch_id', $branch->id)
            ->orderByDesc('created')
            ->get();

        if(have_posts($histories))
        {
            foreach($histories as $key => $history)
            {
                $history = $history->toObject();

                if(!empty($history->target_id))
                {
                    $attributes = [
                        'data-target-id' => $history->id,
                        'data-target' => 'cash-flow'
                    ];

                    $attributesStr = '';

                    foreach ($attributes as $attKey => $attValue)
                    {
                        $attributesStr .= $attKey.'="'.$attValue.'" ';
                    }

                    $history->code = '<a href="#" class="js_btn_target" '.$attributesStr.'>'.$history->code.'</a>';
                }

                $history->created = date('d/m/Y H:i', strtotime($history->created));

                $history->amount = Prd::price($history->amount);

                $history->status = Admin::badge(\Skdepot\Status\CashFlow::tryFrom($history->status)->badge(), \Skdepot\Status\CashFlow::tryFrom($history->status)->label());

                $histories[$key] = $history;
            }
        }

        response()->success(trans('ajax.load.success'), [
            'items' => $histories,
        ]);
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'orderId' => Rule::make('Đơn hàng')->notEmpty()->integer()->min(0),
            'discount' => Rule::make('Giảm giá')->notEmpty()->integer()->min(0),
            'surcharge' => Rule::make('Phí trả hàng')->notEmpty()->integer()->min(0),
            'totalPaid' => Rule::make('Tiền trả khách')->notEmpty()->integer()->min(0),
            'products.*.id' => Rule::make('id đặt hàng sản phẩm')->notEmpty()->integer()->min(0),
            'products.*.product_id' => Rule::make('id sản phẩm')->notEmpty()->integer()->min(0),
            'products.*.quantity' => Rule::make('Số lượng trả hàng')->notEmpty()->integer()->min(0),
            'products.*.price' => Rule::make('Giá trị trả hàng')->notEmpty()->integer()->min(0),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $orderId = (int)$request->input('orderId');

        $order = \Ecommerce\Model\Order::find($orderId);

        if(empty($order))
        {
            response()->error('Không tìm thấy đơn hàng');
        }

        if($order->status != \Ecommerce\Enum\Order\Status::COMPLETED->value || $order->status_pay != \Ecommerce\Enum\Order\StatusPay::COMPLETED->value)
        {
            response()->error('Đơn hàng chưa hoàn thành');
        }

        if(empty($order->branch_id))
        {
            response()->error('Đơn hàng chưa có chi nhánh');
        }

        $branch = Branch::widthStop()->whereKey($order->branch_id)->first();

        if(empty($branch))
        {
            response()->error('Chi nhánh của Đơn hàng không tồn tại');
        }

        $user = Auth::user();

        $customer = User::find($order->customer_id);

        $orderReturnAdd = [
            'branch_id'     => $branch->id,
            'branch_name'   => $branch->name,
            'user_id'       => $user->id,
            'user_name'     => $user->firstname.' '.$user->lastname,
            'customer_id'   => $order->customer_id,
            'customer_name' => $order->customer_name,
            'order_id'      => $order->id,
            'order_code'    => $order->code,
            'discount'      => (int)$request->input('discount'),
            'surcharge'      => (int)$request->input('surcharge'),
            'total_quantity' => 0,
            'total_return'   => 0,
            'total_paid'     => (int)$request->input('totalPaid'),
            'status'         => \Skdepot\Status\OrderReturn::success->value,
        ];

        $requestItems = $request->input('products');

        $requestItems = \Illuminate\Support\Collection::make($requestItems)->keyBy('id');

        $orderReturnItems = \Skdepot\Model\OrderReturnDetail::where('order_id', $order->id)
            ->where('status', \Skdepot\Status\OrderReturn::success->value)
            ->get();

        $orderReturnItemsAdd = [];

        $orderItemsUp = [];

        $productsId = [];

        $totalQuantity = 0;

        foreach ($order->items as $item)
        {
            $totalQuantity += $item->quantity;

            foreach ($orderReturnItems as $orderReturnItem)
            {
                if($orderReturnItem->detail_id == $item->id)
                {
                    $item->quantity = $item->quantity - $orderReturnItem->quantity;
                }
            }

            if(!$requestItems->has($item->id))
            {
                continue;
            }

            $requestItem = $requestItems->get($item->id);

            if($requestItem['product_id'] != $item->product_id)
            {
                response()->error('Sản phẩm không hợp lệ');
            }

            if($requestItem['quantity'] == 0)
            {
                unset($requestItems[$item->id]);
                continue;
            }

            if($requestItem['quantity'] > $item->quantity)
            {
                response()->error('Số lượng trả hàng lớn hơn số lượng khách đã mua');
            }

            $orderReturnAdd['total_quantity'] += $requestItem['quantity'];

            $orderReturnAdd['total_return'] += $requestItem['quantity']*$requestItem['price'];

            $orderReturnItemsAdd[] = [
                'order_id'      => $item->order_id,
                'detail_id'     => $item->id,
                'product_id'    => $item->product_id,
                'product_name'  => $requestItem['title'],
                'product_attribute' => $requestItem['attribute_label'],
                'product_code'  => $item->code,
                'cost'          => $item->cost,
                'price_sell'    => $item->price,
                'price'         => $requestItem['price'],
                'quantity'      => $requestItem['quantity'],
                'subtotal'     => $requestItem['price']*$requestItem['quantity'],
                'status'        => \Skdepot\Status\OrderReturn::success->value
            ];

            $orderItemsUp[] = [
                'id' => $item->id,
                'return_quantity' => $item->return_quantity + $requestItem['quantity'],
                'return_price' => $item->return_price + $requestItem['quantity']*$requestItem['price'],
            ];

            $productsId[] = $item->product_id;

            unset($requestItems[$item->id]);
        }

        if(!$requestItems->isEmpty())
        {
            response()->error('Sản phẩm trả lại không thuộc đơn hàng');
        }

        if(empty($orderReturnItemsAdd))
        {
            response()->error('Phiếu trả hàng không có sản phẩm nào được trả');
        }

        if($orderReturnAdd['discount'] > ($orderReturnAdd['surcharge'] + $orderReturnAdd['total_return']))
        {
            response()->error('Giảm giá không được lớn hơn giá trị trả lại cho khách hàng');
        }

        $orderReturnAdd['total_payment'] = $orderReturnAdd['surcharge'] + $orderReturnAdd['total_return'] - $orderReturnAdd['discount'];

        $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
            ->where('branch_id', $branch->id)
            ->get()
            ->keyBy('product_id');

        $inventoriesUpdate = [];

        $inventoriesHistory = [];

        foreach ($orderReturnItemsAdd as $item)
        {
            if(!$inventories->has($item['product_id']))
            {
                response()->error('Không tìm thấy kho hàng của sản phẩm '.$item['title']);
            }

            $inventory = $inventories->get($item['product_id']);

            $newStock = $inventory->stock + $item['quantity'];

            $priceCost = (
                ($item['quantity']*$item['cost'] + $inventory->stock*$inventory->price_cost) /
                ($inventory->stock+$item['quantity']));

            $inventoriesUpdate[] = [
                'id'         => $inventory->id,
                'stock'      => $newStock,
                'price_cost' => $priceCost,
                'status'     => \Skdepot\Status\Inventory::in->value,
            ];

            $inventoriesHistory[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                //Đối tác
                'partner_id'    => $order->customer_id ?? 0,
                'partner_code'  => $order->customer_username ?? '',
                'partner_name'  => $order->billing_fullname ?? '',
                'partner_type'  => (!empty($order->customer_id)) ? 'C' : '',
                //Thông tin
                'cost'          => $item['cost'],
                'price'         => $item['cost']*$item['quantity'],
                'quantity'      => $item['quantity'],
                'start_stock'   => $inventory->stock,
                'end_stock'     => $inventory->stock + $item['quantity'],
                //'target_id'     => $order->id,
                //'target_code'   => $order->code,
                'target_name'   => 'Trả hàng',
                'target_type'   => \Skdepot\Prefix::orderReturn,
            ];
        }

        $order->total_return_quantity += $orderReturnAdd['total_quantity'];

        $order->total_return_price += $orderReturnAdd['total_return'];

        $order->total_return_payment += $orderReturnAdd['total_payment'];

        if($order->total_return_quantity > $totalQuantity)
        {
            response()->error('Số lượng trả hàng lớn hơn số lượng đặt mua');
        }

        try {

            DB::beginTransaction();

            //Tạo phiếu nhập hàng
            $id = \Skdepot\Model\OrderReturn::create($orderReturnAdd);

            if(empty($id) || is_skd_error($id))
            {
                response()->error('Tạo phiếu trả hàng thất bại');
            }

            if(empty($orderReturnAdd['code']))
            {
                $orderReturnAdd['code'] = \Skdepot\Helper::code(\Skdepot\Prefix::orderReturn->value, $id);
            }

            // Cập nhật mã phiếu vào lịch sử kho
            foreach ($inventoriesHistory as &$history)
            {
                $history['target_id'] = $id;
                $history['target_code'] = $orderReturnAdd['code'];
                $history['target_name'] = 'Trả hàng';
                $history['target_type'] = \Skdepot\Prefix::orderReturn->value;
            }

            // Cập nhật purchase_order_id
            foreach ($orderReturnItemsAdd as &$detail)
            {
                $detail['order_return_id'] = $id;

                $order->total_return_cost = $detail['cost']*$detail['quantity'];
            }

            \Skdepot\Model\OrderReturnDetail::inserts($orderReturnItemsAdd);

            //Cập nhật kho hàng
            \Skdepot\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            \Skdepot\Model\History::inserts($inventoriesHistory);

            //Tạo phiếu chi
            if(!empty($orderReturnAdd['total_paid']))
            {
                //Tạo phiếu chi
                $code = \Skdepot\Helper::code(\Skdepot\Prefix::cashFlowOrderReturn->value, $id);

                $idCashFlow = \Skdepot\Model\CashFlow::create([
                    'code'          => $code,
                    'branch_id'     => $orderReturnAdd['branch_id'],
                    'branch_name'   => $orderReturnAdd['branch_name'],
                    //Người chi
                    'user_id'       => $orderReturnAdd['user_id'],
                    'user_name'     => $orderReturnAdd['user_name'],
                    //Người nhận
                    'partner_id'    => $orderReturnAdd['customer_id'],
                    'partner_code'  => '',
                    'partner_name'  => $orderReturnAdd['customer_name'],
                    'address' => '',
                    'phone' => '',
                    'partner_type' => 'C',

                    //Loại
                    'group_id'   => \Skdepot\CashFlowGroup\Transaction::orderReturn->id(),
                    'group_name' => \Skdepot\CashFlowGroup\Transaction::orderReturn->label(),
                    'origin' => 'purchase',
                    'method' => 'cash',
                    'amount' => $orderReturnAdd['total_paid']*-1,

                    'target_id'     => $id,
                    'target_code'   => $orderReturnAdd['code'],
                    'target_type'   => \Skdepot\Prefix::orderReturn->value,
                    'time'          => time(),
                    'status'        => \Skdepot\Status\CashFlow::success->value,
                    'user_created'  => Auth::id()
                ]);

                if(empty($idCashFlow) || is_skd_error($idCashFlow))
                {
                    throw new \Exception('Không tạo được phiếu chi');
                }
            }

            if(!empty($customer) && $orderReturnAdd['total_paid'] != $orderReturnAdd['total_payment'])
            {
                $debt = $orderReturnAdd['total_payment'] - $orderReturnAdd['total_paid'];

                \Skdepot\Model\UserDebt::create([
                    'before'            => $customer->debt,
                    'amount'            => $debt,
                    'balance'           => $customer->debt + $debt,
                    'partner_id'        => $customer->id,
                    'target_id'         => $id,
                    'target_code'       => $orderReturnAdd['code'],
                    'target_type'       => \Skdepot\Prefix::orderReturn,
                    'target_type_name'  => 'Trả hàng',
                    'time'              => time()
                ]);

                $customer->debt += $debt;

                $customer->save();
            }

            $order->save();

            \Ecommerce\Model\OrderItem::updateBatch($orderItemsUp, 'id');

            DB::commit();

            response()->success('Tạo phiếu trả hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu trả hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function cancel(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'data' => Rule::make('Phiếu trả hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('data');

        $object = \Skdepot\Model\OrderReturn::find($id);

        if(empty($object))
        {
            response()->error('Phiếu trả hàng không còn trên hệ thống');
        }

        if($object->status === \Skdepot\Status\PurchaseOrder::cancel->value)
        {
            response()->error('Phiếu trả hàng này đã được hủy');
        }

        $order = \Ecommerce\Model\Order::find($object->order_id);

        if(empty($order))
        {
            response()->error('Đơn hàng của phiếu trả hàng không còn trên hệ thống');
        }

        $items = \Skdepot\Model\OrderReturnDetail::where('order_return_id', $object->id)
            ->where('status', \Skdepot\Status\OrderReturn::success->value)
            ->get();

        $inventories = Skdepot\Model\Inventory::whereIn('product_id', $items->pluck('product_id')->toArray())
            ->where('branch_id', $object->branch_id)
            ->get()
            ->keyBy('product_id');

        $inventoriesUpdate = [];

        $inventoriesHistory = [];

        $order->total_return_quantity -= $object->total_quantity;

        $order->total_return_price -= $object->total_return;

        $order->total_return_payment -= $object->total_payment;

        $orderItemsUp = [];

        foreach ($items as $item)
        {
            if(!$inventories->has($item->product_id))
            {
                response()->error('Không tìm thấy kho hàng của sản phẩm '.$item->product_name);
            }

            $inventory = $inventories->get($item->id);

            $newStock = $inventory->stock - $item['quantity'];

            if($newStock < 0)
            {
                response()->error('Mã hàng '.$item->product_code.' không đủ tồn kho để hủy');
            }

            $priceCost = (($inventory->stock*$inventory->price_cost - $item->quantity*$item->cost) / $newStock);

            $inventoriesUpdate[] = [
                'id'         => $inventory->id,
                'stock'      => $newStock,
                'price_cost' => $priceCost,
                'status'     => ($newStock == 0) ? \Skdepot\Status\Inventory::out->value : \Skdepot\Status\Inventory::in->value,
            ];

            $inventoriesHistory[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                //Đối tác
                'partner_id'    => $object->customer_id,
                'partner_name'  => $object->customer_name,
                'partner_type'  => 'C',
                //Thông tin
                'cost'          => $item->cost,
                'price'         => $item->cost*$item->quantity,
                'quantity'      => $item->quantity*-1,
                'start_stock'   => $inventory->stock,
                'end_stock'     => $inventory->stock - $item->quantity,
                'target_id'     => $object->id,
                'target_code'   => $object->code,
                'target_name'   => 'Trả hàng',
                'target_type'   => \Skdepot\Prefix::orderReturn,
            ];

            foreach ($order->items as $orderItem)
            {
                if($orderItem->id == $item->detail_id)
                {
                    $orderItemsUp[] = [
                        'id' => $item->id,
                        'return_quantity' => $item->return_quantity - $item->quantity,
                        'return_price' => $item->return_price - $item->quantity*$item->price,
                    ];
                }
            }
        }

        try {

            DB::beginTransaction();

            \Skdepot\Model\OrderReturnDetail::where('order_return_id', $object->id)
                ->where('status', \Skdepot\Status\OrderReturn::success->value)
                ->update([
                    'status' => \Skdepot\Status\OrderReturn::cancel->value,
                ]);

            \Skdepot\Model\OrderReturn::whereKey($object->id)->update([
                'status' => \Skdepot\Status\OrderReturn::cancel->value,
            ]);

            //Cập nhật kho hàng
            \Skdepot\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            \Skdepot\Model\History::inserts($inventoriesHistory);

            //Xóa phiếu chi
            \Skdepot\Model\CashFlow::widthChildren()
                ->where('target_id', $object->id)
                ->where('target_code', $object->code)
                ->where('target_type', \Skdepot\Prefix::orderReturn->value)
                ->delete();

            $order->save();

            \Ecommerce\Model\OrderItem::updateBatch($orderItemsUp, 'id');

            DB::commit();

            response()->success('Hủy phiếu trả hàng thành công', [
                'status' => Admin::badge(\Skdepot\Status\OrderReturn::cancel->badge(), 'Đã hủy')
            ]);
        }
        catch (\Exception $e)
        {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu trả hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function print(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu trả hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Skdepot\Model\OrderReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng không còn trên hệ thống');
        }

        $object->created = date('d/m/Y H:i', strtotime($object->created));

        $object->discount = Prd::price($object->discount);

        $object->surcharge = Prd::price($object->surcharge);

        $object->total_return = Prd::price($object->total_return);

        $object->total_payment = Prd::price($object->total_payment);

        $object->total_paid = Prd::price($object->total_paid);

        $products = \Skdepot\Model\OrderReturnDetail::where('order_return_id', $object->id)->get();

        response()->success('Dữ liệu print', [
            'purchase' => $object->toObject(),
            'items' => $products->map(function ($item, $key) {
                $item->stt = $key+1;
                $item->cost = Prd::price($item->cost);
                $item->price_sell = Prd::price($item->price_sell);
                $item->price = Prd::price($item->price);
                $item->subtotal = Prd::price($item->subtotal);
                return $item->toObject();
            })
        ]);
    }

    static function export(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'export' => Rule::make('Loại xuất dữ liệu')->notEmpty()->in(['page', 'search', 'checked']),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $type = $request->input('export');

        $query = Qr::orderBy('created');

        if($type === 'page')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu trả hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'checked')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu trả hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'search') {

            $search = $request->input('search');

            if(!empty($search['keyword']))
            {
                $query->orWhere('code', 'like', '%'.$search['keyword'].'%');
            }

            if(!empty($search['branch_id']))
            {
                $branch_id = (int)$search['branch_id'];

                $query->where('branch_id', $branch_id);
            }

            if(!empty($search['status']))
            {
                $query->where('status', $search['status']);
            }
        }

        $objects = \Skdepot\Model\OrderReturn::gets($query);

        if(empty($objects))
        {
            response()->error(trans('Không có phiếu trả hàng nào để xuất'));
        }

        foreach ($objects as $object)
        {
            $object->created = date('d/m/Y H:i', strtotime($object->created));
        }

        $export = new \Skdepot\Export();

        $export->header('code', 'Mã trả hàng', function($item) {
            return $item->code ?? '';
        });

        $export->header('created', 'Ngày trả', function($item) {
            return $item->created;
        });

        $export->header('branch_name', 'Chi nhánh', function($item) {
            return $item->branch_name;
        });

        $export->header('user_name', 'Người trả', function($item) {
            return $item->user_name;
        });

        $export->header('customer_name', 'Khách hàng', function($item) {
            return $item->customer_name;
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->quantity);
        });

        $export->header('subtotal', 'Giá trị trả', function($item) {
            return number_format($item->subtotal);
        });

        $export->header('discount', 'Giảm giá', function($item) {
            return number_format($item->discount);
        });

        $export->header('discount', 'Phí trả hàng', function($item) {
            return number_format($item->surcharge);
        });

        $export->header('total_payment', 'Cần Trả', function($item) {
            return number_format($item->total_payment);
        });

        $export->header('total_paid', 'Đã Trả', function($item) {
            return number_format($item->total_paid);
        });

        $export->setTitle('DSPhieuTraHang_'.time());

        $export->data($objects);

        $path = $export->export('assets/export/order-return/', 'DanhSachPhieuTraHang_'.time().'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu trả hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Skdepot\Model\OrderReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng không còn trên hệ thống');
        }

        $products = \Skdepot\Model\OrderReturnDetail::where('order_return_id', $object->id)->get();

        $export = new \Skdepot\Export();

        $export->header('code', 'Mã hàng', function($item) {
            return $item->product_code ?? '';
        });

        $export->header('name', 'Tên hàng', function($item) {
            return $item->product_name .' '.Str::clear($item->product_attribute);
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->quantity ?? 0);
        });

        $export->header('price_sell', 'Giá bán', function($item) {
            return number_format($item->price_sell ?? 0);
        });

        $export->header('price', 'Giá trả hàng', function($item) {
            return number_format($item->price ?? 0);
        });

        $export->header('total', 'Thành tiền', function($item) {
            return number_format($item->subtotal);
        });

        $export->setTitle('DSChiTietTraHang_'.$object->code);

        $export->data($products);

        $path = $export->export('assets/export/order-return/', 'DanhSachChiTietTraHang_'.$object->code.'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }
}

Ajax::admin('OrderReturnAdminAjax::save');
Ajax::admin('OrderReturnAdminAjax::cancel');
Ajax::admin('OrderReturnAdminAjax::loadProductsDetail');
Ajax::admin('OrderReturnAdminAjax::histories');
Ajax::admin('OrderReturnAdminAjax::print');
Ajax::admin('OrderReturnAdminAjax::export');
Ajax::admin('OrderReturnAdminAjax::exportDetail');