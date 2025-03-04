<?php
use SkillDo\Validate\Rule;

Class CashFlowGroupForm
{
    static function form(FormAdmin $form): FormAdmin {
        $form->leftTop
            ->addGroup('info','Thông Tin')
            ->text('name', [
                'label' => 'Tên nhóm',
                'validations'   => Rule::make()->notEmpty()
            ])
            ->textarea('note', ['label' => 'Ghi chú']);

        return $form;
    }

    static function saveReceipt($id, $insertData)
    {
        $insertData['type'] = 'receipt';
        return \Stock\Model\CashFlowGroup::insert($insertData);
    }

    static function savePayment($id, $insertData)
    {
        $insertData['type'] = 'payment';
        return \Stock\Model\CashFlowGroup::insert($insertData);
    }
}
add_filter('manage_cash_flow_group_receipt_input', 'CashFlowGroupForm::form');
add_filter('manage_cash_flow_group_payment_input', 'CashFlowGroupForm::form');
add_filter('form_submit_cash_flow_group_receipt', 'CashFlowGroupForm::saveReceipt', 10, 2);
add_filter('form_submit_cash_flow_group_payment', 'CashFlowGroupForm::savePayment', 10, 2);
