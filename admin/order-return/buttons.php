<?php
class OrderReturnAdminButton {

    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.order.returns.new')
        ]);
        $buttons[] = Admin::button('blue', [
            'icon' => Admin::icon('download'),
            'text' => 'Xuất file',
            'id' => 'js_btn_export_list'
        ]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    static function orderSearchHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

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
add_filter('table_orders_returns_header_buttons', 'OrderReturnAdminButton::tableHeaderButton');
add_action('order_detail_header_action', 'OrderReturnAdminButton::orderDetail', 10, 1);
add_filter('table_orders_returns_search_header_buttons', 'OrderReturnAdminButton::orderSearchHeaderButton');