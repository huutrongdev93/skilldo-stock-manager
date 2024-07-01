<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {

    public function up(): void
    {
        if(schema()->hasTable('inventories') && !schema()->hasColumn('inventories', 'parent_id')) {

            schema()->table('inventories', function (Blueprint $table) {
                $table->integer('parent_id')->default(0)->after('branch_name');
            });

            $inventories = Inventory::gets();

            foreach ($inventories as $item) {
                if($item->parent_id != 0) continue;
                $product = Product::gets(Qr::set($item->product_id)->where('type', 'variations')->where('parent_id', '<>', 0));
                if(have_posts($product)) {
                    Inventory::insert(['id' => $item->id, 'parent_id' => $product->parent_id]);
                }
            }
        }
    }

    public function down(): void
    {
    }
};