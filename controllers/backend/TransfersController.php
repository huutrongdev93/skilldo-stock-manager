<?php

use Illuminate\Support\Collection;
use SkillDo\Http\Request;

class TransfersController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Skdepot\Table\TransferTable();

        $tableProduct = new \Skdepot\Table\Transfer\ProductDetail();

        Cms::setData('table', $table);

        Cms::setData('tableProduct', $tableProduct);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/transfer/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        $table = new \Skdepot\Table\Transfer\ProductSendAdd();

        Cms::setData('table', $table);

        Cms::setData('form', $this->formSend());

        Cms::setData('action', 'add');

        $this->template->setView(SKDEPOT_NAME.'/views/admin/transfer/add-send', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        Cms::setData('action', 'edit');

        $type = $request->input('type');

        $branch = \Skdepot\Helper::getBranchCurrent();

        $object = \Skdepot\Model\Transfer::whereKey($id)->where(function ($qr) use ($branch) {
            $qr->where('from_branch_id', $branch->id);
            $qr->orWhere(function ($q) use ($branch) {
                $q->where('to_branch_id', $branch->id);
                $q->where('status', \Skdepot\Status\Transfer::process->value);
            });
        })->first();

        if($type === 'clone')
        {
            $object->code = 'Copy_'.$object->code;

            $object->send_time = time();

            Cms::setData('action', 'clone');
        }

        Cms::setData('id', $id);

        Cms::setData('object', $object);

        if($type == 'clone' || $object->status === \Skdepot\Status\Transfer::draft->value)
        {
            Cms::setData('table', (new \Skdepot\Table\Transfer\ProductSendAdd()));

            Cms::setData('form', $this->formSend($object));

            $this->template->setView(SKDEPOT_NAME.'/views/admin/transfer/add-send', 'plugin');
        }
        else
        {
            Cms::setData('table', (new \Skdepot\Table\Transfer\ProductReceiveAdd()));

            Cms::setData('form', $this->formReceive($object));

            $this->template->setView(SKDEPOT_NAME.'/views/admin/transfer/add-receive', 'plugin');
        }

        $this->template->render();
    }

    public function formSend($object = []): \SkillDo\Form\Form
    {
        $branchCurrent = \Skdepot\Helper::getBranchCurrent();

        $branchs = \Skdepot\Helper::getBranchAll();

        $branchs = $branchs->filter(function ($branch) use ($branchCurrent) {
            return ($branch->id !== $branchCurrent->id);
        })->pluck('name', 'id')->prepend('---Chọn chi nhánh---', '')->toArray();

        $form = form()
            ->startDefault('<div class="stock-form-group form-group">')
            ->endDefault('</div>');

        $form
            ->addGroup(function ($f) use ($object) {
                $f->text('code', [
                    'label' => 'Mã chuyển hàng',
                    'placeholder' => 'Mã phiếu tự động',
                    'start' => 6
                ], $object->code ?? '');
                $f->datetime('time', [
                    'label' => 'Ngày chuyển hàng',
                    'start' => 6
                ], date('d/m/Y H:i', $object->send_date ?? time()));
            }, [
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ])
            ->select2('to_branch_id', $branchs, [
                'label' => 'Chuyển tới',
            ], $object->to_branch_id ?? '')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Trạng thái</b>  <b>'.\Skdepot\Status\PurchaseOrder::draft->label().'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><b>Tổng số lượng</b>  <b class="js_transfer_total_quantity">0</b></p>')
            ->textarea('note', [
                'placeholder' => 'Ghi chú',
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ], $object->note ?? '');

        return $form;
    }

    public function formReceive($object = []): \SkillDo\Form\Form
    {
        $form = form()
            ->startDefault('<div class="stock-form-group form-group">')
            ->endDefault('</div>');

        if(empty($object->receive_date))
        {
            $object->receive_date = time();
        }

        $form
            ->datetime('time', [
                'label' => 'Ngày nhận hàng',
            ], date('d/m/Y H:i', $object->receive_date))
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Mã chuyển hàng</span>  <b>'.$object->code.'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Trạng thái</span>  <b>'.Admin::badge(\Skdepot\Status\Transfer::tryFrom($object->status)->badge(), \Skdepot\Status\Transfer::tryFrom($object->status)->label()).'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Chi nhánh gửi</span>  <b>'.$object->from_branch_name.'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Ngày chuyển</span>  <b>'.date('d/m/Y H:i', $object->send_date).'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Ghi chú</span>  <b>'.$object->note.'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Tổng số lượng chuyển</span>  <b>'.$object->total_send_quantity.'</b></p>')
            ->none('<p class="d-flex justify-content-between align-items-center h-10 mb-4"><span>Tổng số lượng nhận</span>  <b class="js_transfer_receive_total_quantity">'.$object->total_receive_quantity.'</b></p>')
            ->textarea('note', [
                'placeholder' => 'Ghi chú',
                'start' => '<div class="form-group"><div class="row">',
                'end' => '</div></div>',
            ], $object->note ?? '');

        return $form;
    }
}