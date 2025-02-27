<?php
namespace Stock\Model;

use SkillDo\DB;

Class StockTake extends \SkillDo\Model\Model
{
    protected string $table = 'stock_takes';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(StockTake $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => \Stock\Helper::code('KK', $object->id)]);
            }
        });
    }
}