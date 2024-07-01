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

    public function runUpdate($stockVersion): void
    {
        $listVersion    = ['1.1.0', '1.3.0', '2.0.0'];
        $model          = model();
        foreach ($listVersion as $version ) {
            if(version_compare( $version, $stockVersion ) == 1) {
                $function = 'update_Version_'.str_replace('.','_',$version);
                if(method_exists($this, $function)) $this->$function($model);
            }
        }
        Option::update('stock_manager_version', STOCK_VERSION );
    }
    public function update_Version_1_1_0($model): void
    {
        (include STOCK_PATH.'/database/db_1.1.0.php')->up();
    }
    public function update_Version_1_3_0($model): void
    {
        (include STOCK_PATH.'/database/db_1.3.0.php')->up();
    }
    public function update_Version_2_0_0($model): void
    {
        (include STOCK_PATH.'/database/db_2.0.0.php')->up();
    }
}