{!!
    Admin::partial('components/page-default/page-save', [
        'module'  => 'member',
        'ajax' => (!empty($object)) ? 'SkdepotMemberAdminAjax::save': 'SkdepotMemberAdminAjax::add',
        'object' => $object ?? null,
    ]);
!!}