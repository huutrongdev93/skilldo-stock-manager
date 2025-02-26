<?php
use SkillDo\Http\Request;

class DamageItemsController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Stock\Table\DamageItem();

        $tableProduct = new \Stock\Table\DamageItems\ProductDetail();

        Cms::setData('table', $table);

        Cms::setData('tableProduct', $tableProduct);

        $this->template->setView(STOCK_NAME.'/views/admin/damage-items/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        Cms::setData('table', (new \Stock\Table\DamageItems\ProductAdd()));

        Cms::setData('form', $this->form());

        Cms::setData('action', 'add');

        $this->template->setView(STOCK_NAME.'/views/admin/damage-items/add', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        $type = $request->input('type');

        $object = \Stock\Model\DamageItem::find($id);

        if($type === 'clone')
        {
            $object->code = 'Copy_'.$object->code;
        }

        Cms::setData('object', $object);

        Cms::setData('id', $id);

        Cms::setData('action', (!empty($type)) ? $type : 'edit');

        Cms::setData('table', (new \Stock\Table\DamageItems\ProductAdd()));

        Cms::setData('form', $this->form($object));

        $this->template->setView(STOCK_NAME.'/views/admin/damage-items/add', 'plugin');

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
                    'label' => 'Ngày xuất hủy',
                    'start' => 6
                ], date('d/m/Y H:i', $object->purchase_date ?? time()));
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->popoverAdvance('damage', [
                'label' => 'Người xuất hủy',
                'search' => 'user',
                'multiple' => false,
                'noImage' => true,
            ], $object->purchase_id ?? Auth::id())
            ->text('code', [
                'label' => 'Mã xuất hủy',
                'placeholder' => 'Mã phiếu nhập tự động'
            ], $object->code ?? '')
            ->none('<p class="d-flex justify-content-between mb-4"><b>Trạng thái</b>  <b>'.\Stock\Status\DamageItem::draft->label().'</b></p>')
            ->none('<p class="d-flex justify-content-between mb-4"><b>Tổng giá trị hủy</b>  <b class="js_damage_items_cost_total">0</b></p>')
            ->textarea('note', [
                'label' => 'Ghi chú'
            ], $object->note ?? '');

        return $form;
    }
}