<?php
namespace Stock\Update;

use Illuminate\Database\Schema\Blueprint;
use SkillDo\DB;
use Storage;

class UpdateVersion130
{
    protected array $structure = [];

    public function database(): void
    {
        if(!schema()->hasTable('inventories_history'))
        {
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

    public function run(): void
    {
        $this->database();
    }
}