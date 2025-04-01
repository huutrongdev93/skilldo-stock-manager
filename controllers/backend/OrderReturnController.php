<?php

use Illuminate\Support\Collection;
use SkillDo\Http\Request;

class OrderReturnController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Stock\Table\OrderReturn();

        $tableProduct = new \Stock\Table\OrderReturn\ProductDetail();

        Cms::setData('table', $table);

        Cms::setData('tableProduct', $tableProduct);

        $this->template->setView(STOCK_NAME.'/views/admin/order-return/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        $orderId = $request->input('orderId');

        $order = \Ecommerce\Model\Order::find($orderId);

        if(empty($order) || $order->status != \Ecommerce\Enum\Order\Status::COMPLETED->value || $order->status_pay != \Ecommerce\Enum\Order\StatusPay::COMPLETED->value)
        {
            $order = null;
        }

        if(empty($order))
        {
            Cms::setData('table', new \Stock\Table\OrderReturn\Order());

            $this->template->setView(STOCK_NAME.'/views/admin/order-return/search-order', 'plugin');

            $this->template->render();
        }
        else
        {
            $table = new \Stock\Table\OrderReturn\ProductAdd();

            Cms::setData('table', $table);

            foreach($order->items as $item)
            {
                $item->option = @unserialize($item->option);

                $attributes = OrderItem::getMeta($item->id, 'attribute', true);

                $item->attributes = '';

                if(have_posts($attributes)) {

                    foreach ($attributes as $attribute) {
                        $item->attributes .= $attribute.' / ';
                    }

                    $item->attributes = trim(trim($item->attributes), '/' );
                }

                $item->fullname = $item->title.(!empty($item->attributes) ? ' - '.$item->attributes : '');
            }

            Cms::setData('orderItems', $order->items);

            unset($order->items);

            Cms::setData('order', $order);

            Cms::setData('form', $this->form($order));

            Cms::setData('action', 'add');

            $this->template->setView(STOCK_NAME.'/views/admin/order-return/add', 'plugin');

            $this->template->render();
        }
    }

    public function edit(Request $request, $id): void
    {
    }

    public function form($order, $object = []): \SkillDo\Form\Form
    {
        $form = form()
            ->startDefault('<div class="stock-form-group form-group">')
            ->endDefault('</div>');

        $form
            ->popoverAdvance('user_id', [
                'label'     => 'Người trả hàng',
                'search'    => 'user',
                'multiple'  => false,
                'noImage'   => true,
            ], $object->user_id ?? Auth::id())
            ->none('<p class="d-flex align-items-center h-10 mb-4 text-2xl"><b class="color-green">Trả hàng</b> &nbsp;/&nbsp;<b><a href="'.Url::admin('plugins/order/detail/'.$order->id).'" target="_blank">DH'.$order->code.'</a></b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4">Số lượng hàng mua <b class="js_order_return_total_quantity_sell">0</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4">Tổng giá gốc hàng mua  <b class="js_order_return_total_price_sell">0</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4">Tổng số lượng trả hàng  <b class="js_order_return_total_quantity">0</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4">Tổng tiền trả hàng  <b class="js_order_return_total_price">0</b></p>')
            ->price('discount', [
                'label' => 'Giảm giá',
            ], number_format($object->discount ?? 0))
            ->price('surcharge', [
                'label' => 'Phí trả hàng',
            ], number_format($object->discount ?? 0))
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Cần trả khách</b>  <b class="js_order_return_total_payment color-blue">0</b></p>')
            ->price('totalPaid', [
                'label' => 'Tiền trả khách',
            ], number_format($object->discount ?? 0))
            ->textarea('note', [
                'placeholder' => 'Ghi chú',
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ], $object->note ?? '');

        return $form;
    }
}