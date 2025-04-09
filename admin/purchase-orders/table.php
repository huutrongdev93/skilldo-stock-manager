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

class PurchaseOrder extends SKDObjectTable
{
    protected string $module = 'purchase_orders';

    protected mixed $model = \Skdepot\Model\PurchaseOrder::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã nhập hàng'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['branch_name'] = [
            'label'  => trans('Chi nhánh'),
            'column' => fn($item, $args) => ColumnText::make('branch_name', $item, $args)
        ];

        $this->_column_headers['supplier_name'] = [
            'label'  => trans('Nhà cung cấp'),
            'column' => fn($item, $args) => ColumnText::make('supplier_name', $item, $args)
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
                    return \Skdepot\Status\PurchaseOrder::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Skdepot\Status\PurchaseOrder::tryFrom($status)->label();
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
            'purchase_date' => $item->purchase_date,
            'branch_name' => $item->branch_name,
            'user_created_name' => $item->user_created_name,
            'purchase_name' => $item->purchase_name,
            'supplier_name' => $item->supplier_name,
            'status' => Admin::badge(\Skdepot\Status\PurchaseOrder::tryFrom($item->status)->badge(), \Skdepot\Status\PurchaseOrder::tryFrom($item->status)->label()),
            'total_quantity' => $item->total_quantity,
            'subtotal' => \Prd::price($item->subtotal),
            'discount' => \Prd::price($item->discount),
            'total_payment' => \Prd::price($item->total_payment),
            'payment' => \Prd::price($item->subtotal - $item->total_payment - $item->discount),
        ];

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-sharp-duotone fa-solid fa-eye"></i>',
            'tooltip' => 'Chi tiết',
            'data-target' => 'purchase-order',
            'data-target-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode($data)),
            'class' => 'js_btn_target'
        ]);

        if($item->status === \Skdepot\Status\PurchaseOrder::draft->value)
        {
            $buttons[] = Admin::button('blue', [
                'icon' => Admin::icon('edit'),
                'href' => \Url::route('admin.purchase.orders.edit', ['id' => $item->id]),
                'tooltip' => 'Cập nhật',
            ]);

            $buttons['cancel'] = Admin::btnConfirm('red', [
                'icon'      => Admin::icon('close'),
                'tooltip'   => 'Đồng ý',
                'id'        => $item->id,
                'model'     =>  \Skdepot\Model\PurchaseOrder::class,
                'ajax'      => 'PurchaseOrderAdminAjax::cancel',
                'heading'   => 'Đồng ý',
                'description' => 'Bạn có chắc chắn muốn xác nhận hủy phiếu nhập hàng này?',
                'attr' => [
                    'callback-success' => 'purchaseOrderIndexHandle.cancelSuccess',
                ]
            ]);
        }

        if($item->status === \Skdepot\Status\PurchaseOrder::success->value)
        {
            $buttons[] = Admin::button('green', [
                'icon' => '<i class="fa fa-reply-all"></i>',
                'href' => \Url::route('admin.purchase.returns.edit', ['id' => $item->id]).'?type=purchase-orders',
                'tooltip' => 'Trả hàng nhập hàng',
                'target' => '_blank'
            ]);
        }

        $buttons['action'] = \Plugin::partial(SKDEPOT_NAME, 'admin/purchase-order/table-action', ['item' => $item]);

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
        $form->select2('status', \Skdepot\Status\PurchaseOrder::options()
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
            'href' => Url::route('admin.purchase.orders.new')
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
                $timeStart  = strtotime(str_replace('/', '-', $time[0]).' 00:00:00');

                $timeEnd    = strtotime(str_replace('/', '-', $time[1]).' 00:00:00');

                $query->where('purchase_date', '>=', $timeStart);

                $query->where('purchase_date', '<=', $timeEnd);
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
            $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
            $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        }

        return $objects;
    }
}