<?php
namespace Skdepot\Table;
use Qr;
use Admin;
use SkillDo\DB;
use SkillDo\Form\Form;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnImage;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;
use SkillDo\Http\Request;
use Skdepot\Status\Inventory as Status;
use Url;

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

        $this->_column_headers['price_cost'] = [
            'label'  => trans('Giá gốc'),
            'column' => fn($item, $args) => ColumnText::make('price_cost', $item, $args)->number()
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
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return Status::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return Status::tryFrom($status)->label();
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
            $listButton['edit'] = Admin::button('blue', [
                'icon'    => Admin::icon('edit'),
                'tooltip' => 'Cập nhật',
                'data-id' => $item->id,
                'data-bill' => htmlspecialchars(json_encode([
                    'code'       => $item->code,
                    'title'      => $item->title,
                    'price_cost' => $item->price_cost,
                    'stock'      => $item->stock,
                ])),
                'class'   => 'js_inventory_btn_edit'
            ]);
        }

        $listButton['purchaseOrder'] = Admin::button('green', [
            'icon' => '<i class="fa-light fa-basket-shopping-plus"></i>',
            'tooltip' => 'Nhập hàng',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'href' => Url::route('admin.purchase.orders.new').'?source=products',
            'class' => 'js_inventory_btn_purchase_order'
        ]);

        $listButton['purchaseReturn'] = Admin::button('red', [
            'icon' => '<i class="fa-light fa-basket-shopping-minus"></i>',
            'tooltip' => 'Xuất hàng',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'href' => Url::route('admin.purchase.returns.new').'?source=products',
            'class' => 'js_inventory_btn_purchase_return'
        ]);
        
        return apply_filters('admin_'.$this->module.'_table_columns_action', $listButton);
    }

    function headerFilter(Form $form, Request $request)
    {
        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        $form->text('keyword', ['placeholder' => trans('table.search.keyword').'...'], request()->input('keyword'));
        $form->select2('status', \Skdepot\Status\Inventory::options()->pluck('label', 'value')
            ->prepend('Tất cả trạng thái', '')
            ->toArray(), [], request()->input('status'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('reload');

        return $buttons;
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $branch = \Skdepot\Helper::getBranchCurrent();

        $status = request()->input('status');

        $keyword = request()->input('keyword');

        $selected = [
            'products.id',
            'products.code',
            'products.title',
            'products.attribute_label',
            'products.image',
            'products.price',
            'products.price_sale',
            'products.hasVariation',
            'products.parent_id',
        ];

        $p = 'cle_products';

        $pn = 'products';

        $i = 'cle_i';

        $i2 = 'cle_i2';

        $vs = 'cle_vs';

        $query
            ->select([
                ...$selected,
                DB::raw("CASE
                    WHEN $p.hasVariation = 0 THEN $i.stock
                    WHEN $p.hasVariation = 1 THEN $vs.variation_stock
                END AS stock"),
                DB::raw("CASE
                    WHEN $p.hasVariation = 0 THEN $i.reserved
                    WHEN $p.hasVariation = 1 THEN $vs.variation_reserved
                END AS reserved"),
                DB::raw("CASE
                    WHEN $p.hasVariation = 0 THEN $i.price_cost
                    WHEN $p.hasVariation = 1 THEN $vs.variation_price_cost
                END AS price_cost"),

                DB::raw("CASE 
                    WHEN $p.hasVariation = 0 THEN $i.status 
                    WHEN $p.hasVariation = 1 THEN IF($vs.has_instock = 1, 'instock', 'outstock') 
                END as status")
            ]);

        $query->leftJoin('inventories as i', function ($join) use ($pn) {
            $join->on("$pn.id", '=', 'i.product_id')
                ->where($pn.'.hasVariation', 0)
                ->where('i.branch_id', 1);
        });

        // Subquery để tính toán các giá trị tổng hợp cho variations
        $subQuery = DB::table('products as p2')
            ->leftJoin('inventories as i2', function ($join) use ($branch) {
                $join->on('p2.id', '=', 'i2.product_id')
                    ->where('i2.branch_id', $branch->id);
            })
            ->select([
                'p2.parent_id',
                DB::raw("COALESCE(SUM($i2.stock), 0) as variation_stock"),
                DB::raw("COALESCE(SUM($i2.reserved), 0) as variation_reserved"),
                DB::raw("COALESCE(AVG($i2.price_cost), 0) as variation_price_cost"),
                DB::raw("MAX(CASE WHEN $i2.status = 'instock' THEN 1 ELSE 0 END) as has_instock")
            ])
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($q2) use ($keyword) {
                    $q2->where('p2.code', 'like', "%{$keyword}%")
                        ->orWhere('p2.title', 'like', "%{$keyword}%");
                });
            })
            ->where('p2.type', 'variations')
            ->groupBy('p2.parent_id');

        if($status == \Skdepot\Status\Inventory::out->value)
        {
            $subQuery->where('i2.status', $status);
        }

        $query->leftJoinSub($subQuery, 'vs', // Alias cho subquery
            function ($join) use ($pn) {
                $join->on("$pn.id", '=', 'vs.parent_id')->where("$pn.hasVariation", 1);
            }
        );

        $query->where("$pn.type", 'product');

        if($status == \Skdepot\Status\Inventory::in->value)
        {
            $query->where(function ($q) use ($status, $pn) {
                $q->where("$pn.hasVariation", 0)
                    ->where('i.status', $status)
                    ->orWhere(function ($q2) use ($pn) {
                        $q2->where("$pn.hasVariation", 1)
                            ->where("vs.has_instock", 1);
                    });
            });
        }
        else if($status == \Skdepot\Status\Inventory::out->value)
        {
            $query->where(function ($q) use ($status, $pn) {
                $q->where("$pn.hasVariation", 0)
                    ->where('i.status', $status)
                    ->orWhere(function ($q2) use ($pn) {
                        $q2->where("$pn.hasVariation", 1)
                            ->where(function ($q3) {
                                $q3->where('vs.has_instock', 0)
                                    ->orWhereNull('vs.has_instock');
                            });
                    });
            });
        }

        if (!empty($keyword)) {
            $query->where(function ($q) use ($pn, $keyword) {
                $q->where("$pn.hasVariation", 0)
                    ->where(function ($q2) use ($pn, $keyword) {
                        $q2->where("$pn.code", 'like', "%{$keyword}%")
                            ->orWhere("$pn.title", 'like', "%{$keyword}%");
                    })
                    ->orWhere(function ($q2) use ($pn, $keyword) {
                        $q2->where("$pn.hasVariation", 1)
                            ->whereExists(function ($q3) use ($pn, $keyword) {
                                $q3->select(DB::raw(1))
                                    ->from('products as p2')
                                    ->whereColumn('p2.parent_id', "$pn.id")
                                    ->where('p2.type', 'variations')
                                    ->where(function ($q4) use ($keyword) {
                                        $q4->where('p2.code', 'like', "%{$keyword}%")
                                            ->orWhere('p2.title', 'like', "%{$keyword}%");
                                    });
                            });
                    });
            });
        }

        return $query;
    }

    public function queryDisplay(Qr $query, \SkillDo\Http\Request $request, $data = []): Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc');

        return $query;
    }

    public function dataDisplay($objects)
    {
        $branch = \Skdepot\Helper::getBranchCurrent();

        if($objects->count() == 1 && $objects->first()->hasVariation === 0)
        {
            foreach ($objects as $product)
            {
                if(!empty($product->attribute_label))
                {
                    $product->branch_id = $branch->id;

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
                'products.hasVariation',
                'products.parent_id',
                'i.status',
                'i.price_cost',
                'i.stock',
                'i.reserved'
            ];

            $products = \Ecommerce\Model\Variation::whereIn('products.parent_id', $productsId)
                ->select($selected)
                ->leftJoin('inventories as i', function ($join) use ($branch) {
                    $join->on('i.product_id', '=', 'products.id');
                    $join->where('i.branch_id', $branch->id);
                })
                ->groupBy($selected);


            $status = request()->input('status');

            if(!empty($status))
            {
                $products->where('i.status', $status);
            }

            $keyword = trim(request()->input('keyword'));

            if(!empty($keyword))
            {
                $products->where(function ($qr) use ($keyword) {
                    $qr->where('products.title', 'like', '%'.$keyword.'%');
                    $qr->orWhere('products.code', $keyword);
                });
            }

            $products = $products->get();

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
                    $product->attribute_label = '<span style="font-weight: bold;">' . $product->attribute_label . '</span>';
                }

                $object->variations = [];

                foreach ($products as $product)
                {
                    if($product->parent_id == $object->id)
                    {
                        $product->branch_id   = $branch->id;
                        $object->variations[] = $product->toObject();
                    }
                }
            }
        }

        $objects->transform(function ($item) use ($branch) {
            // Kiểm tra nếu child là mảng và chỉ có 1 phần tử
            if (!empty($item->variations)) {
                // Thay thế item cha bằng item con duy nhất
                if(count($item->variations) === 1)
                {
                    return $item->variations[0];
                }

                $item->branch_id = $branch->id;

                $item->code = '('.count($item->variations).') Mã hàng';
            }
            // Giữ nguyên nếu không thỏa mãn điều kiện
            return $item;
        });

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
                ->attributesColumn(['colspan' => 10])
                ->html(function ($column)
                {
                    if(!empty($column->item->variations))
                    {
                        $table = new ProductVariation(['items' => $column->item->variations ?? []]);
                        echo '<div>';
                        $table->display();
                        echo '</div>';
                    }
//                    \Plugin::view(SKDEPOT_NAME, 'admin/inventories/product-variations', [
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

        $this->_column_headers['price_cost'] = [
            'label'  => trans('Giá gốc'),
            'column' => fn($item, $args) => ColumnText::make('price_cost', $item, $args)->number()
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
            'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return Status::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return Status::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $listButton = [];

        $listButton['edit'] = Admin::button('blue', [
            'icon' => Admin::icon('edit'),
            'tooltip' => 'Cập nhật',
            'data-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode([
                'code'       => $item->code,
                'title'      => $item->title,
                'price_cost' => $item->price_cost,
                'stock'      => $item->stock,
            ])),
            'class' => 'js_inventory_btn_edit'
        ]);

        $listButton['purchaseOrder'] = Admin::button('green', [
            'icon' => '<i class="fa-light fa-basket-shopping-plus"></i>',
            'tooltip' => 'Nhập hàng',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'href' => Url::route('admin.purchase.orders.new').'?source=products',
            'class' => 'js_inventory_btn_purchase_order'
        ]);

        $listButton['purchaseReturn'] = Admin::button('red', [
            'icon' => '<i class="fa-light fa-basket-shopping-minus"></i>',
            'tooltip' => 'Xuất hàng',
            'data-id' => $item->id,
            'data-branch-id' => $item->branch_id,
            'href' => Url::route('admin.purchase.returns.new').'?source=products',
            'class' => 'js_inventory_btn_purchase_return'
        ]);

        return $listButton;
    }
}