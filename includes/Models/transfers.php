<?php

namespace Skdepot\Model;

use Qr;
use SkillDo\DB;

class Transfer extends \Skilldo\Model\Model
{
    protected string $table = 'skdepot_transfers';

    protected string $primaryKey = 'id';

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(Transfer $object, $action)
        {
            if($action == 'add' && empty($object->code))
            {
                $code = \Skdepot\Helper::code(\Skdepot\Prefix::transfer->value, $object->id);

                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => $code]);
            }
        });
    }
}