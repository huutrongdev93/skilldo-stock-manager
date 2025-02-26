<?php
namespace Stock\Model;
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
        'image'                         => ['image'],
        'total_invoiced'                => ['int', 0],
        'total_invoiced_without_return' => ['int', 0],
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function(Suppliers $object, $action) {
            if($action == 'add' && empty($object->code))
            {
                DB::table($object->getTable())
                    ->where($object->getPrimaryKey(), $object->id)
                    ->update(['code' => \Stock\Helper::code('NCC', $object->id)]);
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