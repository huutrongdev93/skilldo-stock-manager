<?php
namespace Skdepot\Model;

use SkillDo\DB;

Class PurchaseOrder extends \SkillDo\Model\Model
{
    protected string $table = 'skdepot_purchase_orders';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(PurchaseOrder $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => \Skdepot\Helper::code(\Skdepot\Prefix::purchaseOrder->value, $object->id)]);
            }
        });
    }
}