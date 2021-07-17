<?php
/**
Plugin name     : Stock manager
Plugin class    : stock_manager
Plugin uri      : http://sikido.vn
Description     : Ứng dụng quản lý kho hàng nhiều chi nhánh
Author          : Nguyễn Hữu Trọng
Version         : 1.1.0
 */
define('STOCK_NAME', 'stock-manager');
define('STOCK_PATH', Path::plugin(STOCK_NAME));
define('STOCK_VERSION', '1.1.0');
class stock_manager {

    private $name = 'stock_manager';

    public function active() {

        $model = get_model();

        $model->query("CREATE TABLE IF NOT EXISTS `".CLE_PREFIX."inventories` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `product_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `product_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `branch_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `parent_id` int(11) DEFAULT '0',
          `stock` int(11) DEFAULT '0',
          `reserved` int(11) DEFAULT '0',
          `created` datetime DEFAULT NULL,
          `updated` datetime DEFAULT NULL,
          `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instock',
          `order` int(11) NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        if(!$model->db_field_exists('stock_status','products')) {
            $model->query("ALTER TABLE `".CLE_PREFIX."products` ADD `stock_status` VARCHAR(255) NULL DEFAULT 'instock' AFTER `weight`;");
        }

        $branch = Branch::count([]);

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
    }

    public function uninstall() {
        $model = get_model();
        $model->query("DROP TABLE IF EXISTS `".CLE_PREFIX."branchs`");
        $model->query("DROP TABLE IF EXISTS `".CLE_PREFIX."inventories`");
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