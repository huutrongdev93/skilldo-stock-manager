<?php
namespace Skdepot;

use Branch;
use Illuminate\Support\Collection;
use SkillDo\DB;

class Helper {

    static function code($prefix, $id): string
    {
        $code = str_pad($id, 6, '0', STR_PAD_LEFT);

        return $prefix . $code;
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
        $website = \Skdepot\Config::get('website');

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
            $branches = \Skdepot\Helper::getBranchAll();
        }

        $inventoriesAdd = [];

        foreach ($products as $product) {

            if($product->hasVariation === 1 && $product->type === 'product')
            {
                continue;
            }

            foreach ($branches as $branch)
            {
                $inventory = \Skdepot\Model\Inventory::where('product_id', $product->id)
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
                    'status'        => \Skdepot\Status\Inventory::out->value,
                    'stock'         => 0,
                    'branch_id'     => $branch->id,
                    'branch_name'   => $branch->name,
                ];

                if(count($inventoriesAdd) >= 500)
                {
                    \Skdepot\Model\Inventory::inserts($inventoriesAdd);

                    $inventoriesAdd = [];
                }
            }
        }

        if(!empty($inventoriesAdd))
        {
            \Skdepot\Model\Inventory::inserts($inventoriesAdd);
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

class ReportColumns
{
    static function salesTime(): array
    {
        return [
            'time' => [
                'label' => 'Thời gian',
            ],
            'numOrder' => [
                'label' => 'Đơn Hàng',
            ],
            'price' => [
                'label' => 'Tiền hàng',
                'calculator' => 'Tiền hàng = Số lượng đặt hàng * giá bán'
            ],
            'discount' => [
                'label' => 'Giảm giá',
            ],
            'priceReturn' => [
                'label' => 'Tiền trả lại',
            ],
            'revenue' => [
                'label' => 'Doanh thu thuần',
                'calculator' => 'Doanh thu thuần = Tiền hàng - Giảm giá - Tiền hàng trả lại'
            ],
            'shipping' => [
                'label' => 'Phí giao hàng',
            ],
            'revenueTotal' => [
                'label' => 'Tổng doanh thu',
                'calculator' => 'Tổng doanh thu = Doanh thu thuần + phí giao hàng + tiền thuế'
            ],
            'grossProfit' => [
                'label' => 'Lợi nhuận gộp',
                'class' => 'fw-bold',
                'calculator' => 'Lợi nhuận gộp = Doanh thu thuần có ghi nhận giá vốn - Tiền vốn'
            ],
        ];
    }

    static function salesProduct(): array
    {
        return [
            'code' => [
                'label' => 'Mã sản phẩm',
            ],
            'name' => [
                'label' => 'Tên sản phẩm',
            ],
            'quantity' => [
                'label' => 'Số lượng',
            ],
            'subtotal' => [
                'label' => 'Tiền hàng',
                'calculator' => 'Tiền hàng = Số lượng đặt hàng * giá bán'
            ],
            'discount' => [
                'label' => 'Giảm giá',
            ],
            'priceReturn' => [
                'label' => 'Tiền trả lại',
            ],
            'revenue' => [
                'label' => 'Doanh thu thuần',
                'calculator' => 'Doanh thu thuần = Tiền hàng - Giảm giá - Tiền hàng trả lại'
            ],
            'costTotal' => [
                'label' => 'Tiên vốn',
                'calculator' => 'Giá vốn * Số lượng thực'
            ],
            'grossProfit' => [
                'label' => 'Lợi nhuận gộp',
                'class' => 'fw-bold',
                'calculator' => 'Lợi nhuận gộp = Doanh thu thuần có ghi nhận giá vốn - Tiền vốn'
            ],
        ];
    }

    static function salesBranch(): array
    {
        return [
            'name' => [
                'label' => 'Tên chi nhánh',
            ],
            'quantity' => [
                'label' => 'SL đơn hàng',
            ],
            'subtotal' => [
                'label' => 'Tiền hàng',
                'calculator' => 'Tiền hàng = Số lượng đặt hàng * giá bán'
            ],
            'discount' => [
                'label' => 'Giảm giá',
            ],
            'priceReturn' => [
                'label' => 'Tiền trả lại',
            ],
            'revenue' => [
                'label' => 'Doanh thu thuần',
                'calculator' => 'Doanh thu thuần = Tiền hàng - Giảm giá - Tiền hàng trả lại'
            ],
            'shipping' => [
                'label' => 'Phí giao hàng',
            ],
            'revenueTotal' => [
                'label' => 'Tổng doanh thu',
                'calculator' => 'Tổng doanh thu = Doanh thu thuần + phí giao hàng + tiền thuế'
            ],
            'grossProfit' => [
                'label' => 'Lợi nhuận gộp',
                'class' => 'fw-bold',
                'calculator' => 'Lợi nhuận gộp = Doanh thu thuần có ghi nhận giá vốn - Tiền vốn'
            ],
        ];
    }

    static function salesCustomer(): array
    {
        return [
            'name' => [
                'label' => 'Tên khách hàng',
            ],
            'email' => [
                'label' => 'Email',
            ],
            'phone' => [
                'label' => 'Số điện thoại',
            ],
            'quantity' => [
                'label' => 'Số lượng',
            ],
            'subtotal' => [
                'label' => 'Tiền hàng',
                'calculator' => 'Tiền hàng = Số lượng đặt hàng * giá bán'
            ],
            'discount' => [
                'label' => 'Giảm giá',
            ],
            'priceReturn' => [
                'label' => 'Tiền trả lại',
            ],
            'revenue' => [
                'label' => 'Doanh thu thuần',
                'calculator' => 'Doanh thu thuần = Tiền hàng - Giảm giá - Tiền hàng trả lại'
            ],
            'costTotal' => [
                'label' => 'Tiên vốn',
                'calculator' => 'Giá vốn * Số lượng thực'
            ],
            'grossProfit' => [
                'label' => 'Lợi nhuận gộp',
                'class' => 'fw-bold',
                'calculator' => 'Lợi nhuận gộp = Doanh thu thuần có ghi nhận giá vốn - Tiền vốn'
            ],
        ];
    }

    static function financial(): array
    {
        return [
            'subtotal' => [
                'label' => 'Doanh thu bán hàng (1)',
            ],
            'deductionRevenue' => [
                'label' => 'Giảm trừ Doanh thu (2 = 2.1+2.2)',
                'child' => [
                    'discount' => ['label' => 'Chiết khấu (Giảm giá) (2.1)'],
                    'priceReturn' => ['label' => 'Giá trị hàng bị trả lại (2.2)']
                ]
            ],
            'revenue' => [
                'label' => 'Doanh thu thuần (3=1-2)',
            ],
            'cost' => [
                'label' => 'Giá vốn bán hàng (4)',
            ],
            'expenses' => [
                'label' => 'Chi phí (5=5.1+5.2)',
                'child' => [
                    'purchaseOrder' => ['label' => 'Chi phí nhập hàng (5.1)'],
                    'damageItem' => ['label' => 'Chi phí xuất hủy hàng (5.2)']
                ]
            ],
            'income' => [
                'label' => 'Thu nhập khác (6=6.1+6.2)',
                'child' => [
                    'shipping' => ['label' => 'Phí vận chuyển (6.1)'],
                    'surchargeReturn' => ['label' => 'Phí trả hàng (6.2)']
                ]
            ],
            'profit' => [
                'label' => 'Lợi nhuận (7=3-4-5+6)',
                'valueClass' => 'fw-bold text-danger'
            ],
        ];
    }

    static function inventorySupplier(): array
    {
        return [
            'code' => [
                'label' => 'Mã nhà cung cấp',
            ],
            'name' => [
                'label' => 'Tên nhà cung cấp',
            ],
            'quantity' => [
                'label' => 'SL nhập hàng',
            ],
            'subtotal' => [
                'label' => 'Giá trị nhập',
            ],
            'returnQuantity' => [
                'label' => 'SL trả hàng',
            ],
            'returnSubtotal' => [
                'label' => 'Giá trị trả',
            ],
            'netValue' => [
                'label' => 'Giá trị thuần',
            ],
        ];
    }

    static function inventorySupplierChild(): array
    {
        return [
            'code' => [
                'label' => 'Mã phiếu',
            ],
            'date' => [
                'label' => 'Thời gian',
            ],
            'type' => [
                'label' => 'Loại phiếu',
            ],
            'quantity' => [
                'label' => 'SL sản phẩm',
            ],
            'subtotal' => [
                'label' => 'Giá trị',
            ],
        ];
    }

    static function inventoryProduct(): array
    {
        return [
            'code' => [
                'label' => 'Mã sản phẩm',
            ],
            'name' => [
                'label' => 'Tên sản phẩm',
            ],
            'quantity' => [
                'label' => 'SL nhập hàng',
            ],
            'subtotal' => [
                'label' => 'Giá trị nhập',
            ],
            'returnQuantity' => [
                'label' => 'SL trả hàng',
            ],
            'returnSubtotal' => [
                'label' => 'Giá trị trả',
            ],
            'netValue' => [
                'label' => 'Giá trị thuần',
            ],
        ];
    }
}