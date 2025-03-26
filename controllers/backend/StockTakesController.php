<?php
use SkillDo\Http\Request;

class StockTakesController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Stock\Table\StockTake();

        $tableProduct = new \Stock\Table\StockTake\ProductDetail();

        Cms::setData('table', $table);

        Cms::setData('tableProduct', $tableProduct);

        $this->template->setView(STOCK_NAME.'/views/admin/stock-take/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        $table = new \Stock\Table\StockTake\ProductAdd();

        Cms::setData('table', $table);

        Cms::setData('form', $this->form());

        Cms::setData('action', 'add');

        $this->template->setView(STOCK_NAME.'/views/admin/stock-take/add', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        Cms::setData('action', 'edit');

        $type = $request->input('type');

        $object = \Stock\Model\StockTake::find($id);

        if($type === 'clone')
        {
            $object->code = 'Copy_'.$object->code;

            Cms::setData('action', 'clone');
        }

        Cms::setData('id', $id);

        Cms::setData('object', $object);

        Cms::setData('table', (new \Stock\Table\StockTake\ProductAdd()));

        Cms::setData('form', $this->form($object));

        $this->template->setView(STOCK_NAME.'/views/admin/stock-take/add', 'plugin');

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
                    'label' => 'Mã kiểm kho',
                    'placeholder' => 'Mã phiếu tự động',
                    'start' => 6
                ], $object->code ?? '');
                $f->datetime('time', [
                    'label' => 'Ngày kiểm kho',
                    'start' => 6
                ], date('d/m/Y H:i', $object->purchase_date ?? time()));
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->popoverAdvance('user', [
                'label' => 'Người kiểm kho',
                'search' => 'user',
                'multiple' => false,
                'noImage' => true,
            ], $object->user_id ?? Auth::id())
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Trạng thái</b>  <b>'.\Stock\Status\PurchaseOrder::draft->label().'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Tổng SL thực tế</b>  <b class="js_stock_take_total_actual_quantity">0</b></p>')
            ->textarea('note', [
                'label' => 'Ghi chú'
            ], $object->note ?? '');

        return $form;
    }
}