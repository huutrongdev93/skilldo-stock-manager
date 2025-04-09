<?php

use SkillDo\DB;
use SkillDo\Validate\Rule;

class StockCustomerAdminAjax
{
    static function updateBalance(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Id khách hàng')->notEmpty()->integer()->min(1),
            'balance' => Rule::make('Giá trị nợ điều chỉnh')->notEmpty()->integer()->min(0),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = (int)$request->input('id');

        $object = \SkillDo\Model\User::whereKey($id)->first();

        if(!have_posts($object))
        {
            response()->error('Khách hàng không tồn tại');
        }

        $balance = (int)$request->input('balance');

        if($balance === $object->debt)
        {
            response()->error('Giá trị nợ sau khi điều chỉnh không thay đổi');
        }

        $amount = $balance - $object->debt;

        //Tạo phiếu điều chỉnh
        $id = \Skdepot\Model\DebtAdjustment::create([
            'balance'       => $balance,
            'partner_id'    => $object->id,
            'partner_type'  => 'user',
            'debt_before'   => $object->debt,
            'time'          => time(),
            'user_id'       => Auth::id(),
            'user_code'     => Auth::user()->username,
            'user_name'     => Auth::user()->firstname.' '.Auth::user()->lastname,
            'note'          => $request->input('note'),
        ]);

        if(empty($id) || is_skd_error($id))
        {
            response()->error($id);
        }

        //Khởi tạo lịch sử thay đổi công nợ
        \Skdepot\Model\UserDebt::create([
            'before'            => $object->debt,
            'amount'            => $amount,
            'balance'           => $balance,
            'partner_id'        => $object->id,
            'target_id'         => $id,
            'target_code'       => \Skdepot\Helper::code(\Skdepot\Prefix::adjustment->value, $id),
            'target_type'       => \Skdepot\Prefix::adjustment->value,
            'target_type_name'  => 'Điều chỉnh',
            'time'              => time()
        ]);

        $object->debt = $balance;

        $object->save();

        response()->success(trans('ajax.update.success'), [
            'debt' => $balance
        ]);
    }
}

Ajax::admin('StockCustomerAdminAjax::updateBalance');