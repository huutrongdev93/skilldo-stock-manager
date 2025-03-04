<?php
namespace Stock;

use Illuminate\Support\Collection;

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
            ],
            'onbackorder' => [
                'label' => trans('stock.status.onbackorder'),
                'color' => 'yellow',
            ],
        ];
        if(!empty($key) && !empty($type) && isset($status[$key])) {
            if(!empty($status[$key][$type])) return apply_filters('inventory_status_'.$type, $status[$key][$type], $key, $type);
            return apply_filters( 'inventory_status', $status[$key], $key, $type);
        }
        return apply_filters( 'inventory_status', $status, $key);
    }

    static function config($key = '')
    {
        $default = [
            'stockOrder' => 'one',
            'purchaseOrder' => 'success',
            'lackStock' => 'handmade'
        ];

        $config = \Option::get('inventoriesConfig', $default);

        if(!is_array($config)) {
            $config = $default;
        }

        foreach($default as $keyConfig => $value) {
            if (!isset($config[$keyConfig])) {
                $config[$keyConfig] = $value;
            }
        }

        return (!empty($key)) ? \Arr::get($config, $key) : $config;
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

class CashFlowHelper {

    static function partnerType(): Collection
    {
        return Collection::make([
            ['key' => 'S', 'name' => 'Nhà cung cấp'],
            ['key' => 'C', 'name' => 'Khách hàng'],
            ['key' => 'O', 'name' => 'Khác']
        ]);
    }
}