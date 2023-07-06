<?php
Class StockOrderAction {

    public function __construct() {
    	add_filter('checkout_order_before_save', array($this, 'checkout'), 10, 2);
        add_filter('pre_insert_order_data', array($this, 'insert'), 10, 2);
        add_action('admin_order_status_wc-wait-confirm_action',  array($this, 'orderUnConfirm'), 1, 2 );
        add_action('admin_order_status_'.ORDER_CONFIRM.'_action',  array($this, 'orderConfirm'), 1, 2 );
        add_action('admin_order_status_'.ORDER_PROCESSING.'_action',  array($this, 'orderConfirm'), 1, 2 );
        add_action('admin_order_status_'.ORDER_SHIPPING.'_action',  array($this, 'orderConfirm'), 1, 2 );
        add_action('admin_order_status_'.ORDER_COMPLETED.'_action',  array($this, 'orderConfirm'), 1, 2 );
        add_action('admin_order_status_'.ORDER_CANCELLED.'_save',  array($this, 'orderCancelled'), 1, 1);
    }

    public function insert($data, $order) {
        if(!empty($order['branch_id'])) $data['branch_id'] = (int)$order['branch_id'];
        return $data;
    }

    public function checkout($order, $metadata_order) {

        $branches = Branch::gets();

        if(have_posts($branches)) {

            if(count($branches) == 1) {

                $order['branch_id'] = $branches[0]->id;
            }
            else {

                if(!empty($metadata_order['other_delivery_address'])) {
                    $city   = $metadata_order['shipping_city'];
                }
                else {
                    $city   = $metadata_order['billing_city'];
                }

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

    public function orderUnConfirm( $order, $status) {

        if($order->status != $status) {

            foreach ($order->items as $item) {

                $stock_process = Order::getItemMeta($item->id, 'stock_process', true);

                if($stock_process) {

                    $args = Qr::set('product_id', $item->product_id)->where('branch_id', $order->branch_id);

                    $inventory = Inventory::get($args);

                    if(have_posts($inventory)) {
                        if(!is_skd_error(Inventory::update(
                            ['stock' => $inventory->stock + $item->quantity],
                            Qr::set($inventory->id),
                            'order_change'
                        ))) {
                            Order::updateItemMeta($item->id, 'stock_process', false);
                        }
                    }
                }
            }
        }
    }

    public function orderConfirm( $order, $status) {

        if($order->status != $status) {

            foreach ($order->items as $item) {

                $stock_process = Order::getItemMeta($item->id, 'stock_process', true);

                if(empty($stock_process)) {

                    $args = Qr::set('product_id', $item->product_id)->where('branch_id', $order->branch_id);

                    $inventory = Inventory::get($args);

                    if(have_posts($inventory)) {

                        if(!is_skd_error(Inventory::update(
                            ['stock' => ($inventory->stock - $item->quantity)],
                            Qr::set($inventory->id),
                            'order_change'
                        ))) {

                            Order::updateItemMeta($item->id, 'stock_process', true);
                        }
                    }
                }
            }
        }
    }

    public function orderCancelled($order) {

        foreach ($order->items as $item) {

            $stock_process = Order::getItemMeta($item->id, 'stock_process', true);

            if($stock_process) {

                $args = Qr::set('product_id', $item->product_id)->where('branch_id', $order->branch_id);

                $inventory = Inventory::get($args);

                if(have_posts($inventory)) {
                    Inventory::update(
                        ['stock' => $inventory->stock + $item->quantity],
                        Qr::set($inventory->id),
                        'order_cancel'
                    );
                }
            }
        }
    }
}

new StockOrderAction();

