<?php
namespace Skdepot\Model;

use SkillDo\DB;

Class DamageItem extends \SkillDo\Model\Model
{
    protected string $table = 'skdepot_damage_items';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(DamageItem $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => \Skdepot\Helper::code('XH', $object->id)]);
            }
        });
    }
}