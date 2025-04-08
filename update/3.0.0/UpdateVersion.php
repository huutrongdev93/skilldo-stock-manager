<?php
namespace Stock\Update;

use Illuminate\Database\Schema\Blueprint;
use SkillDo\DB;
use Storage;

class UpdateVersion300
{
    protected array $structure = [
        'upload.php',
        'thumb.png',
        'ajax.php',
        'order.php',
        'checkout.php',
        'template.php',
        'admin/admin.php',
        'admin/inventories/inventories.php',
    ];

    public function database(): void
    {
        (include STOCK_PATH.'database/db_3.0.0.php')->up();
    }

    public function suppliers(): void
    {
        DB::table('routes')
            ->where('controller', 'frontend/products/index/')
            ->where('object_type', 'suppliers')
            ->delete();

    }

    public function histories(): void
    {
        \Stock\Helper::createInventories();

        $inventories = \Stock\Model\Inventory::all();

        foreach ($inventories as $inventory)
        {
            \Stock\Model\History::where('inventory_id', $inventory->id)->update([
                'product_id' => $inventory->product_id,
                'branch_id' => $inventory->branch_id
            ]);
        }
    }

    public function structure(): void
    {
        $storages = Storage::disk('plugin');

        foreach ($this->structure as $file)
        {
            $file = STOCK_NAME.'/'.$file;

            if($storages->has($file))
            {
                if($storages->directoryExists($file))
                {
                    $storages->deleteDirectory($file);
                }
                else {
                    $storages->delete($file);
                }
            }
        }
    }

    public function run(): void
    {
        DB::table("inventories")
            ->where('product_id', '')
            ->delete();
        $this->database();
        $this->histories();
        $this->structure();
    }
}