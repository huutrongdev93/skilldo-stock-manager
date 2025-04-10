<?php
use SkillDo\Http\Request;

class PurchaseOrdersController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Skdepot\Table\PurchaseOrder();

        Cms::setData('table', $table);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/purchase-order/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        $source = $request->input('source');

        $table = new \Skdepot\Table\PurchaseOrder\ProductAdd();

        Cms::setData('table', $table);

        Cms::setData('form', $this->form());

        Cms::setData('action', 'add');

        Cms::setData('source', $source);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/purchase-order/add', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        Cms::setData('action', 'edit');

        $type = $request->input('type');

        $purchaseOrder = \Skdepot\Model\PurchaseOrder::find($id);

        if($type === 'clone')
        {
            $purchaseOrder->code = 'Copy_'.$purchaseOrder->code;

            Cms::setData('action', 'clone');
        }

        Cms::setData('purchaseOrder', $purchaseOrder);

        $table = new \Skdepot\Table\PurchaseOrder\ProductAdd();

        Cms::setData('table', $table);

        Cms::setData('form', $this->form($purchaseOrder));

        $this->template->setView(SKDEPOT_NAME.'/views/admin/purchase-order/add', 'plugin');

        $this->template->render();
    }

    public function form($object = []): \SkillDo\Form\Form
    {
        $form = form()
                    ->startDefault('<div class="stock-form-group form-group">')
                    ->endDefault('</div>');

        $form

            ->addGroup(function ($f) use ($object) {
                $f->text('code', [
                    'label' => 'Mã phiếu nhập',
                    'placeholder' => 'Mã phiếu nhập tự động',
                    'start' => 6
                ], $object->code ?? '');
                $f->datetime('time', [
                    'label' => 'Ngày nhập hàng',
                    'start' => 6
                ], date('d/m/Y H:i', $object->purchase_date ?? time()));
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->addGroup(function ($f) use ($object) {
                $f->popoverAdvance('purchase', [
                    'label'     => 'Người nhập hàng',
                    'search'    => 'member',
                    'multiple'  => false,
                    'noImage'   => true,
                    'start'     => 6
                ], $object->purchase_id ?? Auth::id())
                ->popoverAdvance('supplier', [
                    'label'     => 'Nhà cung cấp',
                    'search'    => 'suppliers',
                    'multiple'  => false,
                    'noImage'   => true,
                    'start'     => 6
                ], $object->supplier_id ?? Auth::id());
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Trạng thái</b>  <b>'.\Skdepot\Status\PurchaseOrder::draft->label().'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Tổng tiền hàng</b>  <b class="js_purchase_order_cost_total">0</b></p>')
            ->price('discount', [
                'label' => 'Giảm giá',
            ], number_format($object->discount ?? 0))
            ->price('total_payment', [
                'label' => 'Đã trả NCC',
            ], number_format($object->total_payment ?? 0))
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4">Cần trả NCC  <b class="js_purchase_order_total_payment">0</b></p>')
            ->textarea('note', [
                'label' => 'Ghi chú'
            ], $object->note ?? '');

        return $form;
    }
}