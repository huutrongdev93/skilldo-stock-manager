<?php
namespace Skdepot\Table;

use Admin;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;
use Url;

class StockTake extends SKDObjectTable
{
    protected string $module = 'stock_takes';

    protected mixed $model = \Skdepot\Model\StockTake::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã kiểm kho'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['balance_date'] = [
            'label'  => trans('Ngày cân bằng'),
            'column' => fn($item, $args) => ColumnText::make('balance_date', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['branch_name'] = [
            'label'  => trans('Chi nhánh'),
            'column' => fn($item, $args) => ColumnText::make('branch_name', $item, $args)
        ];

        $this->_column_headers['total_actual_quantity'] = [
            'label'  => trans('SL thực tế'),
            'column' => fn($item, $args) => ColumnText::make('total_actual_quantity', $item, $args)
        ];

        $this->_column_headers['total_actual_price'] = [
            'label'  => trans('Tổng thực tế'),
            'column' => fn($item, $args) => ColumnText::make('total_actual_price', $item, $args)->number()
        ];

        $this->_column_headers['total_adjustment_quantity'] = [
            'label'  => trans('Tổng chênh lệch'),
            'column' => fn($item, $args) => ColumnText::make('total_adjustment_quantity', $item, $args)
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Skdepot\Status\StockTake::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Skdepot\Status\StockTake::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $data = [
            'code'        => $item->code,
            'branch_name' => $item->branch_name,
            'user_name' => $item->user_name,
            'balance_date' => $item->balance_date,
            'status' => Admin::badge(\Skdepot\Status\StockTake::tryFrom($item->status)->badge(), \Skdepot\Status\StockTake::tryFrom($item->status)->label()),
            'total_actual_quantity' => $item->total_actual_quantity,
            'total_actual_price' => \Prd::price($item->total_actual_price),
            'total_increase_quantity' => $item->total_increase_quantity,
            'total_increase_price' => \Prd::price($item->total_increase_price),
            'total_reduced_quantity' => $item->total_reduced_quantity,
            'total_reduced_price' => \Prd::price($item->total_reduced_price),
            'total_adjustment_quantity' => $item->total_adjustment_quantity,
            'total_adjustment_price' => \Prd::price($item->total_adjustment_price),
        ];

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-sharp-duotone fa-solid fa-eye"></i>',
            'tooltip' => 'Chi tiết',
            'data-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode($data)),
            'class' => 'js_stock_take_btn_detail'
        ]);

        if($item->status === \Skdepot\Status\StockTake::draft->value)
        {
            $buttons[] = Admin::button('blue', [
                'icon' => Admin::icon('edit'),
                'href' => \Url::route('admin.stock.takes.edit', ['id' => $item->id]),
                'tooltip' => 'Cập nhật',
            ]);

            $buttons['cancel'] = Admin::btnConfirm('red', [
                'icon'      => Admin::icon('close'),
                'tooltip'   => 'Đồng ý',
                'id'        => $item->id,
                'model'     =>  \Skdepot\Model\StockTake::class,
                'ajax'      => 'StockTakeAdminAjax::cancel',
                'heading'   => 'Đồng ý',
                'description' => 'Bạn có chắc chắn muốn xác nhận hủy phiếu kiểm hàng hàng này?',
                'attr' => [
                    'callback-success' => 'stockTakeIndexHandle.cancelSuccess',
                ]
            ]);
        }

        $buttons['action'] = \Plugin::partial(SKDEPOT_NAME, 'admin/stock-take/table-action', ['item' => $item]);

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
        $form->select2('status', \Skdepot\Status\StockTake::options()
            ->pluck('label', 'value')
            ->prepend('Tất cả trạng thái', '')
            ->toArray(), [], $request->input('status'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.stock.takes.new')
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

        $status = $request->input('status');

        if(!empty($status))
        {
            $query->where('status', $status);
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
        foreach ($objects as $object)
        {
            $object->balance_date = !empty($object->balance_date) ? $object->balance_date : strtotime($object->created);
            $object->balance_date = date('d/m/Y H:s', $object->balance_date);
        }

        return $objects;
    }
}