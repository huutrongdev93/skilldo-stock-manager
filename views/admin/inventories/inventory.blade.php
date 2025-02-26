{!! Admin::partial('components/page-default/page-index', [
    'name'      => trans('inventories.title'),
    'table'     => $table,
]) !!}
{!! Plugin::partial(STOCK_NAME, 'admin/inventories/modal'); !!}