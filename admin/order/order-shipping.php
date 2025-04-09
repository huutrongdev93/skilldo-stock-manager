<?php

use Ecommerce\Enum\Order\Status;
use Illuminate\Database\Capsule\Manager as DB;

Class StockOrderStatus {

    public function __construct() {
        add_action('admin_order_status_before_update', 'StockOrderStatus::purchaseOrderCheck', 10, 2);
        add_action('admin_order_status_update', 'StockOrderStatus::statusChange', 10, 2);
    }

    /**
     * Kiểm tra số lượng tồn kho trước khi trừ kho
     */
    static function purchaseOrderCheck($order, $status): void
    {
        if(have_posts($order) && $status !== \Ecommerce\Enum\Order\Status::CANCELLED->value)
        {
            $purchaseOrderStatus = (isset($order->inventory_status)) ? $order->inventory_status : Order::getMeta($order->id, 'inventory_status', true);

            if(empty($purchaseOrderStatus)) {

                $products = OrderItem::where('order_id', $order->id)->get();

                $productsId = $products->pluck('product_id')->toArray();

                $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
                    ->where('branch_id', $order->branch_id)
                    ->get()
                    ->keyBy('product_id');

                $inventoriesUp = [];

                foreach ($products as $item) {

                    $productId = $item->product_id;

                    if(!$inventories->has($productId))
                    {
                        continue;
                    }

                    $inventory = $inventories->get($productId);

                    if (!isset($inventoriesUp[$productId]))
                    {
                        $inventoriesUp[$productId] = $inventory->stock;
                    }

                    $inventoriesUp[$productId] = $inventoriesUp[$productId] - $item->quantity;

                    if ($inventoriesUp[$productId] < 0)
                    {
                        response()->error(trans('Số lượng sản phẩm :name trong kho không đủ', [
                            'name' => $item->title,
                        ]));
                    }
                }
            }
        }
    }

    static function statusChange($order, $status): void
    {
        if(have_posts($order) && !empty($order->branch_id))
        {
            if(!in_array($status, [
                Status::WAIT->value,
                Status::CONFIRM->value,
                Status::WAIT_PICKUP->value,
                Status::PICKUP_FAIL->value,
                Status::CANCELLED->value,
            ]))
            {
                StockOrderStatus::purchaseOrder($order, $status);
            }
            else if($status == Status::CANCELLED->value)
            {
                StockOrderStatus::purchaseReturn($order, $status);
            }
        }
    }

    /**
     * Xuất kho hàng khi đơn hàng bắt đầu vận chuyển
     */
    static function purchaseOrder($order, $status): void
    {
        $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

        if(empty($purchaseOrderStatus)) {

            $products = OrderItem::where('order_id', $order->id)->get();

            $productsId = $products->pluck('product_id')->toArray();

            $inventoriesHistory = [];

            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
                ->where('branch_id', $order->branch_id)
                ->get()
                ->keyBy('product_id');

            $inventoriesUp = [];

            foreach($products as $item) {

                $product_id = $item->product_id;

                if(!$inventories->has($product_id))
                {
                    continue;
                }

                $inventory = $inventories[$product_id];

                if(!isset($inventoriesUp[$product_id]))
                {
                    $inventoriesUp[$product_id] = [
                        'id'        => $inventory->id,
                        'stock'     => $inventory->stock,
                        'reserved'  => $inventory->reserved,
                        'quantity'  => 0,
                        'status'    => \Skdepot\Status\Inventory::in->value
                    ];
                }

                $inventoriesUp[$product_id]['stock'] -= $item->quantity;

                $inventoriesUp[$product_id]['reserved'] -= $item->quantity;

                $inventoriesUp[$product_id]['quantity'] += $item->quantity;
            }

            foreach ($inventoriesUp as $productId => $inventoryUp)
            {
                $inventory = $inventories[$productId];

                $inventoriesHistory[] = [
                    'inventory_id'  => $inventory->id,
                    'product_id'    => $inventory->product_id,
                    'branch_id'     => $inventory->branch_id,
                    //Đối tác
                    'partner_id'    => $order->customer_id ?? 0,
                    'partner_code'  => $order->customer_username ?? '',
                    'partner_name'  => $order->billing_fullname ?? '',
                    'partner_type'  => 'C',
                    //Thông tin
                    'cost'          => $inventory->price_cost,
                    'price'         => $inventory->price_cost*$inventoryUp['quantity'],
                    'quantity'      => $inventoryUp['quantity']*-1,
                    'start_stock'   => $inventory->stock,
                    'end_stock'     => $inventoryUp['stock'],
                    'target_id'     => $order->id,
                    'target_code'   => $order->code,
                    'target_name'   => 'Bán hàng',
                    'target_type'   => 'DH',
                ];

                if($inventoryUp['stock'] <= 0) {
                    $inventoryUp['status'] = \Skdepot\Status\Inventory::out->value;
                }

                unset($inventoriesUp[$productId]['quantity']);
            }

            if(have_posts($inventoriesUp))
            {
                DB::table('inventories')
                    ->upsert(
                        $inventoriesUp,
                        ['id'],           // unique key
                        ['stock', 'reserved', 'status']      // columns to update
                    );

                if(have_posts($inventoriesHistory))
                {
                    \Skdepot\Model\History::inserts($inventoriesHistory);
                }

                Order::updateMeta($order->id, 'inventory_status', $inventoriesUp);
            }
        }
    }

    /**
     * Nhập kho hàng khi đơn hàng bị hủy
     */
    static function purchaseReturn($order, $status): void
    {
        $purchaseOrderStatus = Order::getMeta($order->id, 'inventory_status', true);

        if(!empty($purchaseOrderStatus)) {

            $products = OrderItem::where('order_id', $order->id)->get();

            $inventoriesHistory = [];

            $productsId = $products->pluck('product_id')->toArray();

            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
                ->where('branch_id', $order->branch_id)
                ->get()
                ->keyBy('product_id');

            $inventoriesUp = [];

            foreach($products as $item) {

                $product_id = $item->product_id;

                if(!$inventories->has($product_id))
                {
                    continue;
                }

                $inventory = $inventories[$product_id];

                $priceCost = (
                    ($item->quantity*$item->cost + $inventory->stock*$inventory->price_cost) /
                    ($inventory->stock+$item->quantity));

                $inventoriesUp[] = [
                    'id'         => $inventory->id,
                    'stock'      => $inventory->stock + $item->quantity,
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
                    'cost'          => $item->cost,
                    'price'         => $item->cost*$item->quantity,
                    'quantity'      => $item->quantity,
                    'start_stock'   => $inventory->stock,
                    'end_stock'     => $inventory->stock + $item->quantity,
                    'target_id'     => $order->id,
                    'target_code'   => $order->code,
                    'target_name'   => 'Trả hàng',
                    'target_type'   => 'DH',
                ];
            }

            if(have_posts($inventoriesUp))
            {
                DB::table('inventories')
                    ->upsert(
                        $inventoriesUp,
                        ['id'],           // unique key
                        ['stock', 'price_cost', 'status']      // columns to update
                    );

                if(have_posts($inventoriesHistory))
                {
                    \Skdepot\Model\History::inserts($inventoriesHistory);
                }

                Order::updateMeta($order->id, 'inventory_status', '');
            }
        }
    }
}

new StockOrderStatus();

