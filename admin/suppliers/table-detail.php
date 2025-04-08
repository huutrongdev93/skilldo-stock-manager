<?php
namespace Stock\Table\Suppliers;

use Admin;
use Branch;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\Columns\ColumnView;
use SkillDo\Table\SKDObjectTable;

class PurchaseOrder extends SKDObjectTable
{
    public int $supplierId = 0;

    protected string $module = 'suppliers_purchase_orders';

    protected mixed $model = \Stock\Model\PurchaseOrder::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã nhập hàng'),
            'column' => fn($item, $args) => ColumnView::make('target_code', $item, $args)->html(function ($column){
                echo '<a href="#" class="js_btn_target" data-target="purchase-order" data-target-id="'.$column->item->id.'" data-target-cash-flow="0">'.$column->item->code.'</a>';
            })
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['subtotal'] = [
            'label'  => trans('Tổng giá trị'),
            'column' => fn($item, $args) => ColumnText::make('subtotal', $item, $args)->number()
        ];

        $this->_column_headers['total_payment'] = [
            'label'  => trans('Cần trả NCC'),
            'column' => fn($item, $args) => ColumnText::make('total_payment', $item, $args)->value(function ($item) {
                return $item->subtotal - $item->total_payment - $item->discount;
            })->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Stock\Status\PurchaseOrder::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Stock\Status\PurchaseOrder::tryFrom($status)->label();
                })
        ];

        return $this->_column_headers;
    }

    function headerFilter(Form $form, Request $request)
    {
        $branch = Branch::gets();

        $branchOptions = [];

        foreach ($branch as $item) {
            $branchOptions[$item->id] = $item->name;
        }

        $form->select2('branch', $branchOptions, [], request()->input('branch'));

        $form->hidden('supplierId',  [], (!empty($this->supplierId)) ? $this->supplierId : $request->input('supplierId'));

        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $supplierId = trim($request->input('supplierId'));

        $query->where('supplier_id', $supplierId);

        $branchId = (int)$request->input('branch');

        if($branchId == 0) $branchId = 1;

        if(!empty($branchId))
        {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    public function queryDisplay(Qr $query, \SkillDo\Http\Request $request, $data = []): Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query
            ->orderBy('created', 'desc');

        return $query;
    }

    public function dataDisplay($objects)
    {
        foreach ($objects as $object)
        {
            $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
            $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        }

        return $objects;
    }
}

class PurchaseReturn extends SKDObjectTable
{
    public int $supplierId = 0;

    protected string $module = 'suppliers_purchase_returns';

    protected mixed $model = \Stock\Model\PurchaseReturn::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã trả hàng'),
            'column' => fn($item, $args) => ColumnView::make('target_code', $item, $args)->html(function ($column){
                echo '<a href="#" class="js_btn_target" data-target="purchase-return" data-target-id="'.$column->item->id.'" data-target-cash-flow="0">'.$column->item->code.'</a>';
            })
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['return_discount'] = [
            'label'  => trans('Giảm giá'),
            'column' => fn($item, $args) => ColumnText::make('return_discount', $item, $args)->number()
        ];

        $this->_column_headers['return_total'] = [
            'label'  => trans('NCC cần trả'),
            'column' => fn($item, $args) => ColumnText::make('return_total', $item, $args)
                ->value(function ($item) {
                    return $item->subtotal - $item->return_discount - $item->total_payment;
                })
                ->number()
        ];

        $this->_column_headers['total_payment'] = [
            'label'  => trans('NCC đã trả'),
            'column' => fn($item, $args) => ColumnText::make('total_payment', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Stock\Status\PurchaseReturn::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Stock\Status\PurchaseReturn::tryFrom($status)->label();
                })
        ];

        return $this->_column_headers;
    }

    function headerFilter(Form $form, Request $request)
    {
        $branch = Branch::gets();

        $branchOptions = [];

        foreach ($branch as $item) {
            $branchOptions[$item->id] = $item->name;
        }

        $form->select2('branch', $branchOptions, [], request()->input('branch'));

        $form->hidden('supplierId',  [], (!empty($this->supplierId)) ? $this->supplierId : $request->input('supplierId'));

        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $supplierId = trim($request->input('supplierId'));

        $query->where('supplier_id', $supplierId);

        $branchId = (int)$request->input('branch');

        if($branchId == 0) $branchId = 1;

        if(!empty($branchId))
        {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    public function queryDisplay(Qr $query, \SkillDo\Http\Request $request, $data = []): Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query
            ->orderBy('created', 'desc');

        return $query;
    }

    public function dataDisplay($objects)
    {
        foreach ($objects as $object)
        {
            $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
            $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        }

        return $objects;
    }
}

