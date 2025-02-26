<?php
class AdminDamageItemsButton {
    /**
     * Thêm buttons action cho header của table
     * @param $buttons
     * @return array
     */
    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.stock.damageItems.new')
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
add_filter('table_inventories_damage_items_header_buttons', 'AdminDamageItemsButton::tableHeaderButton');
add_filter('table_inventories_damage_items_bulk_action_buttons', 'AdminDamageItemsButton::bulkAction', 30);