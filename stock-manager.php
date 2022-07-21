<?php
/**
Plugin name     : Stock manager
Plugin class    : stock_manager
Plugin uri      : http://sikido.vn
Description     : Ứng dụng quản lý kho hàng nhiều chi nhánh
Author          : Nguyễn Hữu Trọng
Version         : 1.2.0
 */
const STOCK_NAME = 'stock-manager';

const STOCK_VERSION = '1.2.0';

define('STOCK_PATH', Path::plugin(STOCK_NAME));

if(!class_exists('Sicommerce_Cart')) {
    return false;
}

class stock_manager {

    private $name = 'stock_manager';

    public function active() {

        $model = model();

        if(!$model::schema()->hasTable('inventories')) {
            $model::schema()->create('inventories', function ($table) {
                $table->increments('id');
                $table->string('product_id', 255)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('product_name', 200)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('product_code', 200)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('branch_id', 255)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('branch_name', 100)->collate('utf8mb4_unicode_ci')->nullable();
                $table->integer('parent_id')->default(0);
                $table->integer('stock')->default(0);
                $table->integer('reserved')->default(0);
                $table->string('status', 100)->collate('utf8mb4_unicode_ci')->default('instock');
                $table->integer('default')->default(0);
                $table->integer('order')->default(0);
                $table->dateTime('created');
                $table->dateTime('updated')->nullable();
            });
        }

        if($model::schema()->hasTable('products') && !$model::schema()->hasColumn('products', 'stock_status')) {
            $model::schema()->table('products', function ($table) {
                $table->string('stock_status', 100)->default('instock')->after('weight');
            });
        }

        $branch = Branch::count();

        if(empty($branch)) {
            Branch::insert([
                'name'    => 'Kho trung tâm',
                'address' => Option::get('contact_address'),
                'email'   => Option::get('contact_mail'),
                'phone'   => Option::get('contact_phone'),
                'default' => 1
            ]);
        }

        $root = Role::get('root');
        $root->add_cap('inventory_list');
        $root->add_cap('inventory_edit');

        $admin = Role::get('administrator');
        $admin->add_cap('inventory_list');
        $admin->add_cap('inventory_edit');

        AdminStockProduct::productStatusCreated();
    }

    public function uninstall() {
        $model = model();
        $model::schema()->drop('inventories');
    }
}
require_once 'stock-manager-ajax.php';
require_once 'includes/inventory.php';
require_once 'order-action.php';
if(Admin::is()) {
    include_once 'admin/stock-roles.php';
    include_once 'admin/stock-manager-admin.php';
    include_once 'admin/stock-manager-metabox.php';
    require_once 'update.php';
}
include_once 'template/stock-manager-template.php';