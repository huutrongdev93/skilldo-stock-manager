<?php
namespace Stock\Table\OrderReturn;
use Admin;
use Prd;
use Sicommerce_Cart;
use SkillDo\Form\Form;
use SkillDo\Table\SKDObjectTable;
use SkillDo\Http\Request;
use Url;
use Qr;

class Order extends SKDObjectTable {

    protected string $module = 'orders_returns_search';

    protected mixed $model = \Ecommerce\Model\Order::class;

    function getColumns()
    {
        $this->_column_headers = [
            'code'             => [
                'label' => trans('order.title'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnView::make('code', $item, $args)->html(function(\SkillDo\Table\Columns\ColumnView $column) {
                    $url = Url::admin(Sicommerce_Cart::url('order').'/detail/'.$column->item->id);
                    echo '<a href="'.$url.'" style="font-weight:bold;" target="_blank">#'.$column->item->code.'</a>';
                })
            ],
            'created'          => [
                'label' => trans('table.created'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('created', $item, $args)->datetime('d/m/Y H:i')
            ],
            'billing_fullname' => [
                'label' => trans('table.order.customer'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnView::make('billing_fullname', $item, $args)->html(function(\SkillDo\Table\Columns\ColumnView $column) {
                    echo (!empty($column->item->billing_fullname)) ? '<p class="mb-0">'.$column->item->billing_fullname.'</p>' : '';
                    echo (!empty($column->item->billing_email)) ? '<p>'.$column->item->billing_email.'</p>' : '';
                })
            ],
            'billing_phone'    => [
                'label'  => trans('table.order.phone'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('billing_phone', $item, $args)
            ],
            'total'            => [
                'label' => trans('table.order.total'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('total', $item, $args)->value(function ($item) {
                    return Prd::price($item->total, $item->currency ?? []);
                })
            ],
        ];

        $this->_column_headers['action'] = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $buttons['edit'] = Admin::button('white', [
            'href' => Url::route('admin.order.returns.new').'?orderId='.$item->id,
            'text' => 'Chọn',
            'icon' => Admin::icon('edit')
        ]);

        return $buttons;
    }

    function headerSearch(Form $form, Request $request): Form
    {
        $form->text('code', [
            'placeholder' => 'Mã đơn hàng'
        ]);

        $form->text('name', [
            'placeholder' => 'Tên khách hàng'
        ]);

        $form->text('phone',[
            'placeholder' => 'Số điện thoại'
        ]);

        $form->daterange('time', [
            'placeholder' => 'Ngày tạo đơn'
        ]);

        return $form;
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $query->where('status', \Ecommerce\Enum\Order\Status::COMPLETED->value);

        $query->where('status_pay', \Ecommerce\Enum\Order\StatusPay::COMPLETED->value);

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

        $code = trim($request->input('code'));

        if(!empty($code))
        {
            $query->where('code', 'like', '%'.$code.'%');
        }

        $keyword = $request->input('keyword');

        if(!empty($keyword))
        {
            $keyword = trim($keyword);
            $query->setMetaQuery('billing_fullname', $keyword, 'like');
        }

        $branch = \Stock\Helper::getBranchCurrent();

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