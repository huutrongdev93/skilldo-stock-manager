<?php
namespace Skdepot\Table;

use Admin;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;

class CashFlow extends SKDObjectTable
{
    protected string $module = 'cash_flow';

    protected mixed $model = \Skdepot\Model\CashFlow::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã phiếu'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['group_name'] = [
            'label'  => trans('Loại thu chi'),
            'column' => fn($item, $args) => ColumnText::make('group_name', $item, $args)
        ];

        $this->_column_headers['partner_name'] = [
            'label'  => trans('Người nộp/nhận'),
            'column' => fn($item, $args) => ColumnText::make('partner_name', $item, $args)
        ];

        $this->_column_headers['amount'] = [
            'label'  => trans('Giá trị'),
            'column' => fn($item, $args) => ColumnText::make('amount', $item, $args)->number()
        ];

        $this->_column_headers['type'] = [
            'label'  => trans('Loại'),
            'column' => fn($item, $args) => ColumnBadge::make('amount', $item, $args)
                ->color(function (string $status) {
                    return ($status <= 0) ? 'red' : 'green';
                })
                ->label(function (string $status) {
                    return ($status <= 0) ? 'phiếu chi' : 'phiếu thu';
                })
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Skdepot\Status\CashFlow::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Skdepot\Status\CashFlow::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-sharp-duotone fa-solid fa-eye"></i>',
            'tooltip' => 'Chi tiết',
            'data-bill' => htmlspecialchars(json_encode($item->toObject())),
            'data-id' => $item->id,
            'data-target' => 'cash-flow',
            'data-target-id' => $item->id,
            'class' => 'js_btn_target'
        ]);

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-duotone fa-solid fa-print"></i>',
            'tooltip' => 'Chi tiết',
            'data-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode($item)),
            'class' => 'js_cash_flow_btn_print'
        ]);

        $buttons['cancel'] = Admin::btnConfirm('red', [
            'icon'      => Admin::icon('close'),
            'tooltip'   => 'Đồng ý',
            'id'        => $item->id,
            'model'     =>  \Skdepot\Model\CashFlow::class,
            'ajax'      => 'CashFlowAdminAjax::cancel',
            'heading'   => 'Đồng ý',
            'description' => 'Bạn có chắc chắn muốn xác nhận hủy phiếu thu/chi này?',
            'attr' => [
                'callback-success' => 'cashFlowIndexHandle.cancelSuccess',
            ]
        ]);

        return apply_filters('admin_'.$this->module.'_table_columns_action', $buttons);
    }

    function headerFilter(Form $form, Request $request)
    {
        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        $time = $request->input('time');

        if(empty($time))
        {
            $time = '01'.date('/m/Y').' - '.date('t/m/Y');
        }

        $form->text('keyword', ['placeholder' => 'Mã phiếu'], $request->input('keyword'));
        $form->daterange('time', [], $time);
        $form->select2('type', [
            '' => 'Tất cả',
            'receipt' => 'Phiếu thu',
            'payment' => 'Phiếu chi',
        ], [], $request->input('type'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu thu',
            'data-type' => 'receipt',
            'class' => 'js_cash_flow_btn_add'
        ]);

        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu chi',
            'data-type' => 'payment',
            'class' => 'js_cash_flow_btn_add'
        ]);

        $buttons[] = Admin::button('blue', [
            'icon' => Admin::icon('download'),
            'text' => 'Xuất file',
            'id' => 'js_btn_export_list'
        ]);

        $buttons[] = Admin::button('reload');

        return $buttons;
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $time = trim($request->input('time'));

        if(!empty($time))
        {
            $time = explode(' - ', $time);

            if(count($time) == 2)
            {
                $timeStart  = str_replace('/', '-', $time[0]).' 00:00:00';

                $timeEnd    = str_replace('/', '-', $time[1]).' 00:00:00';

                $query->where('created', '>=', $timeStart);

                $query->where('created', '<=', $timeEnd);
            }
        }

        $keyword = trim($request->input('keyword'));

        if(!empty($keyword))
        {
            $query->where('code', $keyword);
        }

        $type = $request->input('type');

        if($type == 'receipt')
        {
            $query->where('amount', '>', 0);
        }
        if($type == 'payment')
        {
            $query->where('amount', '<', 0);
        }

        $branch = \Skdepot\Helper::getBranchCurrent();

        if(!empty($branch))
        {
            $query->where('branch_id', $branch->id);
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
        return $objects;
    }
}