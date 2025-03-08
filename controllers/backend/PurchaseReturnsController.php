<?php
use SkillDo\Http\Request;

class PurchaseReturnsController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Stock\Table\PurchaseReturn();

        Cms::setData('table', $table);

        $this->template->setView(STOCK_NAME.'/views/admin/purchase-return/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        $table = new \Stock\Table\PurchaseReturn\ProductAdd();

        Cms::setData('table', $table);

        Cms::setData('form', $this->form());

        Cms::setData('action', 'add');

        $this->template->setView(STOCK_NAME.'/views/admin/purchase-return/add', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        Cms::setData('action', 'edit');

        $type = $request->input('type');

        if($type != 'purchase-orders')
        {
            $purchaseReturn = \Stock\Model\PurchaseReturn::find($id);

            if($type === 'clone')
            {
                $purchaseReturn->code = 'Copy_'.$purchaseReturn->code;
            }
        }

        if(!empty($type))
        {
            Cms::setData('action', $type);
        }

        Cms::setData('purchaseReturn', $purchaseReturn ?? null);

        Cms::setData('id', $id);

        Cms::setData('table', (new \Stock\Table\PurchaseReturn\ProductAdd()));

        Cms::setData('form', $this->form($purchaseReturn ?? []));

        $this->template->setView(STOCK_NAME.'/views/admin/purchase-return/add', 'plugin');

        $this->template->render();
    }

    public function form($object = []): \SkillDo\Form\Form
    {
        $branches = \Branch::all()->pluck('name', 'id')->toArray();

        $form = form()
            ->startDefault('<div class="stock-form-group form-group">')
            ->endDefault('</div>');

        $form

            ->addGroup(function ($f) use ($branches, $object) {
                $f->select2('branch_id', $branches, ['label' => 'Chi nhánh', 'start' => 6], $object->branch_id ?? 0);
                $f->datetime('time', [
                    'label' => 'Ngày trả hàng',
                    'start' => 6
                ], date('d/m/Y H:i', $object->purchase_date ?? time()));
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->addGroup(function ($f) use ($object) {
                $f->popoverAdvance('purchase', [
                    'label' => 'Người trả hàng',
                    'search' => 'user',
                    'multiple' => false,
                    'noImage' => true,
                    'start' => 6
                ], $object->purchase_id ?? Auth::id());
                $f->popoverAdvance('supplier', [
                    'label' => 'Nhà cung cấp',
                    'search' => 'suppliers',
                    'multiple' => false,
                    'noImage' => true,
                    'start' => 6
                ], $object->supplier_id ?? Auth::id());
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->text('code', [
                'label' => 'Mã phiếu trả hàng',
                'placeholder' => 'Mã phiếu nhập tự động'
            ], $object->code ?? '')
            ->none('<p class="d-flex justify-content-between mb-4"><b>Trạng thái</b>  <b>'.\Stock\Status\PurchaseReturn::draft->label().'</b></p>')
            ->none('<p class="d-flex justify-content-between mb-4"><b>Tổng tiền hàng</b>  <b class="js_purchase_return_cost_total">0</b></p>')
            ->price('return_discount', [
                'label' => 'Giảm giá',
            ], number_format($object->return_discount ?? 0))
            ->price('total_payment', [
                'label' => 'NCC đã trả',
            ], number_format($object->total_payment ?? 0))
            ->none('<p class="d-flex justify-content-between mb-4">Nhà cung cấp cần trả  <b class="js_purchase_return_total_payment">0</b></p>')
            ->textarea('note', [
                'label' => 'Ghi chú'
            ], $object->note ?? '');

        return $form;
    }
}