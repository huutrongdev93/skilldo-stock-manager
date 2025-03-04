<?php
class CashFlowAdminButton {

    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu thu',
            'data-type' => 'receipt',
            'class' => 'js_cash_flow_btn_add'
        ]);
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu chi',
            'data-type' => 'payment',
            'class' => 'js_cash_flow_btn_add'
        ]);
        $buttons[] = Admin::button('blue', [
            'icon' => Admin::icon('download'),
            'text' => 'Xuất file',
            'id' => 'js_btn_export_list'
        ]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

}
add_filter('table_cash_flow_header_buttons', 'CashFlowAdminButton::tableHeaderButton');
