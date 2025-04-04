<?php
namespace Stock\Model;

use SkillDo\DB;

Class OrderReturn extends \SkillDo\Model\Model
{
    protected string $table = 'orders_returns';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(OrderReturn $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => \Stock\Helper::code(\Stock\Prefix::orderReturn->value, $object->id)]);
            }
        });
    }
}