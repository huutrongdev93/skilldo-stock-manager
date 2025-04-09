<?php
class OrderReturnAdminButton
{
    static function orderDetail($order): void
    {
        if($order->status == \Ecommerce\Enum\Order\Status::COMPLETED->value && $order->status_pay == \Ecommerce\Enum\Order\StatusPay::COMPLETED->value)
        {
            echo Admin::button('blue', [
                'icon' => Admin::icon('add'),
                'text' => 'Trả hàng',
                'href' => Url::route('admin.order.returns.new').'?orderId='.$order->id,
            ]);
        }
    }
}

add_action('order_detail_header_action', 'OrderReturnAdminButton::orderDetail', 10, 1);