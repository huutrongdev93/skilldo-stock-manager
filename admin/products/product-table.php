<?php
use Illuminate\Database\Capsule\Manager as DB;
use SkillDo\Form\Form;

class AdminStockProductTable {

    static function customDataDisplay($objects)
    {
        $branch = \Stock\Helper::getBranchCurrent();

        $productsId = $objects->pluck('id')->toArray();

        $inventories = \Stock\Model\Inventory::whereIn('product_id', $productsId)
            ->where('branch_id', $branch->id)
            ->select('product_id', 'status')
            ->get();

        foreach ($objects as $object)
        {
            $object->stock_status = \Stock\Status\Inventory::out->value;

            foreach ($inventories as $inventory)
            {
                if(($inventory->product_id == $object->id || $inventory->parent_id == $object->id) && $inventory->status == \Stock\Status\Inventory::in->value)
                {
                    $object->stock_status = \Stock\Status\Inventory::in->value;
                    break;
                }
            }
        }

        return $objects;
    }

    static function productTableHeader($column): array
    {
        $newColumn = [];

        foreach ($column as $key => $col) {

            if($key == 'order') {
                $newColumn['stock'] = [
                    'label' => 'Tồn kho',
                    'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnBadge::make('stock_status', $item, $args)
                        ->color(function (string $status) {
                            return \Stock\Status\Inventory::tryFrom($status)->badge();
                        })
                        ->label(function (string $status) {
                            return \Stock\Status\Inventory::tryFrom($status)->label().' <i class="fa-thin fa-pen"></i>';
                        })
                        ->attributes(fn ($item): array => [
                            'data-id' => $item->id,
                        ])
                        ->class(['js_product_quick_edit_stock'])
                ];
            }

            $newColumn[$key] = $col;
        }

        return $newColumn;
    }
}

add_filter('admin_product_controllers_index_object', 'AdminStockProductTable::customDataDisplay');
add_filter('manage_product_columns', 'AdminStockProductTable::productTableHeader');
