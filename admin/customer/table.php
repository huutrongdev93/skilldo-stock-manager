<?php
namespace Stock\Table\Customer;

use Admin;
use Branch;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\Columns\ColumnView;
use SkillDo\Table\SKDObjectTable;

class Debt extends SKDObjectTable
{
    public int $userId = 0;

    protected string $module = 'users_debt';

    protected mixed $model = \Stock\Model\UserDebt::class;

    function getColumns() {

        $this->_column_headers = [];

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
            'label'  => trans('Nợ cần trả'),
            'column' => fn($item, $args) => ColumnText::make('balance', $item, $args)
                ->value(function ($item) { return $item->balance*-1;})
                ->number()
        ];

        return $this->_column_headers;
    }

    function headerFilter(Form $form, Request $request)
    {
        $form->hidden('id',  [], (!empty($this->userId)) ? $this->userId : $request->input('userId'));
        $form->hidden('view',  [], 'debt');

        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $userId = trim($request->input('id'));

        $query->where('partner_id', $userId);

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
            $object->time = !empty($object->time) ? $object->time : strtotime($object->created);

            $object->time = date('d/m/Y H:s', $object->time);
        }

        return $objects;
    }
}