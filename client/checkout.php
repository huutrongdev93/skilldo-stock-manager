<?php
use Illuminate\Database\Capsule\Manager as DB;

Class CheckoutInventory {

    public function __construct()
    {
        add_filter('checkout_add_to_cart_errors', 'CheckoutInventory::checkAddToCart', 10, 4);
        add_filter('cart_update_quantity_errors', 'CheckoutInventory::checkUpdateCart', 10, 4);
        add_filter('cart_checkout_errors', 'CheckoutInventory::checkCheckout');
        add_filter('checkout_order_before_save', 'CheckoutInventory::updateOrderData', 10, 4);
        add_filter('checkout_order_after_save', 'CheckoutInventory::checkoutUpStock');
    }

    /*
     * Kiểm tra trước khi thêm vào giỏ hàng
     */
    static function checkAddToCart($error, $cart, $product, $variation)
    {
        $branch = \Skdepot\Helper::getBranchWebsite();

        $stock = \Skdepot\Model\Inventory::where('product_id', (($product->hasVariation == 0) ? $product->id : $variation->id));

        if(have_posts($branch))
        {
            $stock->where('branch_id', $branch->id);
        }

        $stock = $stock->select('stock', 'reserved')->first();

        if(have_posts($stock))
        {
            $stock = $stock->stock - $stock->reserved;

            if($stock < $cart['qty'])
            {
                return new SKD_Error('outstock', trans('Số lượng đặt hàng của bạn lớn hơn số lượng tồn kho của sản phẩm'));
            }
        }

        return $error;
    }
    /*
     * Kiểm tra trước khi cập nhật vào giỏ hàng
     */
    static function checkUpdateCart($error, $item, $rowId, $qty)
    {
        if($item['qty'] < $qty) {

            $branch = \Skdepot\Helper::getBranchWebsite();

            if(!empty($item['variable']))
            {
                $productId = $item['variable'];
            }
            else
            {
                $productId = (!empty($item['option']['product_id'])) ? $item['option']['product_id'] : $item['id'];
            }

            $stock = \Skdepot\Model\Inventory::where('product_id', $productId);

            if(have_posts($branch))
            {
                $stock->where('branch_id', $branch->id);
            }

            $stock = $stock->select('stock', 'reserved')->first();

            if(have_posts($stock))
            {
                $stock = $stock->stock - $stock->reserved;

                if($stock < $qty)
                {
                    return new SKD_Error('outstock', trans('Số lượng đặt hàng của bạn lớn hơn số lượng tồn kho của sản phẩm'));
                }
            }
        }

        return $error;
    }
    /*
     * Kiểm tra trước khi đặt hàng
     */
    static function checkCheckout($error)
    {
        $carts = Scart::getItems();

        if(have_posts($carts)) {

            $branch = \Skdepot\Helper::getBranchWebsite();

            foreach ($carts as $item) {

                if(!empty($item['variable']))
                {
                    $productId = $item['variable'];
                }
                else
                {
                    $productId = (!empty($item['option']['product_id'])) ? $item['option']['product_id'] : $item['id'];
                }

                $stock = \Skdepot\Model\Inventory::where('product_id', $productId);

                if(have_posts($branch))
                {
                    $stock->where('branch_id', $branch->id);
                }

                $stock = $stock->select('stock', 'reserved')->first();

                if(have_posts($stock))
                {
                    $stock = $stock->stock - $stock->reserved;

                    if($stock < $item['qty'])
                    {
                        return new SKD_Error('outstock', trans('Số lượng đặt hàng của sản phẩm '.$item['name'].' lớn hơn số lượng tồn kho còn lại'));
                    }
                }
            }
        }

        return $error;
    }

    static function updateOrderData($order, $metadata, $data, $cart): array
    {
        $branch = \Skdepot\Helper::getBranchWebsite();

        if(have_posts($branch))
        {
            $order['branch_id'] = $branch->id;

            $productsId = array_map(function ($item) {
                return $item['variable'] ?? $item['id'];
            }, $cart);

            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
                ->where('branch_id', $branch->id)
                ->select('product_id', 'price_cost')
                ->get()
                ->keyBy('product_id');

            foreach ($order['items'] as $key => $item)
            {
                if(!$inventories->has($item['product_id']))
                {
                    response()->error('Không tìm thấy dữ liệu tồn kho của sản phẩm', $item);
                }

                $inventory = $inventories[$item['product_id']];

                $order['cost'] += $inventory->price_cost*$item['quantity'];

                $item['cost'] = $inventory->price_cost;

                $order['items'][$key] = $item;
            }
        }

        return $order;
    }

    static function checkoutUpStock($order)
    {
        if(have_posts($order) && have_posts($order->items)) {

            $productsId = [];

            foreach($order->items as $item) {
                $productsId[] = $item->product_id;
            }

            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $productsId)
                ->where('branch_id', $order->branch_id)
                ->get()
                ->keyBy('product_id');

            $inventoriesUp = [];

            foreach($order->items as $item)
            {
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
                        'reserved'  => $inventory->reserved,
                    ];
                }

                $inventoriesUp[$product_id]['reserved'] = $inventoriesUp[$product_id]['reserved'] + $item->quantity;
            }

            if(have_posts($inventoriesUp))
            {
                DB::table('inventories')
                    ->upsert(
                        $inventoriesUp,
                        ['id'],           // unique key
                        ['reserved']      // columns to update
                    );
            }
        }

        return $order;
    }
}

new CheckoutInventory();