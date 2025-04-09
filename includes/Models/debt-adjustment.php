<?php
namespace Skdepot\Model;
use SkillDo\DB;
use SkillDo\Model\Model;

Class DebtAdjustment extends Model {

    protected string $table = 'debt_adjustment';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(DebtAdjustment $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->update(['code' => \Skdepot\Helper::code(\Skdepot\Prefix::adjustment->value, $object->id)]);
            }
        });
    }
}