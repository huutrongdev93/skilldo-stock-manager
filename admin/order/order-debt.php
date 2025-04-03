<?php

use Ecommerce\Enum\Order\Status;
use Illuminate\Database\Capsule\Manager as DB;

Class StockOrderStatusDebt {

    public function __construct() {
        add_action('admin_order_status_update', 'StockOrderStatusDebt::statusChange', 10, 2);
    }

    static function statusChange($order, $status): void
    {
        if(have_posts($order) && !empty($order->branch_id))
        {
            if(!in_array($status, [
                Status::WAIT->value,
                Status::CANCELLED->value,
            ]))
            {
                $idUserDebt = Order::getMeta($order->id, 'user_debt_id', true);

                if(empty($idUserDebt))
                {
                    //Tạo công nợ cho đơn hàng
                    $customer = User::find($order->customer_id);

                    //Cộng công nợ khi đơn hàng được xác nhận
                    $idUserDebt = \Stock\Model\UserDebt::create([
                        'before'            => $customer->debt,
                        'amount'            => $order->total,
                        'balance'           => $customer->debt + $order->total,
                        'partner_id'        => $customer->id,
                        'target_id'         => $order->id,
                        'target_code'       => $order->code,
                        'target_type'       => 'Order',
                        'target_type_name'  => 'Mua hàng',
                        'time'              => time()
                    ]);

                    if(!empty($idUserDebt) && !is_skd_error($idUserDebt))
                    {
                        $customer->debt = $customer->debt + $order->total;

                        $customer->save();

                        Order::updateMeta($order->id, 'user_debt_id', $idUserDebt);
                    }
                }
            }
            else if($status == Status::CANCELLED->value)
            {
                //Id cộng công nợ
                $idUserDebt = Order::getMeta($order->id, 'user_debt_id', true);

                //Id phiếu thu công nợ
                $idCashFlow = Order::getMeta($order->id, 'cashFlow_id', true);

                //Nếu đơn hàng đã tính công nợ
                if(!empty($idUserDebt))
                {
                    $customer = User::find($order->customer_id);

                    //Nếu đơn hàng đã thanh toán thì tạo phiếu chi
                    if(!empty($idCashFlow))
                    {
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
                            'note'          => 'Phiếu chi được tạo tự động khi đơn hàng '.$order->code.' bị hủy',
                            'status'        =>  \Stock\Status\CashFlow::success->value,
                        ];

                        $idCashFlow = \Stock\Model\CashFlow::create($cashFlow);

                        if(!empty($idCashFlow) && !is_skd_error($idCashFlow))
                        {
                            Order::deleteMeta($order->id, 'cashFlow_id');
                        }
                    }
                    //Nếu đơn hàng chưa thanh toán thì trừ công nợ
                    else
                    {
                        \Stock\Model\UserDebt::create([
                            'before'            => $customer->debt,
                            'amount'            => $order->total*-1,
                            'balance'           => $customer->debt - $order->total,
                            'partner_id'        => $customer->id,
                            'target_id'         => $order->id,
                            'target_code'       => $order->code,
                            'target_type'       => 'Order',
                            'target_type_name'  => 'Hủy đơn hàng',
                            'time'              => time()
                        ]);

                        $customer->debt = $customer->debt - $order->total;

                        $customer->save();
                    }

                    Order::deleteMeta($order->id, 'user_debt_id');

                    Order::deleteMeta($order->id, 'cashFlow_id');
                }
            }
        }
    }
}

new StockOrderStatusDebt();

