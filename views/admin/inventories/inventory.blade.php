{!! Admin::partial('components/page-default/page-index', [
    'name'      => trans('inventories.title'),
    'table'     => $table,
]) !!}
{!! Plugin::partial(SKDEPOT_NAME, 'admin/inventories/modal'); !!}