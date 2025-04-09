<?php
class StockOrderDetailHistory
{
    static public function addTab($order): void
    {
        Plugin::view(SKDEPOT_NAME, 'admin/order/history-tab', ['order' => $order]);
    }

    static public function tabContent($order): void
    {
        Plugin::view(SKDEPOT_NAME, 'admin/order/history-tab-payment', ['order' => $order]);
        Plugin::view(SKDEPOT_NAME, 'admin/order/history-tab-return', ['order' => $order]);
    }
}

add_action('order_history_before_content', 'StockOrderDetailHistory::addTab');
add_action('order_history_after_content', 'StockOrderDetailHistory::tabContent');