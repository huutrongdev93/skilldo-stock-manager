<?php
/**
Plugin name     : Stock manager
Plugin class    : stock_manager
Plugin uri      : http://sikido.vn
Description     : Ứng dụng quản lý kho hàng nhiều chi nhánh
Author          : Nguyễn Hữu Trọng
Version         : 2.0.0
 */
const STOCK_NAME = 'stock-manager';

const STOCK_VERSION = '2.0.0';

define('STOCK_PATH', Path::plugin(STOCK_NAME));

class stock_manager {

    private string $name = 'stock_manager';

    public function active(): void
    {
        $db = include 'database/database.php';

        $db->up();

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
        $root->add('inventory_list');
        $root->add('inventory_edit');

        $admin = Role::get('administrator');
        $admin->add('inventory_list');
        $admin->add('inventory_edit');

        AdminStockProduct::productStatusCreated();
    }

    public function uninstall(): void
    {
        $db = include 'database/database.php';

        $db->down();
    }
}
require_once 'ajax.php';
require_once 'includes/helper.php';
require_once 'includes/model.php';
require_once 'order.php';
if(Admin::is()) {
    include_once 'admin/roles.php';
    include_once 'admin/admin.php';
    require_once 'update.php';
}
include_once 'template.php';
include_once 'checkout.php';