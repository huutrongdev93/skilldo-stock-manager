<?php
class TransferAdminButton {

    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.stock.transfers.new')
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
add_filter('table_transfer_header_buttons', 'TransferAdminButton::tableHeaderButton');
add_filter('table_transfer_bulk_action_buttons', 'TransferAdminButton::bulkAction', 30);