<?php
namespace Stock\Table;

use Admin;
use Branch;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;

class TransferTable extends SKDObjectTable
{
    protected string $module = 'transfer';

    protected mixed $model = \Stock\Model\Transfer::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã chuyển hàng'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['send_date'] = [
            'label'  => trans('Ngày chuyển'),
            'column' => fn($item, $args) => ColumnText::make('send_date', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['receive_date'] = [
            'label'  => trans('Ngày nhận'),
            'column' => fn($item, $args) => ColumnText::make('receive_date', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['from_branch_name'] = [
            'label'  => trans('Từ chi nhánh'),
            'column' => fn($item, $args) => ColumnText::make('from_branch_name', $item, $args)
        ];

        $this->_column_headers['to_branch_name'] = [
            'label'  => trans('Tới chi nhánh'),
            'column' => fn($item, $args) => ColumnText::make('to_branch_name', $item, $args)
        ];

        $this->_column_headers['total_send_price'] = [
            'label'  => trans('Giá trị chuyển'),
            'column' => fn($item, $args) => ColumnText::make('total_send_price', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Stock\Status\Transfer::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Stock\Status\Transfer::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $branch = \Stock\Helper::getBranchCurrent();

        $buttons = [];

        $data = [
            ...$item->toArray(),
        ];

        $data['status'] = Admin::badge(\Stock\Status\Transfer::tryFrom($item->status)->badge(), \Stock\Status\Transfer::tryFrom($item->status)->label());

        $data['total_send_price'] = \Prd::price($item->total_send_price);

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-sharp-duotone fa-solid fa-eye"></i>',
            'tooltip' => 'Chi tiết',
            'data-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode($data)),
            'class' => 'js_transfer_btn_detail'
        ]);

        if($item->status !== \Stock\Status\Transfer::cancel->value)
        {
            if($item->status === \Stock\Status\Transfer::draft->value || ($item->status !== \Stock\Status\Transfer::success->value && $item->to_branch_id == $branch->id))
            {
                $buttons[] = Admin::button('blue', [
                    'icon' => Admin::icon('edit'),
                    'href' => \Url::route('admin.stock.transfers.edit', ['id' => $item->id]),
                    'tooltip' => 'Cập nhật',
                ]);
            }

            if($item->status !== \Stock\Status\Transfer::success->value)
            {
                $buttons['cancel'] = Admin::btnConfirm('red', [
                    'icon'      => Admin::icon('close'),
                    'tooltip'   => 'Đồng ý',
                    'id'        => $item->id,
                    'model'     =>  \Stock\Model\Transfer::class,
                    'ajax'      => 'TransferAdminAjax::cancel',
                    'heading'   => 'Đồng ý',
                    'description' => 'Bạn có chắc chắn muốn xác nhận hủy phiếu chuyển hàng này?',
                    'attr' => [
                        'callback-success' => 'TransferIndexHandle.cancelSuccess',
                    ]
                ]);
            }
        }

        $buttons['action'] = \Plugin::partial(STOCK_NAME, 'admin/transfer/table-action', ['item' => $item]);

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
        $form->select2('status', \Stock\Status\Transfer::options()
            ->pluck('label', 'value')
            ->prepend('Tất cả trạng thái', '')
            ->toArray(), [], $request->input('status'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $time = trim($request->input('time'));

        if(!empty($time))
        {
            $time = explode(' - ', $time);

            if(count($time) == 2)
            {
                $timeStart  = date('Y/m/d', strtotime(str_replace('/', '-', $time[0]))).' 00:00:00';

                $timeEnd    = date('Y/m/d', strtotime(str_replace('/', '-', $time[1]))).' 23:59:59';

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

        $branch = \Stock\Helper::getBranchCurrent();

        if(!empty($branch))
        {
            $query->where(function ($qr) use ($branch) {
                $qr->where('from_branch_id', $branch->id);
                $qr->orWhere(function ($q) use ($branch) {
                    $q->where('to_branch_id', $branch->id);
                    $q->where('status', '<>', \Stock\Status\Transfer::draft->value);
                });
            });
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