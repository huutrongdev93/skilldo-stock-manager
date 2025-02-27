<?php
class StockTakeAdminButton {

    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.stock.stockTakes.new')
        ]);
        $buttons[] = Admin::button('blue', [
            'icon' => Admin::icon('download'),
            'text' => 'Xuất file',
            'id' => 'js_btn_export_list'
        ]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    static function bulkAction(array $actionList): array
    {
        return $actionList;
    }
}
add_filter('table_stock_takes_header_buttons', 'StockTakeAdminButton::tableHeaderButton');
add_filter('table_stock_takes_bulk_action_buttons', 'StockTakeAdminButton::bulkAction', 30);