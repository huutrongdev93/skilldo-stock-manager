<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {

    public function up(): void
    {
        if(!schema()->hasTable('inventories')) {
            schema()->create('inventories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('product_id', 100)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('product_name', 255)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('product_code', 100)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('branch_id', 50)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('branch_name', 100)->collate('utf8mb4_unicode_ci')->nullable();
                $table->integer('parent_id')->default(0);
                $table->integer('stock')->default(0);
                $table->integer('reserved')->default(0);
                $table->string('status', 100)->collate('utf8mb4_unicode_ci')->default('outstock');
                $table->integer('default')->default(0);
                $table->integer('order')->default(0);
                $table->dateTime('created')->default('CURRENT_TIMESTAMP');
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('inventories_history')) {
            schema()->create('inventories_history', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('inventory_id')->default(0);
                $table->text('message')->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('action', 200)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('type', 50)->default('stock');
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default('CURRENT_TIMESTAMP');
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasColumn('products', 'stock_status')) {
            schema()->table('products', function (Blueprint $table) {
                $table->string('stock_status', 100)->default('outstock')->after('weight');
            });
        }
    }

    public function down(): void
    {
        schema()->drop('inventories');
        schema()->drop('inventories_history');
    }
};