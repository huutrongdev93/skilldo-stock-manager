<?php

namespace Stock\Model;

use Qr;
use SkillDo\DB;

class Transfer extends \Skilldo\Model\Model
{
    protected string $table = 'transfers';

    protected string $primaryKey = 'id';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(Transfer $object, $action)
        {
            if($action == 'add' && empty($object->code))
            {
                $code = \Stock\Helper::code(\Stock\Prefix::transfer->value, $object->id);

                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => $code]);
            }
        });
    }
}