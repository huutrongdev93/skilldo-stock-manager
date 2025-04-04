<?php
const STOCK_NAME = 'stock-manager';

const STOCK_VERSION = '2.0.3';

define('STOCK_PATH', Path::plugin(STOCK_NAME));

if(!class_exists('Sicommerce_Cart')) {
    return false;
}

class stock_manager {

    private string $name = 'stock_manager';

    public function active(): void
    {
        if(!class_exists('Branch')) {
            response()->error('Bạn chưa có plugin chi nhánh');
        }

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

        \Stock\Helper::createInventories();
    }

    public function uninstall(): void
    {
        $db = include 'database/database.php';

        $db->down();
    }
}

include_once 'autoload/autoload.php';

if(!request()->ajax())
{
    if(Plugin::getCheckUpdate(STOCK_NAME) !== STOCK_VERSION)
    {
        $updater = new \Stock\Updater();

        $updater->checkForUpdates();
    }
}