class Debt extends SKDObjectTable
{
    public int $supplierId = 0;

    protected string $module = 'suppliers_debt';

    protected mixed $model = \Stock\Model\Debt::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['target_code'] = [
            'label'  => trans('Mã phiếu'),
            'column' => fn($item, $args) => ColumnView::make('target_code', $item, $args)->html(function ($column){
                if(!empty($column->item->target_id))
                {
                    if($column->item->target_type == \Stock\Prefix::adjustment->value)
                    {
                        echo '<a href="#" class="js_btn_target" data-target="adjustment" data-target-id="'.$column->item->target_id.'">'.$column->item->target_code.'</a>';
                    }
                    else if($column->item->target_type == \Stock\Prefix::purchaseOrder->value)
                    {
                        echo '<a href="#" class="js_btn_target" data-target="purchase-order" data-target-id="'.$column->item->target_id.'" data-target-cash-flow="0">'.$column->item->target_code.'</a>';
                    }
                    else
                    {
                        echo '<a href="#" class="js_btn_target" data-target="cash-flow" data-target-id="'.$column->item->target_id.'">'.$column->item->target_code.'</a>';
                    }

                }
                else
                {
                    echo $column->item->target_code;
                }
            })
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['target_type_name'] = [
            'label'  => trans('Loại'),
            'column' => fn($item, $args) => ColumnText::make('target_type_name', $item, $args)
        ];

        $this->_column_headers['amount'] = [
            'label'  => trans('Giá trị'),
            'column' => fn($item, $args) => ColumnText::make('amount', $item, $args)
                ->value(function ($item) { return $item->amount*-1;})
                ->number()
        ];

        $this->_column_headers['total'] = [
            'label'  => trans('Nợ cần trả NCC'),
            'column' => fn($item, $args) => ColumnText::make('balance', $item, $args)
                ->value(function ($item) { return $item->balance*-1;})
                ->number()
        ];

        return $this->_column_headers;
    }

    function headerFilter(Form $form, Request $request)
    {
        $form->hidden('supplierId',  [], (!empty($this->supplierId)) ? $this->supplierId : $request->input('supplierId'));

        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $supplierId = trim($request->input('supplierId'));

        $query->where('partner_id', $supplierId);

        return $query;
    }

    public function queryDisplay(Qr $query, \SkillDo\Http\Request $request, $data = []): Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query
            ->orderBy('created', 'desc');

        return $query;
    }

    public function dataDisplay($objects)
    {
        foreach ($objects as $object)
        {
            $object->target_type_name = '';

            if($object->target_type === \Stock\Prefix::purchaseOrder->value)
            {
                $object->target_type_name = 'Nhập hàng';
            }

            if($object->target_type === \Stock\Prefix::purchaseReturn->value)
            {
                $object->target_type_name = 'Trả hàng nhà cung cấp';
            }

            if($object->target_type === 'TT'.\Stock\Prefix::purchaseOrder->value)
            {
                $object->target_type_name = 'Thanh toán';
            }

            if($object->target_type === 'PT'.\Stock\Prefix::purchaseReturn->value)
            {
                $object->target_type_name = 'Thanh toán';
            }

            if($object->target_type === 'PC')
            {
                $object->target_type_name = 'Thanh toán';
            }

            if($object->target_type === \Stock\Prefix::adjustment->value)
            {
                $object->target_type_name = 'Điều chỉnh';
            }

            $object->time = !empty($object->time) ? $object->time : strtotime($object->created);

            $object->time = date('d/m/Y H:s', $object->time);
        }

        return $objects;
    }
}