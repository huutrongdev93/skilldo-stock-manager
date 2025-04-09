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
        $table = new \Skdepot\Table\DamageItem();

        $tableProduct = new \Skdepot\Table\DamageItems\ProductDetail();

        Cms::setData('table', $table);

        Cms::setData('tableProduct', $tableProduct);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/damage-items/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        Cms::setData('table', (new \Skdepot\Table\DamageItems\ProductAdd()));

        Cms::setData('form', $this->form());

        Cms::setData('action', 'add');

        $this->template->setView(SKDEPOT_NAME.'/views/admin/damage-items/add', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        $type = $request->input('type');

        $object = \Skdepot\Model\DamageItem::find($id);

        if($type === 'clone')
        {
            $object->code = 'Copy_'.$object->code;
        }

        Cms::setData('object', $object);

        Cms::setData('id', $id);

        Cms::setData('action', (!empty($type)) ? $type : 'edit');

        Cms::setData('table', (new \Skdepot\Table\DamageItems\ProductAdd()));

        Cms::setData('form', $this->form($object));

        $this->template->setView(SKDEPOT_NAME.'/views/admin/damage-items/add', 'plugin');

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
                    'label' => 'Mã xuất hủy',
                    'placeholder' => 'Mã phiếu nhập tự động',
                    'start' => 6
                ], $object->code ?? '');
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
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Trạng thái</b>  <b>'.\Skdepot\Status\DamageItem::draft->label().'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Tổng giá trị hủy</b>  <b class="js_damage_items_cost_total">0</b></p>')
            ->textarea('note', [
                'label' => 'Ghi chú'
            ], $object->note ?? '');

        return $form;
    }
}