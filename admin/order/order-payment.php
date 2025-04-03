<?php

use Ecommerce\Enum\Order\StatusPay;

class CashFlowOrder
{
    static function orderPaymentValidate($order, $status): void
    {
        if(empty($order->branch_id))
        {
            response()->error('Đơn hàng chưa chọn kho hàng');
        }

        $branch = Branch::find($order->branch_id);

        if(!have_posts($branch))
        {
            response()->error('kho hàng của đơn hàng không tồn tại');
        }

        $order->branch_name = $branch->name;
    }

    //Tạo phiếu thu khi đơn hàng được thanh toán
    static function orderPayment($order, $status): void
    {
        //Tạo phiếu thu nếu thanh toán thành công
        if($status === StatusPay::COMPLETED->value)
        {
            $time = time();

            $customer = User::find($order->customer_id);

            //Cộng công nợ nếu chưa tạo
            $idUserDebt = Order::getMeta($order->id, 'user_debt_id', true);

            if(empty($idUserDebt))
            {
                //Cộng công nợ khi đơn hàng thanh toán
                $idUserDebt = \Stock\Model\UserDebt::create([
                    'before'            => $customer->debt,
                    'amount'            => $order->total,
                    'balance'           => $customer->debt + $order->total,
                    'partner_id'        => $customer->id,
                    'target_id'         => $order->id,
                    'target_code'       => $order->code,
                    'target_type'       => 'Order',
                    'target_type_name'  => 'Mua hàng',
                    'time'              => $time
                ]);

                if(!empty($idUserDebt) && !is_skd_error($idUserDebt))
                {
                    $customer->debt = $customer->debt + $order->total;

                    $customer->save();

                    Order::updateMeta($order->id, 'user_debt_id', $idUserDebt);
                }
            }

            $cashFlow = [
                'branch_id'     => $order->branch_id,
                'branch_name'   => $order->branch_name,
                'group_id'      => \Stock\CashFlowGroup\Transaction::orderSuccess->id(),
                'group_name'    => \Stock\CashFlowGroup\Transaction::orderSuccess->label(),
                'user_id'       => Auth::user()->id,
                'user_name'     => Auth::user()->firstname.' '.Auth::user()->lastname,
                'partner_id'    => $customer->id,
                'partner_code'  => $customer->username,
                'partner_name'  => $customer->firstname.' '.$customer->lastname,
                'partner_phone' => $order->billing_phone,
                'partner_address' => $order->billing_address,
                'partner_type'  => 'C',
                'origin'        => 'pay',
                'method'        => 'cash',
                'amount'        => $order->total,
                'target_id'     => $order->id,
                'target_code'   => $order->code,
                'target_type'   => 'Order',
                'time'          => $time,
                'note'          => 'Phiếu thu được tạo tự động khi đơn hàng '.$order->code.' hoàn thành thanh toán',
                'status'        =>  \Stock\Status\CashFlow::success->value,
            ];

            $idCashFlow = \Stock\Model\CashFlow::create($cashFlow);

            if(!empty($idCashFlow) && !is_skd_error($idCashFlow))
            {
                //Trừ công nợ khi đơn hàng được thanh toán
                \Stock\Model\UserDebt::create([
                    'before'            => $customer->debt,
                    'amount'            => $order->total*-1,
                    'balance'           => $customer->debt - $order->total,
                    'partner_id'        => $customer->id,
                    'target_id'         => $idCashFlow,
                    'target_code'       => \Stock\Helper::code(\Stock\Prefix::cashFlowOrder->value, $idCashFlow),
                    'target_type'       => \Stock\Prefix::cashFlow->value,
                    'target_type_name'  => 'Thanh toán đơn hàng',
                    'time'              => $time+10
                ]);

                $customer->debt = $customer->debt - $order->total;

                $customer->save();

                //Lưu đơn thanh toán
                Order::updateMeta($order->id, 'cashFlow_id', $idCashFlow);
            }
        }

        //Tạo phiếu chi nếu hủy thanh toán
        if($status !== StatusPay::COMPLETED->value && $order->status == StatusPay::COMPLETED->value)
        {
            //Id phiếu thu công nợ
            $idCashFlow = Order::getMeta($order->id, 'cashFlow_id', true);

            if(!empty($idCashFlow))
            {
                $customer = User::find($order->customer_id);

                $cashFlow = [
                    'branch_id'     => $order->branch_id,
                    'branch_name'   => $order->branch_name,
                    'group_id'      => \Stock\CashFlowGroup\Transaction::orderReturn->id(),
                    'group_name'    => \Stock\CashFlowGroup\Transaction::orderReturn->label(),
                    'user_id'       => Auth::user()->id,
                    'user_name'     => Auth::user()->firstname.' '.Auth::user()->lastname,
                    'partner_id'    => $customer->id,
                    'partner_code'  => $customer->username,
                    'partner_name'  => $customer->firstname.' '.$customer->lastname,
                    'partner_phone' => $order->billing_phone,
                    'partner_address' => $order->billing_address,
                    'partner_type'  => 'C',
                    'origin'        => 'pay',
                    'method'        => 'cash',
                    'amount'        => $order->total*-1,
                    'target_id'     => $order->id,
                    'target_code'   => $order->code,
                    'target_type'   => 'Order',
                    'time'          => time(),
                    'note'          => 'Phiếu chi được tạo tự động khi đơn hàng '.$order->code.' hủy thanh toán',
                    'status'        =>  \Stock\Status\CashFlow::success->value,
                ];

                $idCashFlow = \Stock\Model\CashFlow::create($cashFlow);

                if(!empty($idCashFlow) && !is_skd_error($idCashFlow))
                {
                    //Cộng công nợ khi đơn hàng hủy thanh toán
                    \Stock\Model\UserDebt::create([
                        'before'            => $customer->debt,
                        'amount'            => $order->total,
                        'balance'           => $customer->debt + $order->total,
                        'partner_id'        => $customer->id,
                        'target_id'         => $idCashFlow,
                        'target_code'       => \Stock\Helper::code(\Stock\Prefix::cashFlowOrder->value, $idCashFlow),
                        'target_type'       => \Stock\Prefix::cashFlow->value,
                        'target_type_name'  => 'Hủy thanh toán',
                        'time'              => time()
                    ]);

                    $customer->debt = $customer->debt + $order->total;

                    $customer->save();

                    Order::deleteMeta($order->id, 'cashFlow_id');
                }
            }
        }
    }
}

add_action('admin_order_status_pay_before_update', 'CashFlowOrder::orderPaymentValidate', 10 , 2);
add_action('admin_order_status_pay_update', 'CashFlowOrder::orderPayment', 10 , 2);
