<?php
namespace Stock;

use Arr;
use Option;
use SkillDo\Cache;

class Config
{
    static function all()
    {
        $cacheId = 'stock_config';

        $config = Cache::get($cacheId);

        if(empty($config))
        {
            $default = [
                'website'       => 0, // Chi nhánh xử lý ngoài website
                'stockOrder'    => 'one',
                'purchaseOrder' => 'success',
                'lackStock'     => 'handmade'
            ];

            $config = Option::get('inventoriesConfig', []);

            if(!is_array($config))
            {
                $config = [];
            }

            foreach ($default as $key => $value)
            {
                $config[$key] = $config[$key] ?? $value;

                if(is_array($value) && is_string($config[$key]))
                {
                    $config[$key] = $value;
                }
            }

            Cache::save($cacheId, $config);
        }

        return $config;
    }

    static function get(string $keyword)
    {
        $config = static::all();

        return Arr::get($config, $keyword);
    }

    static function update($key, $value): void
    {
        $config = static::all();

        if(!Arr::has($config, $key))
        {
            $config = Arr::add($config, $key, $value);
        }
        else
        {
            $config = Arr::set($config, $key, $value);
        }

        self::save($config);
    }

    static function save($config = []): void
    {
        Option::update('inventoriesConfig', $config);

        Cache::delete('stock_config');
    }
}