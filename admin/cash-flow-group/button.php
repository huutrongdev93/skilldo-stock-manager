<?php
class CashFlowGroupButton {

    static function receiptTableButton($buttons): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::route('admin.cashFlow.group.receipt.new')]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    static function receiptFormButton($module): void
    {
        $buttons = [];

        $view = Url::segment(4);

        if($view == 'add') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.cashFlow.group.receipt'),
                'class' => ['btn-back-to-redirect']
            ]);
        }

        if($view == 'edit') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('add', ['href' => Url::route('admin.cashFlow.group.receipt.new'), 'text' => '', 'tooltip' => 'Thêm mới']);
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.cashFlow.group.receipt'),
                'text' => '',
                'tooltip' => 'Quay lại',
                'class' => ['btn-back-to-redirect']
            ]);
        }

        Admin::view('include/form/form-action', ['buttons' => $buttons, 'module' => $module]);
    }

    static function paymentTableButton($buttons): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::route('admin.cashFlow.group.payment.new')]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    static function paymentFormButton($module): void
    {
        $buttons = [];

        $view = Url::segment(4);

        if($view == 'add') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.cashFlow.group.payment'),
                'class' => ['btn-back-to-redirect']
            ]);
        }

        if($view == 'edit') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('add', ['href' => Url::route('admin.cashFlow.group.payment.new'), 'text' => '', 'tooltip' => 'Thêm mới']);
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.cashFlow.group.payment'),
                'text' => '',
                'tooltip' => 'Quay lại',
                'class' => ['btn-back-to-redirect']
            ]);
        }

        Admin::view('include/form/form-action', ['buttons' => $buttons, 'module' => $module]);
    }
}
add_filter('table_cash_flow_group_receipt_header_buttons', 'CashFlowGroupButton::receiptTableButton');
add_action('form_cash_flow_group_receipt_action_button', 'CashFlowGroupButton::receiptFormButton');

add_filter('table_cash_flow_group_payment_header_buttons', 'CashFlowGroupButton::paymentTableButton');
add_action('form_cash_flow_group_payment_action_button', 'CashFlowGroupButton::paymentFormButton');