<?php
class AdminPurchaseReturnButton {
    /**
     * Thêm buttons action cho header của table
     * @param $buttons
     * @return array
     * @throws Exception
     */
    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.stock.purchaseReturns.new')
        ]);
        $buttons[] = Admin::button('blue', [
            'icon' => Admin::icon('download'),
            'text' => 'Xuất file',
            'id' => 'js_btn_export_list'
        ]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    /**
     * Thêm buttons cho hành dộng hàng loạt
     * @param array $actionList
     * @return array
     */
    static function bulkAction(array $actionList): array
    {
        return $actionList;
    }
}
add_filter('table_inventories_purchase_returns_header_buttons', 'AdminPurchaseReturnButton::tableHeaderButton');
add_filter('table_inventories_purchase_returns_bulk_action_buttons', 'AdminPurchaseReturnButton::bulkAction', 30);