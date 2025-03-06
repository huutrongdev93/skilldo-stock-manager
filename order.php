<?php

use Ecommerce\Enum\Order\Status;
use Illuminate\Database\Capsule\Manager as DB;

Class OrderInventory {

    public function __construct() {
        add_action('admin_order_status_before_update', 'OrderInventory::purchaseOrderCheck', 10, 2);
        add_action('admin_order_status_update', 'OrderInventory::statusChange', 10, 2);
        add_action('admin_order_status_pay_update', 'OrderInventory::statusPayChange', 10, 2);
    }

    static function statusChange($order, $status): void
    {
        $purchaseOrder = \Stock\Helper::config('purchaseOrder');

        if($status == Status::SHIPPING->value)
        {
            if($purchaseOrder === 'shipping')
            {
                OrderInventory::purchaseOrder($order, $status);
            }

            if($purchaseOrder === 'pay-shipping' && $order->status_pay == 'paid')
            {
                OrderInventory::purchaseOrder($order, $status);
            }
        }
        else if($status == Status::COMPLETED->value)
        {
            if($purchaseOrder === 'success') {
                OrderInventory::purchaseOrder($order, $status);
            }

            if($purchaseOrder === 'pay-success' && $order->status_pay == 'paid')
            {
                OrderInventory::purchaseOrder($order, $status);
            }
        }
        else if($status == Status::CANCELLED->value)
        {
            OrderInventory::purchaseReturn($order, $status);
        }
        else
        {
            $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

            if(!empty($purchaseOrderStatus)) {

                OrderInventory::purchaseReturnReserved($order, $status);
            }
        }
    }

    static function statusPayChange($order, $status): void
    {
        $purchaseOrder = \Stock\Helper::config('purchaseOrder');

        if($status == 'paid') {

            if($purchaseOrder === 'pay-shipping' && $order->status == Status::SHIPPING->value)
            {
                OrderInventory::purchaseOrder($order, $order->status);
            }

            if($purchaseOrder === 'pay-success' && $order->status == Status::COMPLETED->value)
            {
                OrderInventory::purchaseOrder($order, $order->status);
            }
        }
        else {

            $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

            if(!empty($purchaseOrderStatus))
            {
                OrderInventory::purchaseReturn($order, $order->status);
            }
        }
    }

    static function purchaseOrderCheck($order, $status): void
    {
        if(have_posts($order) && $status !== \Ecommerce\Enum\Order\Status::CANCELLED->value)
        {
            $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

            if(empty($purchaseOrderStatus)) {

                $products = OrderItem::where('order_id', $order->id)->get();

                $productsId = $products->pluck('product_id')->toArray();

                $inventories = Inventory::whereIn('product_id', $productsId)
                    ->where('branch_id', $order->branch_id)
                    ->get()
                    ->keyBy('product_id');

                $inventoriesUp = [];

                foreach ($products as $itemKey => $item) {

                    $productId = $item->product_id;

                    if(!$inventories->has($productId))
                    {
                        continue;
                    }

                    $inventory = $inventories->get($productId);

                    if (!isset($inventoriesUp[$productId]))
                    {
                        $inventoriesUp[$productId] = $inventory->reserved;
                    }

                    $inventoriesUp[$productId] = $inventoriesUp[$productId] - $item->quantity;

                    if ($inventoriesUp[$productId] < 0) {

                        response()->error(trans('Số lượng sản phẩm :name trong kho không đủ (hiện có :reserved)', [
                            'name' => $item->title,
                            'reserved' => $inventoriesUp[$productId]
                        ]));
                    }

                    unset($products[$itemKey]);
                }
            }
        }
    }

    static function purchaseOrder($order, $status): void
    {
        if(have_posts($order)) {

            $products = OrderItem::where('order_id', $order->id)->fetch();

            $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

            if(empty($purchaseOrderStatus)) {

                $productsId = [];

                $inventoriesHistory = [];

                foreach($order->items as $item) {
                    $productsId[] = $item->product_id;
                }

                $inventories = Inventory::whereIn('product_id', $productsId)->where('branch_id', $order->branch_id)->fetch();

                $inventoriesUp = [];

                $checkStock = false;

                foreach($products as $itemKey => $item) {

                    foreach ($inventories as $inventory) {

                        if($inventory->product_id == $item->product_id) {

                            if(!isset($inventoriesUp[$inventory->product_id])) {
                                $inventoriesUp[$inventory->product_id] = [
                                    'reserved'  => $inventory->reserved,
                                    'inventory' => $inventory,
                                ];
                            }

                            $inventoriesUp[$inventory->product_id]['reserved'] = $inventoriesUp[$inventory->product_id]['reserved'] - $item->quantity;

                            if($inventoriesUp[$inventory->product_id]['reserved'] < 0) {
                                $checkStock = true;
                                break;
                            }

                            unset($products[$itemKey]);
                        }
                    }

                    if($checkStock) {
                        break;
                    }
                }

                if(!$checkStock) {

                    foreach ($inventoriesUp as $productId => $inventoryUp) {

                        $updateData = $inventoryUp;

                        $inventory = $updateData['inventory'];

                        unset($updateData['inventory']);

                        Inventory::where('id', $inventory->id)->update($updateData);

                        $inventoriesHistory[] = [
                            'inventory_id'  => $inventory->id,
                            'message'       => InventoryHistory::message('order_change_reserved', [
                                'stockBefore'   => $inventory->reserved,
                                'stockAfter'    => $updateData['reserved'],
                                'code'          => $order->code,
                                'status'        => $status,
                            ]),
                            'action'        => 'tru',
                            'type'          => 'reserved',
                            'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                        ];
                    }

                    Order::updateMeta($order->id, 'inventory_status', \Stock\Helper::config('purchaseOrder'));

                    if(have_posts($inventoriesHistory)) {
                        DB::table('inventories_history')->insert($inventoriesHistory);
                    }
                }
            }
        }
    }

    static function purchaseReturnReserved($order, $status): void
    {
        if(have_posts($order)) {

            $productsId = [];

            $inventoriesHistory = [];

            foreach($order->items as $item) {
                $productsId[] = $item->product_id;
            }

            $inventories = Inventory::whereIn('product_id', $productsId)->where('branch_id', $order->branch_id)->fetch();

            $inventoriesUp = [];

            $productsId = [];

            $products = OrderItem::where('order_id', $order->id)->fetch();

            foreach($products as $itemKey => $item) {

                foreach ($inventories as $inventory) {

                    if($inventory->product_id == $item->product_id) {

                        if(!isset($inventoriesUp[$inventory->product_id])) {
                            $inventoriesUp[$inventory->product_id] = [
                                'reserved'  => $inventory->reserved,
                                'inventory' => $inventory,
                            ];
                        }

                        $inventoriesUp[$inventory->product_id]['reserved']  = $inventoriesUp[$inventory->product_id]['reserved'] + $item->quantity;

                        unset($products[$itemKey]);
                    }
                }
            }

            foreach ($inventoriesUp as $productId => $inventoryUp) {

                $updateData = $inventoryUp;

                $inventory = $updateData['inventory'];

                unset($updateData['inventory']);

                Inventory::where('id', $inventory->id)->update($updateData);

                $inventoriesHistory[] = [
                    'inventory_id'  => $inventory->id,
                    'message'       => InventoryHistory::message('order_change_reserved', [
                        'stockBefore'   => $inventory->reserved,
                        'stockAfter'    => $updateData['reserved'],
                        'code'          => $order->code,
                        'status'        => $status
                    ]),
                    'action'        => 'cong',
                    'type'          => 'reserved',
                    'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                ];
            }

            Order::updateMeta($order->id, 'inventory_status', '');

            if(have_posts($inventoriesHistory)) {
                DB::table('inventories_history')->insert($inventoriesHistory);
            }
        }
    }

    static function purchaseReturn($order, $status): void
    {
        if(have_posts($order)) {

            $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

            $productsId = [];

            $inventoriesHistory = [];

            foreach($order->items as $item) {
                $productsId[] = $item->product_id;
            }

            $inventories = Inventory::whereIn('product_id', $productsId)->where('branch_id', $order->branch_id)->fetch();

            $inventoriesUp = [];

            $productsId = [];

            $products = OrderItem::where('order_id', $order->id)->fetch();

            foreach($products as $itemKey => $item) {

                foreach ($inventories as $inventory) {

                    if($inventory->product_id == $item->product_id) {

                        if(!isset($inventoriesUp[$inventory->product_id])) {
                            $inventoriesUp[$inventory->product_id] = [
                                'stock'     => $inventory->stock,
                                'reserved'  => $inventory->reserved,
                                'inventory' => $inventory,
                            ];
                        }

                        if(empty($purchaseOrderStatus)) {
                            $inventoriesUp[$inventory->product_id]['reserved']  = $inventoriesUp[$inventory->product_id]['reserved'] - $item->quantity;
                        }
                        $inventoriesUp[$inventory->product_id]['stock']     = $inventoriesUp[$inventory->product_id]['stock'] + $item->quantity;
                        $inventoriesUp[$inventory->product_id]['status']    = ($inventoriesUp[$inventory->product_id]['stock'] > 0) ? \Stock\Status\Inventory::in->value : \Stock\Status\Inventory::out->value;

                        unset($products[$itemKey]);
                    }
                }
            }

            foreach ($inventoriesUp as $productId => $inventoryUp) {

                $updateData = $inventoryUp;

                $inventory = $updateData['inventory'];

                unset($updateData['inventory']);

                Inventory::where('id', $inventory->id)->update($updateData);

                DB::table('products')
                    ->where('id', $productId)
                    ->update(['stock_status' => \Stock\Status\Inventory::in->value]);

                if($inventory->parent_id != 0) {
                    $productsId[] = $inventory->parent_id;
                }

                $inventoriesHistory[] = [
                    'inventory_id'  => $inventory->id,
                    'message'       => InventoryHistory::message('order_change', [
                        'stockBefore'   => $inventory->stock,
                        'stockAfter'    => $updateData['stock'],
                        'code'          => $order->code,
                        'status'        => $status
                    ]),
                    'action'        => 'cong',
                    'type'          => 'stock',
                    'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                ];

                if(empty($purchaseOrderStatus)) {

                    $inventoriesHistory[] = [
                        'inventory_id'  => $inventory->id,
                        'message'       => InventoryHistory::message('order_change_reserved', [
                            'stockBefore'   => $inventory->reserved,
                            'stockAfter'    => $updateData['reserved'],
                            'code'          => $order->code,
                            'status'        => $status
                        ]),
                        'action'        => 'tru',
                        'type'          => 'reserved',
                        'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                    ];
                }
            }

            $productsId = array_unique($productsId);

            Order::updateMeta($order->id, 'inventory_status', '');

            foreach ($productsId as $productId) {

                DB::table('products')
                    ->where('id', $productId)
                    ->update([
                        'stock_status' => \Stock\Status\Inventory::in->value
                    ]);
            }

            if(have_posts($inventoriesHistory)) {
                DB::table('inventories_history')->insert($inventoriesHistory);
            }
        }
    }
}

new OrderInventory();

