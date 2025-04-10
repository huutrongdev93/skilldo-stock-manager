<?php
namespace Skdepot\Model;
use Qr;
use SkillDo\DB;
use SkillDo\Model\Model;

Class Suppliers extends Model {

    protected string $table = 'suppliers';

    protected array $columns = [
        'name'          => ['string'],
        'code'          => ['string'],
        'firstname'     => ['string'],
        'lastname'      => ['string'],
        'email'         => ['string'],
        'phone'         => ['string'],
        'address'       => ['string'],
        'image'         => ['image'],
        'status'        => ['string', 'use'],
        'total_invoiced' => ['int', 0],
        'debt' => ['int', 0],
    ];

    protected bool $widthStop = false;

    public function setWidthStop($widthStop = false): static
    {
        $this->widthStop = $widthStop;
        return $this;
    }

    static function widthStop(?Qr $query = null): static
    {
        $model = new static;

        if($query instanceof Qr)
        {
            $model->setQuery($query);
        }

        return $model->setWidthStop(true);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::setQueryBuilding(function (Suppliers $object, Qr $query)
        {
            if(!$object->widthStop)
            {
                if(!$query->isWhere($object->getTable().'.status') && !$query->isWhere('status'))
                {
                    $query->where($object->getTable().'.status', 'use');
                }
            }

            $object->setWidthStop();
        });

        static::saved(function(Suppliers $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->update(['code' => \Skdepot\Helper::code('NCC', $object->id)]);
            }
        });

        static::deleted(function (\Ecommerce\Model\Brands $brand, $listIdRemove, $objects) {
            do_action('delete_suppliers_list_trash_success', $listIdRemove);
        });
    }

    static function options() {

        $suppliers = static::all();

        $suppliers->pluck('id', 'name' )->prepend('Chọn nhà cung cấp', 0);

        return apply_filters( 'gets_suppliers_option', $suppliers );
    }

}