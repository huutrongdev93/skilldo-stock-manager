<?php

namespace Stock\Model;

use SkillDo\DB;

class CashFlow extends \Skilldo\Model\Model
{
    protected string $table = 'cash_flow';

    protected string $primaryKey = 'id';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(CashFlow $object, $action)
        {
            if($action == 'add' && empty($object->code))
            {
                $code = \Stock\Helper::code((($object->amount < 0) ? 'PC' : 'PT'), $object->id);

                if($object->target_type == 'Order')
                {
                    $code = \Stock\Helper::code('TTDH', $object->id);
                }
                if($object->target_type == 'PNH')
                {
                    $code = \Stock\Helper::code('TTPN', $object->id);
                }

                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => $code]);
            }
        });
    }
}