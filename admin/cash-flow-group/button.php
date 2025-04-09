<?php
class CashFlowGroupButton
{
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

add_action('form_cash_flow_group_receipt_action_button', 'CashFlowGroupButton::receiptFormButton');
add_action('form_cash_flow_group_payment_action_button', 'CashFlowGroupButton::paymentFormButton');