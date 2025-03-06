<?php
use Illuminate\Database\Capsule\Manager as DB;

Class CheckoutInventory {

    public function __construct() {
        add_filter('checkout_add_to_cart_errors', 'CheckoutInventory::checkAddToCart', 10, 4);
        add_filter('checkout_add_to_cart_errors', 'CheckoutInventory::checkAddToCart', 10, 4);
        add_filter('cart_update_quantity_errors', 'CheckoutInventory::checkUpdateCart', 10, 4);
        add_filter('cart_checkout_errors', 'CheckoutInventory::checkCheckout');
        add_filter('checkout_order_before_save', 'CheckoutInventory::checkoutUpBranchId', 10, 2);
        add_filter('checkout_order_after_save', 'CheckoutInventory::checkoutUpStock');
    }

    static function checkAddToCart($error, $cart, $product, $variation)
    {
        if($product->stock_status == 'outstock') {
            return new SKD_Error('outstock', trans('Sản phẩm này đã hết hàng liên hệ chúng tôi để nhận được thông tin ngay khi có hàng'));
        }

        if($product->hasVariation == 0) {
            $stock = Inventory::where('product_id', $product->id)->sum('stock');
        }
        else {
            $stock = Inventory::where('product_id', $variation->id)->sum('stock');
        }

        if($stock < $cart['qty']) {
            return new SKD_Error('outstock', trans('Số lượng đặt hàng của bạn lớn hơn số lượng tồn kho của sản phẩm'));
        }

        return $error;
    }

    static function checkUpdateCart($error, $item, $rowId, $qty)
    {
        if($item['qty'] < $qty) {

            if(!empty($item['variable'])) {
                $stock = Inventory::where('product_id', $item['variable'])->sum('stock');
            }
            else {
                $stock = Inventory::where('product_id', $item['id'])->sum('stock');
            }

            if($stock < $qty) {
                return new SKD_Error('outstock', trans('Số lượng đặt hàng của bạn lớn hơn số lượng tồn kho của sản phẩm'));
            }
        }

        return $error;
    }

    static function checkCheckout($error)
    {
        $carts = Scart::getItems();

        if(have_posts($carts)) {

            foreach ($carts as $item) {

                if(!empty($item['variable'])) {
                    $stock = Inventory::where('product_id', $item['variable'])->sum('stock');
                }
                else {
                    if(!empty($item['option']['product_id'])) {
                        $stock = Inventory::where('product_id', $item['option']['product_id'])->sum('stock');
                    }
                    else {
                        $stock = Inventory::where('product_id', $item['id'])->sum('stock');
                    }
                }

                if($stock < $item['qty']) {
                    return new SKD_Error('outstock', trans('Số lượng đặt hàng của sản phẩm '.$item['name'].' lớn hơn số lượng tồn kho còn lại'));
                }
            }
        }

        return $error;
    }

    static function checkoutUpBranchId($order, $metadata) {

        $branches = Branch::gets();

        if(have_posts($branches)) {

            if(count($branches) == 1) {

                $order['branch_id'] = $branches[0]->id;
            }
            else {

                $city = (!empty($metadata['other_delivery_address'])) ? $metadata['shipping_city'] : $metadata['billing_city'];

                $branch_default = 0;

                foreach ($branches as $branch) {
                    if(Str::isSerialized($branch->area)) {
                        $branch->area = unserialize($branch->area);
                        if(in_array($city, $branch->area) !== false) {
                            $order['branch_id'] = $branch->id;
                            break;
                        }
                    }
                    if($branch->default == 1) $branch_default = $branch->id;
                }

                if(empty($order['branch_id'])) $order['branch_id'] = $branch_default;
            }
        }

        return $order;
    }

    static function checkoutUpStock($order) {

        if(have_posts($order) && have_posts($order->items)) {

            $productsId = [];

            $inventoriesHistory = [];

            foreach($order->items as $item) {
                $productsId[] = $item->product_id;
            }

            $inventories = Inventory::whereIn('product_id', $productsId)->where('branch_id', $order->branch_id)->fetch();

            $inventoriesUp = [];

            $products = $order->items;

            $checkStock = false;

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

                        $inventoriesUp[$inventory->product_id]['stock'] = $inventoriesUp[$inventory->product_id]['stock'] - $item->quantity;

                        $inventoriesUp[$inventory->product_id]['reserved'] = $inventoriesUp[$inventory->product_id]['reserved'] + $item->quantity;

                        if($inventoriesUp[$inventory->product_id]['stock'] < 0) {
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

                $productsId = [];

                foreach ($inventoriesUp as $productId => $inventoryUp) {

                    $updateData = $inventoryUp;

                    $inventory = $updateData['inventory'];

                    unset($updateData['inventory']);

                    $updateData['status'] = ($updateData['stock'] == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value;

                    Inventory::where('id', $inventory->id)->update($updateData);

                    DB::table('products')
                        ->where('id', $productId)
                        ->update(['stock_status' => $updateData['status']]);

                    if($inventory->parent_id != 0) {
                        $productsId[] = $inventory->parent_id;
                    }

                    $inventoriesHistory[] = [
                        'inventory_id'  => $inventory->id,
                        'message'       => InventoryHistory::message('order_change', [
                            'stockBefore'   => $inventory->stock,
                            'stockAfter'    => $updateData['stock'],
                            'code'          => $order->code,
                            'status'         => 'created'
                        ]),
                        'action'        => 'tru',
                        'type'          => 'stock',
                        'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                    ];

                    $inventoriesHistory[] = [
                        'inventory_id'  => $inventory->id,
                        'message'       => InventoryHistory::message('order_change_reserved', [
                            'stockBefore'   => $inventory->reserved,
                            'stockAfter'    => $updateData['reserved'],
                            'code'          => $order->code,
                            'status'        => 'created'
                        ]),
                        'action'        => 'cong',
                        'type'          => 'reserved',
                        'created'       => gmdate('Y-m-d H:i:s', time() + 7*3600)
                    ];
                }

                $productsId = array_unique($productsId);

                if(have_posts($productsId)) {
                    foreach ($productsId as $productId) {

                        $stock = Inventory::where('parent_id', $productId)
                            ->where('branch_id', $order->branch_id)
                            ->sum('stock');

                        DB::table('products')
                            ->where('id', $productId)
                            ->update([
                                'stock_status' => ($stock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value
                            ]);
                    }
                }

                if(have_posts($inventoriesHistory)) {
                    DB::table('inventories_history')->insert($inventoriesHistory);
                }
            }
        }

        return $order;
    }
}

new CheckoutInventory();