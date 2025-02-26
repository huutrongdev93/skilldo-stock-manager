<?php
namespace Stock\Table;
use Branch;
use Qr;
use Admin;
use SkillDo\DB;
use SkillDo\Form\Form;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnImage;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;
use SkillDo\Http\Request;

class Inventories extends SKDObjectTable
{
    protected string $module = 'inventories';

    protected mixed $model = \Ecommerce\Model\Product::class;

    protected $tableChild;

    public function __construct($args = [])
    {
        parent::__construct($args);

        $this->tableChild = new InventoriesChild();
    }

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['product_image'] = [
            'label'  => trans(''),
            'column' => fn($item, $args) => ColumnImage::make('image', $item, $args)
                ->size(30)
                ->parentWidth(40)
        ];

        $this->_column_headers['code'] = [
            'label'  => trans('Mã hàng'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['product_name'] = [
            'label'  => trans('Tên hàng'),
            'column' => fn($item, $args) => ColumnText::make('title', $item, $args)
                ->description(function($item) {
                    return $item->attribute_label ?? '';
                }, ['class' => ['fw-bold', 'color-green']])
                ->parentWidth(500)
        ];

        $this->_column_headers['product_price'] = [
            'label'  => trans('Giá bán'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)->number()
        ];

        $this->_column_headers['stock'] = [
            'label'  => trans('Tồn kho'),
            'column' => fn($item, $args) => ColumnText::make('stock', $item, $args)->number()
        ];

        $this->_column_headers['reserved'] = [
            'label'  => trans('Khách đặt'),
            'column' => fn($item, $args) => ColumnText::make('reserved', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('stock_status', $item, $args)
                ->color(function (string $status) {
                    return \Stock\Status\Inventory::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Stock\Status\Inventory::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $listButton = [];

        if(empty($item->variations))
        {
            $listButton['purchaseOrder'] = Admin::button('blue', [
                'icon' => '<i class="fa-light fa-basket-shopping-plus"></i>',
                'tooltip' => 'Nhập hàng',
                'data-id' => $item->id,
                'data-branch-id' => $item->branch_id,
                'class' => 'js_btn_purchase_order'
            ]);

            $listButton['purchaseReturn'] = Admin::button('red', [
                'icon' => '<i class="fa-light fa-basket-shopping-minus"></i>',
                'tooltip' => 'Xuất hàng',
                'data-id' => $item->id,
                'data-branch-id' => $item->branch_id,
                'class' => 'js_btn_purchase_return'
            ]);

            $listButton['history'] = Admin::button('yellow', [
                'icon' => '<i class="fa-light fa-clock-rotate-left"></i>',
                'tooltip' => 'Lịch sử thay đổi',
                'data-id' => $item->id,
                'data-branch-id' => $item->branch_id,
                'class' => 'js_btn_inventories_history'
            ]);
        }
        
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
        $form->select2('status', \Stock\Status\Inventory::options()->pluck('label', 'value')
            ->prepend('Tất cả trạng thái', '')
            ->toArray(), [], request()->input('status'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $query->where('type', '<>', 'null');

        $keyword = trim($request->input('keyword'));

        if(!empty($keyword))
        {
            $query->where(function ($qr) use ($keyword) {
                $qr->where('products.title', 'like', '%'.$keyword.'%');
                $qr->orWhere('products.code', $keyword);
            });
        }

        $status = $request->input('status');

        if(!empty($status))
        {
            $query->where('stock_status', $status);
        }

        return $query;
    }

    public function queryDisplay(Qr $query, \SkillDo\Http\Request $request, $data = []): Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $branchId = (int)$request->input('branch');

        if($branchId == 0) $branchId = 1;

        $selected = [
            'products.id',
            'products.code',
            'products.title',
            'products.attribute_label',
            'products.image',
            'products.price',
            'products.price_sale',
            'products.stock_status',
            'products.hasVariation',
            'products.parent_id',
        ];

        $query
            ->select($selected)
            ->addSelect([
                'stock' => \SkillDo\DB::raw('IFNULL(SUM(cle_i.stock), 0) as stock'),
                'reserved' => \SkillDo\DB::raw('IFNULL(SUM(cle_i.reserved), 0) as reserved')
            ]);

        $query->leftJoin('inventories as i', function ($join) use ($branchId) {
            $join->on('i.product_id', '=', 'products.id')->orOn('i.parent_id', '=', 'products.id');
            $join->where('i.branch_id', $branchId);
        });

        $query
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc')
            ->groupBy($selected);

        return $query;
    }

    public function dataDisplay($objects)
    {
        $branchId = (int)request()->input('branch');

        if($branchId == 0) $branchId = 1;

        if($objects->count() == 1 && $objects->first()->hasVariation === 0)
        {
            foreach ($objects as $product)
            {
                if(!empty($product->attribute_label))
                {
                    $product->branch_id = $branchId;

                    $product->attribute_label = '<span style="font-weight: bold;">' . $product->attribute_label . '</span>';
                }
            }

            return $objects;
        }

        $productsId = $objects->filter(function ($item) {
            return $item->hasVariation === 1;
        })->pluck('id')->toArray();

        if(have_posts($productsId))
        {
            $selected = [
                'products.id',
                'products.code',
                'products.title',
                'products.attribute_label',
                'products.image',
                'products.price',
                'products.price_sale',
                'products.stock_status',
                'products.hasVariation',
                'products.parent_id',
            ];

            $products = \Ecommerce\Model\Variation::whereIn('products.parent_id', $productsId)
                ->select($selected)
                ->addSelect([
                    'stock' => \SkillDo\DB::raw('IFNULL(SUM(cle_i.stock), 0) as stock'),
                    'reserved' => \SkillDo\DB::raw('IFNULL(SUM(cle_i.reserved), 0) as reserved')
                ])
                ->leftJoin('inventories as i', function ($join) use ($branchId) {
                    $join->on('i.product_id', '=', 'products.id');
                    $join->where('i.branch_id', $branchId);
                })
                ->groupBy($selected)
                ->get();

            $variationsId = $products->pluck('id')->toArray();

            $objects = $objects->filter(function ($item) use ($variationsId) {
                return !in_array($item->id, $variationsId);
            });

            foreach ($products as $product)
            {
                if(!empty($product->attribute_label))
                {
                    $product->attribute_label = '<span style="font-weight: bold;">' . $product->attribute_label . '</span>';
                }
            }

            foreach ($objects as $object)
            {
                if(!empty($product->attribute_label))
                {
                    $product->attribute_label = '<span style="font-weight: bold;">' . $product->attribute_label . '</span>' . ' - ';
                }

                $object->variations = [];

                foreach ($products as $product)
                {
                    if($product->parent_id == $object->id)
                    {
                        $product->branch_id = $branchId;
                        $object->variations[] = $product->toObject();
                    }
                }
            }
        }

        foreach ($objects as $object)
        {
            $object->branch_id = $branchId;

            if(!empty($object->variations))
            {
                $object->code = '('.count($object->variations).') Mã hàng';
            }
        }

        return $objects;
    }
}

class InventoriesChild extends SKDObjectTable
{
    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['product_name'] = [
            'label'  => trans('Sản phẩm'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnView::make('product_child', $item, $args)
                ->attributesColumn(['colspan' => 9])
                ->html(function ($column)
                {
                    if(!empty($column->item->variations))
                    {
                        $table = new ProductVariation(['items' => $column->item->variations ?? []]);
                        echo '<div>';
                        $table->display();
                        echo '</div>';
                    }
//                    \Plugin::view(STOCK_NAME, 'admin/inventories/product-variations', [
//                        'variations' => $column->item->variations ?? []
//                    ]);
                })
        ];

        return $this->_column_headers;
    }
}

class ProductVariation extends SKDObjectTable
{
    protected bool $_class_tr_default = false;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['product_image'] = [
            'label'  => trans(''),
            'column' => fn($item, $args) => ColumnImage::make('image', $item, $args)
                ->size(30)
                ->parentWidth(40)
        ];

        $this->_column_headers['product_code'] = [
            'label'  => trans('Mã hàng'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['product_name'] = [
            'label'  => trans('Tên hàng'),
            'column' => fn($item, $args) => ColumnText::make('title', $item, $args)
                ->description(function($item) {
                    return $item->attribute_label ?? '';
                }, ['class' => ['fw-bold', 'color-green']])
                ->parentWidth(400)
        ];

        $this->_column_headers['product_price'] = [
            'label'  => trans('Giá bán'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)->number()
        ];

        $this->_column_headers['stock'] = [
            'label'  => trans('Tồn kho'),
            'column' => fn($item, $args) => ColumnText::make('stock', $item, $args)->number()
        ];

        $this->_column_headers['reserved'] = [
            'label'  => trans('Khách đặt'),
            'column' => fn($item, $args) => ColumnText::make('reserved', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnBadge::make('stock_status', $item, $args)
                ->color(function (string $status) {
                    return \Stock\Status\Inventory::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Stock\Status\Inventory::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $listButton = [];

        $listButton['purchaseOrder'] = Admin::button('blue', [
            'icon'    => '<i class="fa-light fa-basket-shopping-plus"></i>',
            'tooltip' => 'Nhập hàng',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'class' => 'js_btn_purchase_order'
        ]);

        $listButton['purchaseReturn'] = Admin::button('red', [
            'icon'    => '<i class="fa-light fa-basket-shopping-minus"></i>',
            'tooltip' => 'Xuất hàng',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'class' => 'js_btn_purchase_return'
        ]);

        $listButton['history'] = Admin::button('yellow', [
            'icon'    => '<i class="fa-light fa-clock-rotate-left"></i>',
            'tooltip' => 'Lịch sử thay đổi',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'class' => 'js_btn_inventories_history'
        ]);

        return $listButton;
    }
}