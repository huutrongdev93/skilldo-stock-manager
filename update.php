<?php
if(!Admin::is()) return;
function Stock_update_core() {
    if(Admin::is() && Auth::check() ) {
        $version = Option::get('stock_manager_version');
        $version = (empty($version)) ? '1.0.0' : $version;
        if (version_compare( STOCK_VERSION, $version ) === 1 ) {
            $update = new Stock_Update_Version();
            $update->runUpdate($version);
        }
    }
}
add_action('admin_init', 'Stock_update_core');
Class Stock_Update_Version {
    public function runUpdate($stockVersion) {
        $listVersion    = ['1.1.0'];
        $model          = get_model();
        foreach ($listVersion as $version ) {
            if(version_compare( $version, $stockVersion ) == 1) {
                $function = 'update_Version_'.str_replace('.','_',$version);
                if(method_exists($this, $function)) $this->$function($model);
            }
        }
        Option::update('stock_manager_version', STOCK_VERSION );
    }
    public function update_Version_1_1_0($model) {
        Stock_Update_Database::Version_1_1_0($model);
    }
}
Class Stock_Update_Database {
    public static function Version_1_1_0($model) {
        if(!model()::schema()->hasColumn('inventories', 'parent_id')) {
            $model->query("ALTER TABLE `".CLE_PREFIX."inventories` ADD `parent_id` int(11) NOT NULL DEFAULT '0' AFTER `branch_name`");
        }
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