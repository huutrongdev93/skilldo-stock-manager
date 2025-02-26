<?php
use SkillDo\Http\Request;

class InventoriesController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Stock\Table\Inventories();

        Cms::setData('table', $table);

        $this->template->setView(STOCK_NAME.'/views/admin/inventories/inventory', 'plugin');

        $this->template->render();
    }
}