<?php

namespace Stock\Model;

use Qr;
use SkillDo\DB;

class CashFlow extends \Skilldo\Model\Model
{
    protected string $table = 'cash_flow';

    protected string $primaryKey = 'id';

    protected bool $widthChildren = false;

    public function setWidthChildren($widthChildren = false): static
    {
        $this->widthChildren = $widthChildren;
        return $this;
    }

    static function widthChildren(?Qr $query = null): static
    {
        $model = new static;

        if($query instanceof Qr)
        {
            $model->setQuery($query);
        }

        return $model->setWidthChildren(true);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::setQueryBuilding(function (CashFlow $object, Qr $query)
        {
            if(!$object->widthChildren)
            {
                if(!$query->isWhere($object->getTable().'.parent_id') && !$query->isWhere('parent_id'))
                {
                    $query->where($object->getTable().'.parent_id', 0);
                }
            }

            $object->setWidthChildren();
        });

        static::saved(function(CashFlow $object, $action)
        {
            if($action == 'add' && empty($object->code))
            {
                $code = \Stock\Helper::code((($object->amount < 0) ? 'PC' : 'PT'), $object->id);

                if($object->target_type == 'Order')
                {
                    $code = \Stock\Helper::code('TTDH', $object->id);
                }
                if($object->target_type == \Stock\Prefix::purchaseOrder->value)
                {
                    $code = \Stock\Helper::code('TT'.\Stock\Prefix::purchaseOrder->value, $object->id);
                }

                if($object->target_type == \Stock\Prefix::purchaseReturn->value)
                {
                    $code = \Stock\Helper::code('PT'.\Stock\Prefix::purchaseReturn->value, $object->id);
                }

                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->where('code', '')
                    ->update(['code' => $code]);
            }
        });
    }
}