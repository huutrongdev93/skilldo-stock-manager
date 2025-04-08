<?php
namespace Stock\Update;

use Illuminate\Database\Schema\Blueprint;

class UpdateVersion200
{
    protected array $structure = [];

    public function database(): void
    {
        if(!schema()->hasColumn('inventories_history', 'type')) {
            schema()->table('inventories_history', function (Blueprint $table) {
                $table->string('type', 50)->default('stock')->after('action');
                $table->integer('user_created')->default(0)->after('action');
                $table->dateTime('created')->default('CURRENT_TIMESTAMP')->change();
            });
        }
        if(schema()->hasTable('inventories')) {
            schema()->table('inventories', function (Blueprint $table) {
                $table->dateTime('created')->default('CURRENT_TIMESTAMP')->change();
            });
        }
    }

    public function run(): void
    {
        $this->database();
    }
}