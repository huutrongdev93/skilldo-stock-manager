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
            ->where('is_payment', 0)
            ->orderByDesc('created')
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
            'group_id' => -2,
            'group_name' => 'Chi tiền trả NCC',
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
            'amount'        => $payment*-1,
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
                    'group_id'   => -2,
                    'group_name' => 'Chi tiền trả NCC',
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
}

Ajax::admin('SuppliersAdminAjax::loadDebtPayment');
Ajax::admin('SuppliersAdminAjax::addCashFlow');