<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class () extends Migration {

    public function up(): void
    {
        if(!schema()->hasTable('inventories_history')) {
            schema()->create('inventories_history', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('inventory_id')->default(0);
                $table->text('message')->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('action', 200)->collate('utf8mb4_unicode_ci')->nullable();
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }
    }

    public function down(): void
    {
        schema()->drop('inventories_history');
    }
};