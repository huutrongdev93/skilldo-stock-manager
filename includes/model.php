<?php
Class Inventory extends \SkillDo\Model\Model {

    protected string $table = 'inventories';

    static function deleteById($inventoriesID = 0): array|bool
    {
        return static::whereKey($inventoriesID)->remove();
    }
}

Class InventoryHistory extends \SkillDo\Model\Model {

    protected string $table = 'inventories_history';

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
        else if($action == 'order_change_reserved') {

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
        else if($action == 'inventory_update') {
            $message = '<span class="'.$action.'">[Kho hàng cập nhật]</span> '.$messageDefault;
        }
        else if($action == 'product_update') {
            $message = '<span class="'.$action.'">[Sản phẩm cập nhật]</span> '.$messageDefault;
        }
        else if($action == 'product_update_quick') {
            $message = '<span class="'.$action.'">[Cập nhật nhanh]</span> '.$messageDefault;
        }
        else if(!empty($action)) {
            $message = '<span class="custom">['.$action.']</span> '.$messageDefault;
        }

        if(empty($message)) {
            $message = $messageDefault;
        }

        return apply_filters('inventory_update_message', $message, $action, $args);
    }
}