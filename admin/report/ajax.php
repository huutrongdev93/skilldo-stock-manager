<?php

use SkillDo\DB;
use SkillDo\Validate\Rule;
use Stock\ReportTrait;

class StockReportSaleAdminAjax
{
    use ReportTrait;

    static function sales(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'report' => Rule::make('Loại báo cáo')->notEmpty()->in(['time', 'product', 'branch', 'customer']),
            'time'   => Rule::make('Thời gian')->notEmpty(),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $time = static::searchTimeRange($request->input('time'));

        $reportType = $request->input('report');

        if($reportType === 'time')
        {
            $validate = $request->validate([
                'group'  => Rule::make('Loại hiển thị')->notEmpty()->in(['day', 'month', 'year']),
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            [$reports, $totals] = static::salesTime([
                'group' => $request->input('group'),
                ...$time
            ]);

            response()->success(trans('Load dữ liệu thành công'), [
                'items' => $reports,
                'totals' => $totals,
            ]);
        }

        if($reportType === 'product')
        {
            [$reports, $totals] = static::salesProduct($time);

            response()->success(trans('Load dữ liệu thành công'), [
                'items' => $reports,
                'totals' => $totals,
            ]);
        }

        if($reportType === 'branch')
        {
            [$reports, $totals] = static::salesBranch($time);

            response()->success(trans('Load dữ liệu thành công'), [
                'items' => $reports,
                'totals' => $totals,
            ]);
        }

        if($reportType === 'customer')
        {
            [$reports, $totals] = static::salesCustomer($time);

            response()->success(trans('Load dữ liệu thành công'), [
                'items' => $reports,
                'totals' => $totals,
            ]);
        }

        response()->error(trans('Load dữ liệu thất bại'));
    }

    static function salesTime($args): array
    {
        $cancel = \Ecommerce\Enum\Order\Status::CANCELLED->value;

        $select = [
            DB::raw('COUNT(id) as numOrder'),
            DB::raw('SUM(subtotal) as price'),
            DB::raw('SUM(discount) as discount'),
            DB::raw('SUM(CASE WHEN status = "'.$cancel.'" THEN subtotal ELSE total_return_payment END) as priceReturn'),
            DB::raw('SUM(CASE WHEN status != "'.$cancel.'" THEN subtotal ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN discount ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_payment ELSE 0 END) as revenue'),
            DB::raw('SUM(shipping) as shipping'),
            DB::raw('SUM(total) - SUM(CASE WHEN status = "'.$cancel.'" THEN subtotal ELSE total_return_payment END) as revenueTotal'),
            DB::raw('SUM(CASE WHEN status != "'.$cancel.'" THEN subtotal ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN discount ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN cost ELSE 0 END)  - SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_payment ELSE 0 END)  + SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_cost ELSE 0 END) as grossProfit')
        ];

        if($args['group'] == 'day')
        {
            $selectList = [
                DB::raw('DATE(created) as time'),
                ...$select
            ];
        }

        if($args['group'] == 'month')
        {
            $selectList = [
                DB::raw('MONTH(created) as month'),
                DB::raw('YEAR(created) as year'),
                ...$select
            ];
        }

        if($args['group'] == 'year')
        {
            $selectList = [
                DB::raw('YEAR(created) as time'),
                ...$select
            ];
        }

        $reports = DB::table('order')
            ->select($selectList)
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']]);

        if($args['group'] == 'day')
        {
            $reports->groupBy(DB::raw('DATE(created)'))
                ->orderBy('time', 'desc');
        }
        if($args['group'] == 'month')
        {
            $reports->groupBy(DB::raw('YEAR(created), MONTH(created)'))
                ->orderBy(DB::raw('YEAR(created)'), 'desc')
                ->orderBy(DB::raw('MONTH(created)'), 'desc');
        }
        if($args['group'] == 'year')
        {
            $reports->groupBy(DB::raw('YEAR(created)'))
                ->orderBy('time', 'desc');
        }

        $reports = $reports->get();

        $totals = DB::table('order')
            ->select($select)
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->first();

        foreach ($reports as $report)
        {
            if($args['group'] == 'day')
            {
                $report->time = date('d/m/Y', strtotime($report->time));
            }
            if($args['group'] == 'month')
            {
                $report->time = $report->month.' - '.$report->year;
            }

            foreach ($report as $key => $value)
            {
                if($key == 'time')
                {
                    continue;
                }

                if($key == 'discount' || $key == 'priceReturn')
                {
                    $value = $value*-1;
                }

                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        $totals->time = date(match ($args['group']) {
                'day'   => 'd/m/Y',
                'month' => 'm/Y',
                'year'  => 'Y',
            }, strtotime($args['dateStart'])).' - '. date(match ($args['group']) {
                'day'   => 'd/m/Y',
                'month' => 'm/Y',
                'year'  => 'Y',
            }, strtotime($args['dateEnd']));

        foreach ($totals as $key => $value)
        {
            if($key == 'time')
            {
                continue;
            }

            if($key == 'discount' || $key == 'priceReturn')
            {
                $value = $value*-1;
            }

            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return [$reports, $totals];
    }

    static function salesProduct($args): array
    {
        $cancel = \Ecommerce\Enum\Order\Status::CANCELLED->value;

        $select = [
            DB::raw('SUM(cle_od.quantity - cle_od.return_quantity) as quantity'), //SL bán
            DB::raw('SUM(cle_od.return_quantity) as return_quantity'), //SL trả lại
            DB::raw('SUM((cle_od.subtotal)) as subtotal'), //Tiên hàng
            DB::raw('SUM(cle_od.discount * cle_od.quantity) as discount'), //giảm giá
            DB::raw('SUM(cle_od.return_price + cle_od.return_quantity * cle_od.discount) as priceReturn'), //Tiền trả hàng
            DB::raw('SUM((cle_od.subtotal) - cle_od.discount - (cle_od.return_price + cle_od.return_quantity * cle_od.discount)) as revenue'), //Doanh thu thuần
            DB::raw('SUM((cle_od.quantity - cle_od.return_quantity)* cle_od.cost) as costTotal'),
            DB::raw('SUM((cle_od.subtotal) - cle_od.discount - (cle_od.quantity * cle_od.cost) - (cle_od.return_price + cle_od.return_quantity * cle_od.discount) +  (cle_od.return_quantity * cle_od.cost)) as grossProfit')
        ];

        $reports = DB::table('order_detail as od')
            ->join('order as o', 'o.id', '=', 'od.order_id')
            ->where('o.status', '!=', $cancel)
            ->select([
                'od.code',
                DB::raw('MAX(cle_od.title) as name'), //SL bán
                ...$select
            ])
            ->groupBy('od.code')
            ->whereBetween('od.created', [$args['dateStart'], $args['dateEnd']])
            ->orderBy('quantity', 'desc')
            ->get();

        $totals = DB::table('order_detail as od')
            ->join('order as o', 'o.id', '=', 'od.order_id')
            ->where('o.status', '!=', $cancel)
            ->select($select)
            ->whereBetween('od.created', [$args['dateStart'], $args['dateEnd']])
            ->first();

        foreach ($reports as $report)
        {
            foreach ($report as $key => $value)
            {
                if($key == 'discount' || $key == 'priceReturn')
                {
                    $value = $value*-1;
                }

                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        foreach ($totals as $key => $value)
        {
            if($key == 'name')
            {
                $totals->{$key} = 'Tổng';
                continue;
            }

            if($key == 'discount' || $key == 'priceReturn')
            {
                $value = $value*-1;
            }

            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return [$reports, $totals];
    }

    static function salesBranch($args): array
    {
        $cancel = \Ecommerce\Enum\Order\Status::CANCELLED->value;

        $select = [
            DB::raw('COUNT(id) as quantity'),
            DB::raw('SUM(subtotal) as subtotal'),
            DB::raw('SUM(discount) as discount'),
            DB::raw('SUM(CASE WHEN status = "'.$cancel.'" THEN subtotal ELSE total_return_payment END) as priceReturn'),
            DB::raw('SUM(CASE WHEN status != "'.$cancel.'" THEN subtotal ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN discount ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_payment ELSE 0 END) as revenue'),
            DB::raw('SUM(shipping) as shipping'),
            DB::raw('SUM(total) - SUM(CASE WHEN status = "'.$cancel.'" THEN subtotal ELSE total_return_payment END) as revenueTotal'),
            DB::raw('SUM(CASE WHEN status != "'.$cancel.'" THEN subtotal ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN discount ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN cost ELSE 0 END)  - SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_payment ELSE 0 END)  + SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_cost ELSE 0 END) as grossProfit')
        ];

        $reports = DB::table('order')
            ->select([
                'branch_id',
                ...$select
            ])
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->groupBy('branch_id')
            ->orderBy('subtotal', 'desc')
            ->get();

        $totals = DB::table('order')
            ->select($select)
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->first();

        $branches = Branch::widthStop()->get()->keyBy('id');

        foreach ($reports as $report)
        {
            if(!$branches->has($report->branch_id))
            {
                $report->name = '---';
            }
            else
            {
                $report->name = $branches[$report->branch_id]->name;
            }

            foreach ($report as $key => $value)
            {
                if($key == 'discount' || $key == 'priceReturn')
                {
                    $value = $value*-1;
                }

                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        $totals->name = 'Tổng';

        foreach ($totals as $key => $value)
        {
            if($key == 'name')
            {
                continue;
            }

            if($key == 'discount' || $key == 'priceReturn')
            {
                $value = $value*-1;
            }

            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return [$reports, $totals];
    }

    static function salesCustomer($args): array
    {
        $cancel = \Ecommerce\Enum\Order\Status::CANCELLED->value;

        $select = [
            DB::raw('COUNT(id) as quantity'),
            DB::raw('SUM(subtotal) as subtotal'),
            DB::raw('SUM(discount) as discount'),
            DB::raw('SUM(CASE WHEN status = "'.$cancel.'" THEN subtotal ELSE total_return_payment END) as priceReturn'),
            DB::raw('SUM(CASE WHEN status != "'.$cancel.'" THEN subtotal ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN discount ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_payment ELSE 0 END) as revenue'),
            DB::raw('SUM(shipping) as shipping'),
            DB::raw('SUM(total) - SUM(CASE WHEN status = "'.$cancel.'" THEN subtotal ELSE total_return_payment END) as revenueTotal'),
            DB::raw('SUM(CASE WHEN status != "'.$cancel.'" THEN subtotal ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN discount ELSE 0 END) - SUM(CASE WHEN status != "'.$cancel.'" THEN cost ELSE 0 END)  - SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_payment ELSE 0 END)  + SUM(CASE WHEN status != "'.$cancel.'" THEN total_return_cost ELSE 0 END) as grossProfit')
        ];

        $reports = DB::table('order')
            ->select([
                'customer_id',
                ...$select
            ])
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->groupBy('customer_id')
            ->orderBy('subtotal', 'desc')
            ->get();

        $totals = DB::table('order')
            ->select($select)
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->first();

        $customers = User::whereKey($reports->pluck('customer_id')->toArray())->get()->keyBy('id');

        foreach ($reports as $report)
        {
            if(!$customers->has($report->customer_id))
            {
                $report->name = '---';
                $report->email = '---';
                $report->phone = '---';
            }
            else
            {
                $report->name = $customers[$report->customer_id]->firstname.' '.$customers[$report->customer_id]->lastname;
                $report->email = $customers[$report->customer_id]->email;
                $report->phone = $customers[$report->customer_id]->phone;
            }

            foreach ($report as $key => $value)
            {
                if($key == 'discount' || $key == 'priceReturn')
                {
                    $value = $value*-1;
                }

                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        $totals->name = 'Tổng';
        $totals->email = '';
        $totals->phone = '';

        foreach ($totals as $key => $value)
        {
            if($key == 'name' || $key == 'email' || $key == 'phone')
            {
                continue;
            }

            if($key == 'discount' || $key == 'priceReturn')
            {
                $value = $value*-1;
            }

            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return [$reports, $totals];
    }

    static function export(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'report' => Rule::make('Loại báo cáo')->notEmpty()->in(['time', 'product', 'branch', 'customer']),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $reportType = $request->input('report');

        $time  = static::searchTimeRange($request->input('search.time'));

        if($reportType === 'time')
        {
            static::exportTime($request, $time);
        }

        if($reportType === 'product')
        {
            static::exportProduct($request, $time);
        }

        if($reportType === 'branch')
        {
            static::exportBranch($request, $time);
        }

        if($reportType === 'customer')
        {
            static::exportCustomer($request, $time);
        }

        response()->error(trans('ajax.load.error'));
    }

    static function exportTime(\SkillDo\Http\Request $request, $args): void
    {
        $validate = $request->validate([
            'search.group' => Rule::make('Nhóm')->notEmpty()->in(['day', 'month', 'year']),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $group  = Str::clear($request->input('search.group'));

        [$reports, $totals] = static::salesTime([
            'group' => $group,
            ...$args
        ]);

        $columns = \Stock\ReportColumns::salesTime();

        $path = static::exportWithColumns($columns, [
            'columnsData'   => [$totals, ...$reports],
            'sheetName'     => 'BaoCaoDoanhThu',
            'filename'      => 'BaoCaoDoanhThuTheoThoiGian_'.date('d-m-Y', $args['timeStart']).'_'.date('d-m-Y', $args['timeEnd']),
        ]);

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportProduct(\SkillDo\Http\Request $request, $args): void
    {
        [$reports, $totals] = static::salesProduct($args);

        $columns = \Stock\ReportColumns::salesProduct();

        $path = static::exportWithColumns($columns, [
            'columnsData'   => [$totals, ...$reports],
            'sheetName'     => 'BaoCaoDoanhThu',
            'filename'      => 'BaoCaoDoanhThuTheoSanPham_'.date('d-m-Y', $args['timeStart']).'_'.date('d-m-Y', $args['timeEnd']),
        ]);

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportBranch(\SkillDo\Http\Request $request, $args): void
    {
        [$reports, $totals] = static::salesBranch($args);

        $columns = \Stock\ReportColumns::salesBranch();

        $path = static::exportWithColumns($columns, [
            'columnsData'   => [$totals, ...$reports],
            'sheetName'     => 'BaoCaoDoanhThu',
            'filename'      => 'BaoCaoDoanhThuTheoChiNhanh_'.date('d-m-Y', $args['timeStart']).'_'.date('d-m-Y', $args['timeEnd']),
        ]);

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportCustomer(\SkillDo\Http\Request $request, $args): void
    {
        [$reports, $totals] = static::salesCustomer($args);

        $columns = \Stock\ReportColumns::salesCustomer();

        $path = static::exportWithColumns($columns, [
            'columnsData'   => [$totals, ...$reports],
            'sheetName'     => 'BaoCaoDoanhThu',
            'filename'      => 'BaoCaoDoanhThuTheoKhachHang_'.date('d-m-Y', $args['timeStart']).'_'.date('d-m-Y', $args['timeEnd']),
        ]);

        response()->success(trans('ajax.load.success'), $path);
    }
}

Ajax::admin('StockReportSaleAdminAjax::sales');
Ajax::admin('StockReportSaleAdminAjax::export');

class StockReportFinancialAdminAjax
{
    use ReportTrait;

    static function financial(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'time'   => Rule::make('Thời gian')->notEmpty(),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $time = static::searchTimeRange($request->input('time'));

        $total = static::getData($time);

        response()->success(trans('Load dữ liệu thành công'), [
            'total' => $total,
        ]);
    }

    static function getData($args)
    {
        $branch = \Stock\Helper::getBranchCurrent();

        $cancel = \Ecommerce\Enum\Order\Status::CANCELLED->value;

        $select = [
            DB::raw('SUM(subtotal) as subtotal'),
            DB::raw('SUM(discount) as discount'),
            DB::raw('SUM(total_return_payment) as priceReturn'),
            DB::raw('SUM(shipping) as shipping'),
            DB::raw('SUM(cost -  total_return_cost) as cost')
        ];

        $totals = DB::table('order')
            ->select($select)
            ->where('status', '<>', $cancel)
            ->where('branch_id', $branch->id)
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->first();

        $totals->deductionRevenue = $totals->discount + $totals->priceReturn;

        $totals->revenue = $totals->subtotal - $totals->deductionRevenue;

        //Chi phí hủy hàng
        $damageItem = \Stock\Model\DamageItem::select([
            DB::raw('SUM(subtotal) as subtotal'),
        ])
        ->where('branch_id', $branch->id)
        ->whereBetween('damage_date', [$args['timeStart'], $args['timeEnd']])
        ->first();

        $totals->damageItem = $damageItem->subtotal ?? 0;

        //Chi phí nhập hàng
        $purchaseOrder = \Stock\Model\PurchaseOrder::select([
            DB::raw('SUM(subtotal) - SUM(discount) as subtotal'),
        ])
            ->where('branch_id', $branch->id)
            ->whereBetween('purchase_date', [$args['timeStart'], $args['timeEnd']])
            ->first();

        $totals->purchaseOrder = $purchaseOrder->subtotal ?? 0;

        $totals->expenses = $totals->damageItem + $totals->purchaseOrder;

        //Thu nhập trả hàng
        $purchaseReturn = \Stock\Model\PurchaseReturn::select([
            DB::raw('SUM(subtotal) - SUM(return_discount) as subtotal'),
        ])
            ->where('branch_id', $branch->id)
            ->whereBetween('purchase_date', [$args['timeStart'], $args['timeEnd']])
            ->first();

        $totals->purchaseReturn = $purchaseReturn->subtotal ?? 0;

        //thu nhập trả hàng
        $orderReturn = \Stock\Model\OrderReturn::select([
            DB::raw('SUM(surcharge) as surcharge'),
        ])
            ->where('branch_id', $branch->id)
            ->whereBetween('created', [$args['dateStart'], $args['dateEnd']])
            ->first();

        $totals->surchargeReturn = $orderReturn->surcharge ?? 0;

        $totals->income = $totals->surchargeReturn + $totals->purchaseReturn;

        $totals->profit = $orderReturn->revenue - $orderReturn->cost - $totals->expenses + $totals->income;

        foreach ($totals as $key => $value)
        {
            if($key == 'deductionRevenue' || $key == 'cost' || $key == 'expenses')
            {
                $value = $value*-1;
            }

            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return $totals;
    }
}

Ajax::admin('StockReportFinancialAdminAjax::financial');


class StockReportInventoryAdminAjax
{
    use ReportTrait;

    static function supplier(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'time'   => Rule::make('Thời gian')->notEmpty(),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $time = static::searchTimeRange($request->input('time'));

        [$reports, $totals] = static::supplierData($time);

        response()->success(trans('Load dữ liệu thành công'), [
            'items' => $reports,
            'totals' => $totals,
        ]);
    }

    static function supplierData($args): array
    {
        $branch = \Stock\Helper::getBranchCurrent();

        $select = [
            DB::raw('SUM(cle_po.total_quantity) as quantity'),
            DB::raw('SUM(cle_po.subtotal) as subtotal'),
            DB::raw('COALESCE(SUM(cle_pr.total_quantity), 0) as returnQuantity'),
            DB::raw('COALESCE(SUM(cle_pr.subtotal), 0) as returnSubtotal'),
            DB::raw('SUM(cle_po.subtotal) - COALESCE(SUM(cle_pr.subtotal), 0) as netValue')
        ];

        $reports = DB::table('inventories_purchase_orders as po')
            ->leftJoin('inventories_purchase_returns as pr', function ($join) use ($branch) {
                $join->on('po.supplier_id', '=', 'pr.supplier_id')
                    ->where('pr.status', '=', \Stock\Status\PurchaseReturn::success->value)
                    ->where('pr.branch_id', $branch->id);
            })
            ->where('po.status', '=', \Stock\Status\PurchaseOrder::success->value)
            ->where('po.branch_id', $branch->id)
            ->select([
                'po.supplier_id as id',
                ...$select
            ])
            ->whereBetween('po.purchase_date', [$args['timeStart'], $args['timeEnd']])
            ->groupBy('po.supplier_id')
            ->get();

        $totals = DB::table('inventories_purchase_orders as po')
            ->leftJoin('inventories_purchase_returns as pr', function ($join) use ($branch) {
                $join->on('po.supplier_id', '=', 'pr.supplier_id')
                    ->where('pr.status', '=', \Stock\Status\PurchaseReturn::success->value)
                    ->where('pr.branch_id', $branch->id);
            })
            ->where('po.status', '=', \Stock\Status\PurchaseOrder::success->value)
            ->where('po.branch_id', $branch->id)
            ->select($select)
            ->whereBetween('po.purchase_date', [$args['timeStart'], $args['timeEnd']])
            ->first();

        $suppliers = \Stock\Model\Suppliers::widthStop()->get()->keyBy('id');

        foreach ($reports as $report)
        {
            if(!$suppliers->has($report->id))
            {
                $report->code = '---';
                $report->name = '---';
            }
            else
            {
                $report->code = $suppliers[$report->id]->code;
                $report->name = $suppliers[$report->id]->name;
            }

            foreach ($report as $key => $value)
            {
                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        $totals->code = 'Tổng';

        $totals->name = '';

        foreach ($totals as $key => $value)
        {
            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return [$reports, $totals];
    }

    static function supplierDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id'   => Rule::make('id nhà cung cấp')->notEmpty()->integer()->min(0),
            'time' => Rule::make('Thời gian')->notEmpty(),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $time = static::searchTimeRange($request->input('time'));

        $reports = static::supplierDataDetail($id, $time);

        response()->success(trans('Load dữ liệu thành công'), [
            'items' => $reports,
        ]);
    }

    static function supplierDataDetail($id, $args)
    {
        $purchases = DB::table('inventories_purchase_orders')
            ->select(
                'id',
                'code',
                'purchase_date as date',
                'total_quantity as quantity',
                'subtotal',
                DB::raw('"Nhập hàng" as type') // Thêm cột type để phân biệt
            )
            ->where('supplier_id', $id)
            ->where('status', \Stock\Status\PurchaseOrder::success->value)
            ->whereBetween('purchase_date', [$args['timeStart'], $args['timeEnd']]);

        // Lấy danh sách phiếu trả hàng
        $returns = DB::table('inventories_purchase_returns')
            ->select(
                'id',
                'code',
                'purchase_date as date',
                'total_quantity as quantity',
                'subtotal',
                DB::raw('"Trả hàng" as type')
            )
            ->where('supplier_id', $id)
            ->where('status', \Stock\Status\PurchaseReturn::success->value)
            ->whereBetween('purchase_date', [$args['timeStart'], $args['timeEnd']]);

        // Kết hợp 2 query sử dụng union và sắp xếp theo ngày
        $documents = $purchases->union($returns)
            ->orderBy('date', 'desc')
            ->get();

        foreach ($documents as $report)
        {
            foreach ($report as $key => $value)
            {
                if($key == 'date')
                {
                    $value = date('d/m/Y H:i', $value);
                    $report->{$key} = $value;
                    continue;
                }

                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        return $documents;
    }

    static function product(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'time'   => Rule::make('Thời gian')->notEmpty(),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $time = static::searchTimeRange($request->input('time'));

        [$reports, $totals] = static::productData($time);

        response()->success(trans('Load dữ liệu thành công'), [
            'items' => $reports,
            'totals' => $totals,
        ]);
    }

    static function productData($args): array
    {
        $branch = \Stock\Helper::getBranchCurrent();

        $select = [
            DB::raw('SUM(cle_pod.quantity) as quantity'),
            DB::raw('SUM(cle_pod.quantity*cle_pod.price) as subtotal'),
            DB::raw('COALESCE(SUM(cle_prd.quantity), 0) as returnQuantity'),
            DB::raw('COALESCE(SUM(cle_prd.quantity*cle_prd.price), 0) as returnSubtotal'),
            DB::raw('SUM(cle_pod.quantity*cle_pod.price) - COALESCE(SUM(cle_prd.quantity*cle_prd.price), 0) as netValue')
        ];

        $reports = DB::table('inventories_purchase_orders as po')
            ->where('po.status', '=', \Stock\Status\PurchaseOrder::success->value)
            ->where('po.branch_id', $branch->id)
            ->whereBetween('po.purchase_date', [$args['timeStart'], $args['timeEnd']])
            ->join('inventories_purchase_orders_details as pod', 'po.id', '=', 'pod.purchase_order_id')
            ->leftJoin('inventories_purchase_returns as pr', function ($join) use ($branch, $args) {
                $join->on('po.supplier_id', '=', 'pr.supplier_id')
                    ->where('pr.status', '=', \Stock\Status\PurchaseReturn::success->value)
                    ->where('pr.branch_id', $branch->id)
                    ->whereBetween('pr.purchase_date', [$args['timeStart'], $args['timeEnd']]);
            })
            ->leftJoin('inventories_purchase_returns_details as prd', 'pr.id', '=', 'prd.purchase_return_id')
            ->select([
                'pod.product_id as id',
                'pod.product_code as code',
                DB::raw('Max(CONCAT(cle_pod.product_name, " ", cle_pod.product_attribute)) as name'),
                ...$select
            ])
            ->groupBy('pod.product_id', 'pod.product_code')
            ->get();

        $totals = DB::table('inventories_purchase_orders as po')
            ->where('po.status', '=', \Stock\Status\PurchaseOrder::success->value)
            ->where('po.branch_id', $branch->id)
            ->whereBetween('po.purchase_date', [$args['timeStart'], $args['timeEnd']])
            ->join('inventories_purchase_orders_details as pod', 'po.id', '=', 'pod.purchase_order_id')
            ->leftJoin('inventories_purchase_returns as pr', function ($join) use ($branch, $args) {
                $join->on('po.supplier_id', '=', 'pr.supplier_id')
                    ->where('pr.status', '=', \Stock\Status\PurchaseReturn::success->value)
                    ->where('pr.branch_id', $branch->id)
                    ->whereBetween('pr.purchase_date', [$args['timeStart'], $args['timeEnd']]);
            })
            ->leftJoin('inventories_purchase_returns_details as prd', 'pr.id', '=', 'prd.purchase_return_id')
            ->select($select)
            ->first();

        foreach ($reports as $report)
        {
            foreach ($report as $key => $value)
            {
                if(is_numeric($value))
                {
                    $report->{$key} = number_format($value);
                }
            }
        }

        $totals->code = 'Tổng';

        $totals->name = '';

        foreach ($totals as $key => $value)
        {
            if(is_numeric($value))
            {
                $totals->{$key} = number_format($value);
            }
        }

        return [$reports, $totals];
    }

    static function export(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'report' => Rule::make('Loại báo cáo')->notEmpty()->in(['supplier', 'product', 'branch', 'customer']),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $by = $request->input('report');

        $time  = static::searchTimeRange($request->input('search.time'));

        if($by === 'supplier')
        {
            static::exportSupplier($request, $time);
        }

        if($by === 'product')
        {
            static::exportProduct($request, $time);
        }

        response()->error(trans('ajax.load.error'));
    }

    static function exportSupplier(\SkillDo\Http\Request $request, $args): void
    {
        [$reports, $totals] = static::supplierData($args);

        $columns = \Stock\ReportColumns::inventorySupplier();

        $export = new \Stock\Export();

        $sheet = $export->addSheet('BaoCaoNhapHang', 'BaoCaoNhapHang');

        foreach ($columns as $key => $label)
        {
            $sheet->setHeader($key, $label['label'], function($item) use ($key) {
                return $item->$key ?? '';
            });
        }

        $sheet->setData([$totals, ...$reports]);

        $columns = \Stock\ReportColumns::inventorySupplierChild();

        foreach ($reports as $report)
        {
            $bills = static::supplierDataDetail($report->id, $args);

            $sheet = $export->addSheet($report->code, $report->code);

            foreach ($columns as $key => $label)
            {
                $sheet->setHeader($key, $label['label'], function($item) use ($key) {
                    return $item->$key ?? '';
                });
            }

            $sheet->setData($bills->toArray());
        }

        $path = $export->export('assets/export/report/', 'BaoCaoNhapHangTheoNCC_'.date('d-m-Y', $args['timeStart']).'_'.date('d-m-Y', $args['timeEnd']).'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportProduct(\SkillDo\Http\Request $request, $args): void
    {
        [$reports, $totals] = static::productData($args);

        $columns = \Stock\ReportColumns::inventoryProduct();

        $path = static::exportWithColumns($columns, [
            'columnsData'   => [$totals, ...$reports],
            'sheetName'     => 'BaoCaoNhapHang',
            'filename'      => 'BaoCaoNhapHangTheoSanPham_'.date('d-m-Y', $args['timeStart']).'_'.date('d-m-Y', $args['timeEnd']),
        ]);

        response()->success(trans('ajax.load.success'), $path);
    }
}

Ajax::admin('StockReportInventoryAdminAjax::supplier');
Ajax::admin('StockReportInventoryAdminAjax::supplierDetail');
Ajax::admin('StockReportInventoryAdminAjax::product');
Ajax::admin('StockReportInventoryAdminAjax::export');