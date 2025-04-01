<?php
namespace Stock;

use Branch;
use Ecommerce\Model\Variation;
use Illuminate\Support\Collection;
use SkillDo\DB;
use Stock\Model\Inventory;

class Helper {

    static function code($prefix, $id): string
    {
        $code = str_pad($id, 6, '0', STR_PAD_LEFT);

        return $prefix . $code;
    }

    static function status($key = '', $type = '') {
        $status = [
            'instock' => [
                'label' => trans('stock.status.instock'),
                'color' => 'green',
            ],
            'outstock' => [
                'label' => trans('stock.status.outstock'),
                'color' => 'red',
            ]
        ];
        if(!empty($key) && !empty($type) && isset($status[$key])) {
            if(!empty($status[$key][$type])) return apply_filters('inventory_status_'.$type, $status[$key][$type], $key, $type);
            return apply_filters( 'inventory_status', $status[$key], $key, $type);
        }
        return apply_filters( 'inventory_status', $status, $key);
    }

    static function getBranchAll()
    {
        return \SkillDo\Cache::remember('branch_all', TIME_CACHE, function () {
            return Branch::all();
        });
    }

    /**
     * Lấy chi nhánh của user đang đăng nhập
     */
    static function getBranchCurrent(): array|\SkillDo\Model\Model
    {
        $branch = [];

        $branchId = \Auth::user()->branch_id;

        if(!empty($branchId))
        {
            $cacheId = 'branch_user_'.\Auth::id();

            $branch = \SkillDo\Cache::remember($cacheId, TIME_CACHE, function () use ($branchId) {
                return Branch::whereKey($branchId)->first();
            });
        }

        if(empty($branch))
        {
            $branch = Branch::where('isDefault', 1)->first();
        }

        if(empty($branch))
        {
            $branch = Branch::get();

            if(!have_posts($branch))
            {
                response()->error('Không tìm thấy chi nhánh');
            }

            $branch->isDefault = 1;

            $branch->save();
        }

        return $branch;
    }

    /**
     * Lấy chi nhánh của website
     */
    static function getBranchWebsite(): array|\SkillDo\Model\Model
    {
        $website = \Stock\Config::get('website');

        $branch = [];

        if(!empty($website))
        {
            $branch = Branch::whereKey($website)->first();
        }

        if(!have_posts($website) || empty($website))
        {
            $branch = Branch::where('isDefault', 1)->first();
        }

        if(!have_posts($branch))
        {
            $branch = Branch::get();
        }

        return $branch;
    }

    /**
     * Tạo danh sách kho hàng cho toàn bộ sản phẩm
     */
    static function createInventories($branchId = 0): void
    {
        $products = \Ecommerce\Model\Product::widthVariation()->get();

        if(!empty($branchId))
        {
            $branches = Branch::whereKey($branchId)->get();
        }
        else
        {
            $branches = \Stock\Helper::getBranchAll();
        }

        $inventoriesAdd = [];

        foreach ($products as $product) {

            if($product->hasVariation === 1 && $product->type === 'product')
            {
                continue;
            }

            foreach ($branches as $branch)
            {
                $inventory = Inventory::where('product_id', $product->id)
                    ->where('branch_id', $branch->id)
                    ->count();

                if($inventory !== 0)
                {
                    continue;
                }

                $inventoriesAdd[] = [
                    'product_name'  => $product->title,
                    'product_code'  => $product->code,
                    'product_id'    => $product->id,
                    'parent_id'     => $product->parent_id,
                    'price_cost'    => 0,
                    'status'        => \Stock\Status\Inventory::out->value,
                    'stock'         => 0,
                    'branch_id'     => $branch->id,
                    'branch_name'   => $branch->name,
                ];

                if(count($inventoriesAdd) >= 500)
                {
                    DB::table('inventories')->insert($inventoriesAdd);

                    $inventoriesAdd = [];
                }
            }
        }

        if(!empty($inventoriesAdd))
        {
            DB::table('inventories')->insert($inventoriesAdd);
        }
    }


    static function icon(string $key): string
    {
        return match ($key) {
            'purchaseOrder' => '<i class="fa-duotone fa-solid fa-cart-flatbed-boxes"></i>',
            'purchaseReturn' => '<i class="fa-duotone fa-solid fa-inbox-out"></i>',
            'damageItems' => '<i class="fa-duotone fa-solid fa-hand-holding-box"></i>',
            'inventory' => '<i class="fa-duotone fa-solid fa-cubes"></i>',
            'stockTake' => '<i class="fa-duotone fa-clipboard-check icon-item"></i>',
            default => $key,
        };
    }
}

class CashFlowHelper
{
    static function partnerType(): Collection
    {
        return Collection::make([
            ['key' => 'S', 'name' => 'Nhà cung cấp'],
            ['key' => 'C', 'name' => 'Khách hàng'],
            ['key' => 'O', 'name' => 'Khác']
        ]);
    }
}