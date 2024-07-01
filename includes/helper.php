<?php
class InventoryHelper {
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

        $config = Option::get('inventoriesConfig', $default);

        if(!is_array($config)) {
            $config = $default;
        }

        foreach($default as $keyConfig => $value) {
            if (!isset($config[$keyConfig])) {
                $config[$keyConfig] = $value;
            }
        }

        return (!empty($key)) ? Arr::get($config, $key) : $config;
    }
}