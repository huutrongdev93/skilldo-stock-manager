<?php
namespace Stock\Model;

use SkillDo\DB;

Class PurchaseOrder extends \SkillDo\Model\Model
{
    protected string $table = 'inventories_purchase_orders';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(PurchaseOrder $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => \Stock\Helper::code('PN', $object->id)]);
            }
        });
    }
}