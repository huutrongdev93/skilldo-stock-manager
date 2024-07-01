<?php
use SkillDo\Form\Form;
use SkillDo\Table\SKDObjectTable;
use SkillDo\Http\Request;

class AdminInventoriesTable extends SKDObjectTable {

    function get_columns() {
        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['product_name'] = [
            'label'  => trans('Sản phẩm'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('product_name', $item, $args)
                ->description(function($item) {
                    return $item->optionName;
                }, ['class' => ['fw-bold', 'color-green']])
                ->parentWidth(500)
        ];

        $this->_column_headers['product_code'] = [
            'label'  => trans('SKU'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('product_code', $item, $args)
        ];

        $this->_column_headers['branch_name'] = [
            'label'  => trans('Kho hàng'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('branch_name', $item, $args)
        ];

        $this->_column_headers['stock'] = [
            'label'  => trans('Tồn kho'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('stock', $item, $args)->number()
        ];

        $this->_column_headers['reserved'] = [
            'label'  => trans('Khách đặt'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('reserved', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return InventoryHelper::status($status,'color');
                })
                ->label(function (string $status) {
                    return InventoryHelper::status($status,'label');
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return apply_filters( "manage_".$this->module."_columns", $this->_column_headers );
    }

    function column_default($column_name, $item, $global): void
    {
        do_action('manage_'.$this->module.'_custom_column', $column_name, $item, $global);
    }
	
    function actionButton($item, $module, $table): array
    {
        $listButton = [];

        $listButton['purchaseOrder'] = Admin::button('blue', [
            'icon'    => '<i class="fa-light fa-basket-shopping-plus"></i>',
            'tooltip' => 'Nhập hàng',
            'data-id' => $item->id,
            'data-product-id' => $item->product_id,
            'class' => 'js_btn_purchase_order'
        ]);

        $listButton['purchaseReturn'] = Admin::button('red', [
            'icon'    => '<i class="fa-light fa-basket-shopping-minus"></i>',
            'tooltip' => 'Xuất hàng',
            'data-id' => $item->id,
            'data-product-id' => $item->product_id,
            'class' => 'js_btn_purchase_return'
        ]);

        $listButton['history'] = Admin::button('yellow', [
            'icon'    => '<i class="fa-light fa-clock-rotate-left"></i>',
            'tooltip' => 'Lịch sử thay đổi',
            'data-id' => $item->id,
            'class' => 'js_btn_inventories_history'
        ]);
        
        return apply_filters('admin_'.$this->module.'_table_columns_action', $listButton);
    }

    function headerFilter(Form $form, Request $request)
    {
        $branch = Branch::gets();

        $branchOptions = [];

        foreach ($branch as $item) {
            $branchOptions[$item->id] = $item->name;
        }

        $form->select2('branch', $branchOptions, [], request()->input('branch'));

        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        $form->text('keyword', ['placeholder' => trans('table.search.keyword').'...'], request()->input('keyword'));
        $form->select2('status', [
            '' => trans('Tất cả trạng thái'),
            'instock' => trans('Còn hàng'),
            'outstock' => trans('Hết hàng'),
        ], [], request()->input('status'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }
}