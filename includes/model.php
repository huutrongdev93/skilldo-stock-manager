<?php
Class Inventory extends \SkillDo\Model\Model {

    static string $table = 'inventories';

    static function deleteById($inventoriesID = 0): array|bool
    {
        $inventoriesID = (int)Str::clear($inventoriesID);

        if($inventoriesID == 0) return false;

        $model = model(static::$table);

        $inventories  = static::get($inventoriesID);

        if(have_posts($inventories)) {

            do_action('delete_'.static::$table, $inventoriesID);

            if($model::where('id', $inventoriesID)->remove()) {

                do_action('delete_'.static::$table.'_success', $inventoriesID);

                return [$inventoriesID];
            }
        }

        return false;
    }

    static function deleteList( $inventoriesID = []) {

        if(have_posts($inventoriesID)) {

            $model = model(static::$table);

            if($model::whereIn('id', $inventoriesID)->remove()) {

                do_action('delete_inventories_list_trash_success', $inventoriesID );

                return $inventoriesID;
            }
        }

        return false;
    }
}

Class InventoryHistory extends \SkillDo\Model\Model {

    static string $table = 'inventories_history';

    static function message($action, $args) {
        //số lượng trước khi thay đổi
        $stockBefore = $args['stockBefore'];
        //Số lượng sau thay đổi
        $stockAfter = $args['stockAfter'];

        $stockChange = $stockAfter - $stockBefore;

        $mark = ($stockChange >= 0) ? '+' : '';

        $code = $args['code'] ?? '';

        $status = $args['status'] ?? '';

        $messageDefault = 'Thay đổi số lượng kho từ <b>'.$stockBefore.'</b> thành <strong>'.$stockAfter.'</strong> ('.$mark.$stockChange.')';

        $messageReservedDefault = 'Thay đổi số lượng kho <b>Khách đặt</b> từ <b>'.$stockBefore.'</b> thành <strong>'.$stockAfter.'</strong> ('.$mark.$stockChange.')';

        if($action == 'order_change') {

            $message = match ($status) {
                'created' => '[Đơn hàng mới '.$code.']',
                ORDER_COMPLETED => '[Hoàn thành đơn '.$code.']',
                ORDER_PROCESSING => '[Vận chuyển đơn '.$code.']',
                ORDER_CANCELLED => '[Hủy đơn '.$code.']',
                default => '[ '.OrderHelper::status(($status == 'created') ? ORDER_WAIT : $status, 'label').' đơn '.$code.']'
            };

            if($status == 'created') {
                $status = ORDER_WAIT;
            }

            $message = '<span class="text-status-'.OrderHelper::status($status, 'colorClass').'">'.$message.'</span> '.$messageDefault;
        }

        if($action == 'order_change_reserved') {

            $message = match ($status) {
                'created' => '[Đơn hàng mới '.$code.']',
                ORDER_COMPLETED => '[Hoàn thành đơn '.$code.']',
                ORDER_PROCESSING => '[Vận chuyển đơn '.$code.']',
                ORDER_CANCELLED => '[Hủy đơn '.$code.']',
                default => '[ '.OrderHelper::status(($status == 'created') ? ORDER_WAIT : $status, 'label').' đơn '.$code.']'
            };

            if($status == 'created') {
                $status = ORDER_WAIT;
            }

            $message = '<span class="text-status-'.OrderHelper::status($status, 'colorClass').'">'.$message.'</span> '.$messageReservedDefault;
        }

        if($action == 'inventory_update') {
            $message = '<span class="'.$action.'">[Kho hàng cập nhật]</span> '.$messageDefault;
        }

        if($action == 'product_update') {
            $message = '<span class="'.$action.'">[Sản phẩm cập nhật]</span> '.$messageDefault;
        }

        if($action == 'product_update_quick') {
            $message = '<span class="'.$action.'">[Cập nhật nhanh]</span> '.$messageDefault;
        }

        if(empty($message)) {
            $message = $messageDefault;
        }

        return apply_filters('inventory_update_message', $message, $action, $args);
    }
}