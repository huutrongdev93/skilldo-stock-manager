<?php
namespace Ecommerce\Table;

//suppliers
use Admin;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnText;
use Url;

class AdminSuppliers extends \SkillDo\Table\SKDObjectTable {

    protected string $module = 'suppliers';

    protected mixed $model = \Stock\Model\Suppliers::class;

    function getColumns()
    {
        $this->_column_headers = [
            'cb' => 'cb',
            'code' => [
                'label' => 'Mã nhà cung cấp',
                'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
            ],
            'name' => [
                'label' => 'Tên nhà cung cấp',
                'column' => fn($item, $args) => ColumnText::make('name', $item, $args)
            ],
            'phone' => [
                'label' => 'Điện thoại',
                'column' => fn($item, $args) => ColumnText::make('phone', $item, $args)
            ],
            'email' => [
                'label' => 'Email',
                'column' => fn($item, $args) => ColumnText::make('email', $item, $args)
            ],
            'debt' => [
                'label' => 'Nợ cần trả hiện tại',
                'column' => fn($item, $args) => ColumnText::make('debt', $item, $args)->number()
            ],
            'total_invoiced' => [
                'label' => 'Tổng mua',
                'column' => fn($item, $args) => ColumnText::make('total_invoiced', $item, $args)->number()
            ],
            'action' => trans('table.action'),
        ];

        return apply_filters( "manage_suppliers_columns", $this->_column_headers );
    }

    function actionButton($item, $module, $table): array
    {
        $listButton = [];
        $listButton[] = Admin::button('blue', [
            'href' => Url::route('admin.suppliers.edit', ['id' => $item->id]),
            'icon' => Admin::icon('edit')
        ]);
        $listButton[] = Admin::btnDelete([
            'id' => $item->id,
            'model' => 'Suppliers',
            'description' => trans('message.page.confirmDelete', ['title' => html_escape($item->name)])
        ]);
        /**
         * @since 7.0.0
         */
        return apply_filters('admin_suppliers_table_columns_action', $listButton);
    }

    public function queryFilter(\Qr $query, Request $request): \Qr
    {
        $keyword = $request->input('keyword');

        if (!empty($keyword)) {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        return $query;
    }

    public function queryDisplay(\Qr $query, Request $request, $data = []): \Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query
            ->orderBy('order')
            ->orderBy('created', 'desc');

        return $query;
    }
}