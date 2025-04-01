<?php
class StockOrderAdminAjax
{
    static function detailHistoryPayment(SkillDo\Http\Request $request): void
    {
        $id = (int)$request->input('id');

        $branch = \Stock\Helper::getBranchCurrent();

        $histories = \Stock\Model\CashFlow::where('target_id', $id)
            ->where('target_type', 'Order')
            ->where('branch_id', $branch->id)
            ->orderByDesc('created')
            ->get();

        if(have_posts($histories))
        {
            foreach($histories as $key => $history)
            {
                $history = $history->toObject();

                if(!empty($history->target_id))
                {
                    $attributes = [
                        'data-target-id' => $history->id,
                        'data-target' => 'cash-flow'
                    ];

                    $attributesStr = '';

                    foreach ($attributes as $attKey => $attValue)
                    {
                        $attributesStr .= $attKey.'="'.$attValue.'" ';
                    }

                    $history->code = '<a href="#" class="js_btn_target" '.$attributesStr.'>'.$history->code.'</a>';
                }

                $history->created = date('d/m/Y H:i', strtotime($history->created));

                $history->amount = Prd::price($history->amount);

                $history->status = Admin::badge(\Stock\Status\CashFlow::tryFrom($history->status)->badge(), \Stock\Status\CashFlow::tryFrom($history->status)->label());

                $histories[$key] = $history;
            }
        }

        response()->success(trans('ajax.load.success'), [
            'items' => $histories,
        ]);
    }
    static function detailHistoryReturn(SkillDo\Http\Request $request): void
    {
        $id = (int)$request->input('id');

        $branch = \Stock\Helper::getBranchCurrent();

        $histories = \Stock\Model\OrderReturn::where('order_id', $id)
            ->where('branch_id', $branch->id)
            ->orderByDesc('created')
            ->get();

        if(have_posts($histories))
        {
            foreach($histories as $key => $history)
            {
                $history = $history->toObject();

                $attributes = [
                    'data-target-id' => $history->id,
                    'data-target' => 'order-return'
                ];

                $attributesStr = '';

                foreach ($attributes as $attKey => $attValue)
                {
                    $attributesStr .= $attKey.'="'.$attValue.'" ';
                }

                $history->code = '<a href="#" class="js_btn_target" '.$attributesStr.'>'.$history->code.'</a>';

                $history->created = date('d/m/Y H:i', strtotime($history->created));

                $history->total_payment = Prd::price($history->total_payment);

                $history->status = Admin::badge(\Stock\Status\OrderReturn::tryFrom($history->status)->badge(), \Stock\Status\OrderReturn::tryFrom($history->status)->label());

                $histories[$key] = $history;
            }
        }

        response()->success(trans('ajax.load.success'), [
            'items' => $histories,
        ]);
    }
}

Ajax::admin('StockOrderAdminAjax::detailHistoryPayment');
Ajax::admin('StockOrderAdminAjax::detailHistoryReturn');