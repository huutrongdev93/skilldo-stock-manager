<?php
namespace Stock\Update;

use Illuminate\Database\Schema\Blueprint;
use Inventory;

class UpdateVersion110
{
    protected array $structure = [];

    public function database(): void
    {
        if(schema()->hasTable('inventories') && !schema()->hasColumn('inventories', 'parent_id')) {

            schema()->table('inventories', function (Blueprint $table) {
                $table->integer('parent_id')->default(0)->after('branch_name');
            });

            $inventories = Inventory::gets();

            foreach ($inventories as $item) {

                if($item->parent_id != 0) continue;

                $product = \Ecommerce\Model\Variation::whereKey($item->product_id)
                    ->where('parent_id', '<>', 0)
                    ->get();

                if(have_posts($product))
                {
                    Inventory::insert(['id' => $item->id, 'parent_id' => $product->parent_id]);
                }
            }
        }
    }

    public function run(): void
    {
        $this->database();
    }
}