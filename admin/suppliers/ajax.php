<?php

use SkillDo\DB;
use SkillDo\Validate\Rule;

class SuppliersAdminAjax
{
    static function loadDebtPayment(\SkillDo\Http\Request $request): void
    {
        $id = $request->input('id');

        $object = \Stock\Model\Suppliers::find($id);

        if(empty($object))
        {
            response()->error('Nhà cung cấp không có trên hệ thống');
        }

        $purchaseOrders = \Stock\Model\PurchaseOrder::where('supplier_id', $id)
            ->where('status', \Stock\Status\PurchaseOrder::success->value)
            ->where('is_payment', 0)
            ->orderBy('created')
            ->get();

        response()->success(trans('Load dữ liệu thành công'), [
            'item' => $object,
            'purchaseOrders' => $purchaseOrders
        ]);
    }

    static function addCashFlow(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('id nhà cung cấp')->notEmpty()->integer()->min(0),
            'payment' => Rule::make('Tiền trả cho NCC')->notEmpty()->integer(),
            'purchaseOrders.*.payment' => Rule::make('Tiền trả cho phiếu nhập hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $supplier = \Stock\Model\Suppliers::find($id);

        if(empty($supplier))
        {
            response()->error('Nhà cung cấp không có trên hệ thống');
        }

        $branch = Branch::get();

        $payment = (int)$request->input('payment');

        $paymentPurchaseOrders = 0;

        $paymentPurchaseOrdersId = [];

        $purchaseOrdersPayload = $request->input('purchaseOrders');

        if(have_posts($purchaseOrdersPayload))
        {
            $purchaseOrdersPayload   = (new \Illuminate\Support\Collection($purchaseOrdersPayload))->keyBy('id');

            $paymentPurchaseOrdersId = $purchaseOrdersPayload->pluck('id')->toArray();

            $paymentPurchaseOrders  = $purchaseOrdersPayload->sum('payment');
        }

        if($paymentPurchaseOrders == 0 && $payment == 0)
        {
            response()->error('Giá trị phiếu chi chưa được điền');
        }

        if($payment > 0 && $paymentPurchaseOrders > 0 && $paymentPurchaseOrders > $payment)
        {
            response()->error('Giá trị phiếu chi không được nhỏ hơn giá trị thanh toán cho phiếu nhập');
        }

        if($payment == 0 && $paymentPurchaseOrders > 0)
        {
            $payment = $paymentPurchaseOrders;
        }

        //Khởi tạo phiếu chi
        $idCashFlow = \Stock\Model\CashFlow::create([
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            //Người thu
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->firstname.' '.Auth::user()->lastname,
            //người nhận
            'partner_id'    => $supplier->id,
            'partner_code'  => $supplier->code,
            'partner_name'  => $supplier->name,
            'address'       => $supplier->address,
            'phone'         => $supplier->phone,
            'partner_type'  => 'S',
            //Loại
            'group_id'   => \Stock\CashFlowGroup\Transaction::supplierPayment->id(),
            'group_name' => \Stock\CashFlowGroup\Transaction::supplierPayment->label(),
            'origin' => 'purchase',
            'method' => 'cash',
            'amount' => $payment*-1,
            'target_type' => \Stock\Prefix::purchaseOrder->value,
            'time'          => time(),
            'status'        => \Stock\Status\CashFlow::success->value,
            'user_created'  => Auth::id()
        ]);

        if(empty($idCashFlow) || is_skd_error($idCashFlow))
        {
            response()->error($idCashFlow);
        }

        $code = \Stock\Helper::code('PC', $idCashFlow);

        $balance = $supplier->debt - $payment;

        //Tạo công nợ cho phiêu chi
        \Stock\Model\Debt::create([
            'before'        => ($supplier->debt)*-1,
            'amount'        => $payment,
            'balance'       => $balance*-1,
            'partner_id'    => $supplier->id,
            'target_id'     => $idCashFlow,
            'target_code'   => $code,
            'target_type'   => 'PC',
            'time'          => time()
        ]);

        $supplier->debt = $balance;

        $supplier->save();

        if(have_posts($purchaseOrdersPayload) && have_posts($paymentPurchaseOrdersId) && $paymentPurchaseOrders > 0)
        {
            $cashFlows = [];

            $purchaseOrdersUp = [];

            $purchaseOrders = \Stock\Model\PurchaseOrder::where('supplier_id', $id)
                ->whereIn('id', $paymentPurchaseOrdersId)
                ->where('is_payment', 0)
                ->orderByDesc('created')
                ->get();

            foreach ($purchaseOrders as $purchase)
            {
                if(!$purchaseOrdersPayload->has($purchase->id))
                {
                    continue;
                }

                $purchaseOrderPayload = $purchaseOrdersPayload->get($purchase->id);

                $purchaseUp = [
                    'id' => $purchase->id,
                    'total_payment' => $purchase->total_payment + $purchaseOrderPayload['payment']
                ];

                if($purchaseUp['total_payment'] == ($purchase->sub_total - $purchase->discount))
                {
                    $purchaseUp['is_payment'] = 1;
                }

                //Tạo phiếu chi
                $cashFlows[] = [
                    'code' => $code,
                    'parent_id' => $idCashFlow,
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    //Người thu
                    'user_id'   => Auth::id(),
                    'user_name' => Auth::user()->firstname.' '.Auth::user()->lastname,
                    //người nhận
                    'partner_id'    => $supplier->id,
                    'partner_code'  => $supplier->code,
                    'partner_name'  => $supplier->name,
                    'address'       => $supplier->address,
                    'phone'         => $supplier->phone,
                    'partner_type'  => 'S',
                    //Loại
                    'group_id'   => \Stock\CashFlowGroup\Transaction::supplierPayment->id(),
                    'group_name' => \Stock\CashFlowGroup\Transaction::supplierPayment->label(),
                    'origin'     => 'purchase',
                    'method'     => 'cash',
                    'amount'     => $purchaseOrderPayload['payment']*-1,
                    //Target
                    'target_id' => $purchase->id,
                    'target_code' => $purchase->code,
                    'target_type' => \Stock\Prefix::purchaseOrder->value,
                    'order_value' => $purchase->sub_total,
                    'need_pay_value' => $purchase->sub_total - $purchase->discount,
                    'paid_value' => $purchase->total_payment,

                    'time'          => time(),
                    'status'        => \Stock\Status\CashFlow::success->value,
                    'user_created'  => Auth::id()
                ];

                $purchaseOrdersUp[] = $purchaseUp;
            }

            if(have_posts($cashFlows))
            {
                DB::table('cash_flow')->insert($cashFlows);
            }

            if(have_posts($purchaseOrdersUp))
            {
                \Stock\Model\PurchaseOrder::updateBatch($purchaseOrdersUp, 'id');
            }
        }

        response()->success(trans('Cập nhật dữ liệu thành công'));
    }

    static function status(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Id nhà sản xuất')->notEmpty()->integer()->min(1),
            'status' => Rule::make('Trạng thái')->notEmpty()->in(array_column(\Stock\Status\Supplier::cases(), 'value')),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = (int)$request->input('id');

        $object = \Stock\Model\Suppliers::widthStop()->whereKey($id)->first();

        if(!have_posts($object)) {
            response()->error('Nhà sản xuất không tồn tại');
        }

        $status = Str::clear($request->input('status'));

        if($status == $object->status) {
            response()->error('Trạng thái NCC không thay đổi');
        }

        $object->status = $status;

        $object->save();

        response()->success(trans('ajax.update.success'), \SkillDo\Table\Columns\ColumnBadge::make('status', [], [])
            ->value($object->status)
            ->color(fn (string $state): string => \Stock\Status\Supplier::tryFrom($state)->badge())
            ->label(fn (string $state): string => \Stock\Status\Supplier::tryFrom($state)->label())
            ->attributes(fn ($item): array => [
                'data-id' => $object->id,
                'data-status' => $object->status,
            ])
            ->class(['js_supplier_btn_status'])->view());
    }

    static function updateBalance(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Id nhà sản xuất')->notEmpty()->integer()->min(1),
            'balance' => Rule::make('Giá trị nợ điều chỉnh')->notEmpty()->integer()->min(0),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = (int)$request->input('id');

        $object = \Stock\Model\Suppliers::widthStop()->whereKey($id)->first();

        if(!have_posts($object))
        {
            response()->error('Nhà sản xuất không tồn tại');
        }

        $balance = (int)$request->input('balance');

        if($balance === $object->debt)
        {
            response()->error('Giá trị nợ sau khi điều chỉnh không thay đổi');
        }

        $amount = $balance - $object->debt;

        //Tạo phiếu điều chỉnh
        $id = \Stock\Model\DebtAdjustment::create([
            'balance'       => $balance,
            'partner_id'    => $object->id,
            'partner_type'  => 'supplier',
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
        \Stock\Model\Debt::create([
            'before'        => ($object->debt)*-1,
            'amount'        => $amount*-1,
            'balance'       => $balance*-1,
            'partner_id'    => $object->id,
            'target_id'     => $id,
            'target_code'   => \Stock\Helper::code(\Stock\Prefix::adjustment->value, $id),
            'target_type'   => \Stock\Prefix::adjustment->value,
            'time'          => time()
        ]);

        $object->debt = $balance;

        $object->save();

        response()->success(trans('ajax.update.success'), [
            'debt' => $balance
        ]);
    }
}

Ajax::admin('SuppliersAdminAjax::loadDebtPayment');
Ajax::admin('SuppliersAdminAjax::addCashFlow');
Ajax::admin('SuppliersAdminAjax::status');
Ajax::admin('SuppliersAdminAjax::updateBalance');