<?php
use SkillDo\Http\Request;

class CashFlowGroupController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function receiptIndex(Request $request): void
    {
        Cms::setData('table', (new \Stock\Table\CashFlowGroupReceipt()));

        Cms::setData('title', 'Loại phiếu thu');

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow-group/index', 'plugin');

        $this->template->render();
    }

    public function receiptAdd(Request $request): void
    {
        Cms::setData('module', 'cash_flow_group_receipt');

        Admin::creatForm('cash_flow_group_receipt');

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow-group/save', 'plugin');

        $this->template->render();
    }

    public function receiptEdit(Request $request, $id): void
    {
        Cms::setData('module', 'cash_flow_group_receipt');

        $object = \Stock\Model\CashFlowGroup::where('type', 'receipt')
            ->whereKey($id)
            ->first();

        if(have_posts($object))
        {
            Admin::creatForm('cash_flow_group_receipt', $object);
        }

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow-group/save', 'plugin');

        $this->template->render();
    }

    public function paymentIndex(Request $request): void
    {
        Cms::setData('module', 'cash_flow_group_payment');

        Cms::setData('table', (new \Stock\Table\CashFlowGroupPayment()));

        Cms::setData('title', 'Loại phiếu chi');

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow-group/index', 'plugin');

        $this->template->render();
    }

    public function paymentAdd(Request $request): void
    {
        Cms::setData('module', 'cash_flow_group_payment');

        Admin::creatForm('cash_flow_group_payment');

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow-group/save', 'plugin');

        $this->template->render();
    }

    public function paymentEdit(Request $request, $id): void
    {
        Cms::setData('module', 'cash_flow_group_payment');

        $object = \Stock\Model\CashFlowGroup::where('type', 'payment')
            ->whereKey($id)
            ->first();

        if(have_posts($object))
        {
            Admin::creatForm('cash_flow_group_payment', $object);
        }

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow-group/save', 'plugin');

        $this->template->render();
    }
}