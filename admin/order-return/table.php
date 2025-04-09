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

class OrderReturn extends SKDObjectTable
{
    protected string $module = 'orders_returns';

    protected mixed $model = \Skdepot\Model\OrderReturn::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã trả hàng'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['user_name'] = [
            'label'  => trans('Người bán'),
            'column' => fn($item, $args) => ColumnText::make('user_name', $item, $args)
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['customer_name'] = [
            'label'  => trans('Khách hàng'),
            'column' => fn($item, $args) => ColumnText::make('customer_name', $item, $args)
        ];

        $this->_column_headers['total_payment'] = [
            'label'  => trans('Cần trả khách'),
            'column' => fn($item, $args) => ColumnText::make('total_payment', $item, $args)->number()
        ];

        $this->_column_headers['total_paid'] = [
            'label'  => trans('Đã trả'),
            'column' => fn($item, $args) => ColumnText::make('total_paid', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Skdepot\Status\OrderReturn::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Skdepot\Status\OrderReturn::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $branch = \Skdepot\Helper::getBranchCurrent();

        $buttons = [];

        $data = [
            ...$item->toArray(),
        ];

        $data['status'] = Admin::badge(\Skdepot\Status\OrderReturn::tryFrom($item->status)->badge(), \Skdepot\Status\OrderReturn::tryFrom($item->status)->label());

        $data['total_return'] = \Prd::price($item->total_return);
        $data['total_payment'] = \Prd::price($item->total_payment);
        $data['total_paid'] = \Prd::price($item->total_paid);

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-sharp-duotone fa-solid fa-eye"></i>',
            'tooltip' => 'Chi tiết',
            'data-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode($data)),
            'class' => 'js_order_return_btn_detail'
        ]);

        if($item->status !== \Skdepot\Status\OrderReturn::cancel->value)
        {
            $buttons['cancel'] = Admin::btnConfirm('red', [
                'icon'      => Admin::icon('close'),
                'tooltip'   => 'Đồng ý',
                'id'        => $item->id,
                'model'     =>  \Skdepot\Model\OrderReturn::class,
                'ajax'      => 'OrderReturnAdminAjax::cancel',
                'heading'   => 'Đồng ý',
                'description' => 'Bạn có chắc chắn muốn xác nhận hủy phiếu trả hàng này?',
                'attr' => [
                    'callback-success' => 'OrderReturnIndexHandle.cancelSuccess',
                ]
            ]);
        }

        $buttons['action'] = \Plugin::partial(SKDEPOT_NAME, 'admin/order-return/table-action', ['item' => $item]);

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
        $form->select2('status', \Skdepot\Status\OrderReturn::options()
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
            'href' => Url::route('admin.order.returns.new')
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