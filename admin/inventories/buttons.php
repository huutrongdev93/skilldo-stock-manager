<?php
class AdminInventoriesButton {
    /**
     * Thêm buttons action cho header của table
     * @param $buttons
     * @return array
     */
    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('blue', [
            'icon' => \Stock\Helper::icon('purchaseOrder'),
            'text' => 'Nhập hàng',
            'href' => Url::route('admin.stock.purchaseOrders')
        ]);
        $buttons[] = Admin::button('blue', [
            'icon' => \Stock\Helper::icon('purchaseReturn'),
            'text' => 'Trả hàng',
            'href' => Url::route('admin.stock.purchaseReturns')
        ]);
        $buttons[] = Admin::button('blue', [
            'icon' => \Stock\Helper::icon('damageItems'),
            'text' => 'Xuất hủy',
            'href' => Url::route('admin.stock.damageItems')
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
add_filter('table_inventories_header_buttons', 'AdminInventoriesButton::tableHeaderButton');
add_filter('table_inventories_bulk_action_buttons', 'AdminInventoriesButton::bulkAction', 30);