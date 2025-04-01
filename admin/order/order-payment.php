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
            $cashFlow = [
                'branch_id'     => $order->branch_id,
                'branch_name'   => $order->branch_name,
                'group_id'      => \Stock\CashFlowGroup\Transaction::orderSuccess->id(),
                'group_name'    => \Stock\CashFlowGroup\Transaction::orderSuccess->label(),
                'user_id'       => Auth::user()->id,
                'user_name'     => Auth::user()->firstname.' '.Auth::user()->lastname,
                'partner_id'    => $order->user_created,
                'partner_code'  => $order->user_created,
                'partner_name'  => $order->billing_fullname,
                'partner_phone' => $order->billing_phone,
                'partner_address' => $order->billing_address,
                'partner_type'  => 'C',
                'origin'        => 'pay',
                'method'        => 'cash',
                'amount'        => $order->total,
                'target_id'     => $order->id,
                'target_code'   => $order->code,
                'target_type'   => 'Order',
                'time'          => time(),
                'note'          => 'Phiếu thu được tạo tự động khi đơn hàng '.$order->code.' hoàn thành thanh toán',
                'status'        =>  \Stock\Status\CashFlow::success->value,
            ];

            \Stock\Model\CashFlow::create($cashFlow);
        }

        //hủy phiếu thu nếu thanh toán thất bại
        if($status !== StatusPay::COMPLETED->value && $order->status == StatusPay::COMPLETED->value)
        {
            \Stock\Model\CashFlow::where('target_type', 'Order')
                ->where('target_id', $order->id)
                ->update(['status' => \Stock\Status\CashFlow::cancel->value]);
        }
    }

    //Hủy phiếu thu khi đơn hàng hủy
    static function orderCancel($order, $status): void
    {
        \Stock\Model\CashFlow::where('target_type', 'Order')
            ->where('target_id', $order->id)
            ->update(['status' => \Stock\Status\CashFlow::cancel->value]);
    }
}

add_action('admin_order_status_pay_before_update', 'CashFlowOrder::orderPaymentValidate', 10 , 2);
add_action('admin_order_status_pay_update', 'CashFlowOrder::orderPayment', 10 , 2);
add_action('admin_order_status_update', 'CashFlowOrder::orderCancel', 10 , 2);
