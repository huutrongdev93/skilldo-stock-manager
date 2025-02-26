<?php
use SkillDo\Http\Request;

class SuppliersController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Ecommerce\Table\AdminSuppliers();

        Cms::setData('table', $table);

        $this->template->setView(STOCK_NAME.'/views/admin/suppliers/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        Admin::creatForm('suppliers');

        $this->template->setView(STOCK_NAME.'/views/admin/suppliers/save', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        $object = \Stock\Model\Suppliers::find($id);

        if(have_posts($object))
        {
            Admin::creatForm('suppliers', $object);
        }

        $this->template->setView(STOCK_NAME.'/views/admin/suppliers/save', 'plugin');

        $this->template->render();
    }
}