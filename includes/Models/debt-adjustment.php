<?php
namespace Stock\Model;
use Qr;
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
                    ->update(['code' => \Stock\Helper::code(\Stock\Prefix::adjustment->value, $object->id)]);
            }
        });
    }
}