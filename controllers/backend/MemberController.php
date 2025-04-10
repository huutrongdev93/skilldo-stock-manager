<?php

use Illuminate\Support\Collection;
use SkillDo\Http\Request;

class MemberController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $table = new \Skdepot\Table\Member();

        Cms::setData('table', $table);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/member/index', 'plugin');

        $this->template->render();
    }

    public function add(Request $request): void
    {
        Admin::creatForm('member');

        $this->template->setView(SKDEPOT_NAME.'/views/admin/member/save', 'plugin');

        $this->template->render();
    }

    public function edit(Request $request, $id): void
    {
        $object = \SkillDo\Model\User::where('isMember', 1)->whereKey($id)->first();

        Cms::setData('object', $object);

        Admin::creatForm('member', $object);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/member/save', 'plugin');

        $this->template->render();
    }
}