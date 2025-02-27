<?php
namespace Stock\Model;

use Ecommerce\Enum\Order\Status;

Class History extends \SkillDo\Model\Model {

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
                Status::COMPLETED->value => '[Hoàn thành đơn '.$code.']',
                Status::PROCESSING->value => '[Vận chuyển đơn '.$code.']',
                Status::CANCELLED->value => '[Hủy đơn '.$code.']',
                default => '[ '.Status::tryFrom(($status == 'created') ? Status::WAIT->value : $status)->label().' đơn '.$code.']'
            };

            if($status == 'created') {
                $status = Status::WAIT->value;
            }

            $message = '<span class="text-status-'.Status::tryFrom($status)->badge().'">'.$message.'</span> '.$messageDefault;
        }
        else if($action == 'order_change_reserved') {

            $message = match ($status) {
                'created' => '[Đơn hàng mới '.$code.']',
                Status::COMPLETED->value => '[Hoàn thành đơn '.$code.']',
                Status::PROCESSING->value => '[Vận chuyển đơn '.$code.']',
                Status::CANCELLED->value => '[Hủy đơn '.$code.']',
                default => '[ '.Status::tryFrom(($status == 'created') ? Status::WAIT->value : $status)->label().' đơn '.$code.']'
            };

            if($status == 'created') {
                $status = Status::WAIT->value;
            }

            $message = '<span class="text-status-'.Status::tryFrom($status)->badge().'">'.$message.'</span> '.$messageReservedDefault;
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
        else if($action == 'purchase_update') {
            $message = '<span class="'.$action.'">[Phiếu nhập '.($args['purchaseCode'] ?? '').']</span> '.$messageDefault;
        }
        else if($action == 'purchase_returns_update') {
            $message = '<span class="'.$action.'">[Phiếu trả hàng '.($args['purchaseReturnCode'] ?? '').']</span> '.$messageDefault;
        }
        else if($action == 'damage_items_update') {
            $message = '<span class="'.$action.'">[Phiếu xuất hủy '.($args['damageCode'] ?? '').']</span> '.$messageDefault;
        }
        else if($action == 'stock_take_update') {
            $message = '<span class="'.$action.'">[Phiếu kiểm kho '.($args['stockTakeCode'] ?? '').']</span> '.$messageDefault;
        }
        else if(!empty($action)) {
            $message = '<span class="custom">['.$action.']</span> '.$messageDefault;
        }

        if(empty($message))
        {
            $message = $messageDefault;
        }

        return apply_filters('inventory_update_message', $message, $action, $args);
    }
}