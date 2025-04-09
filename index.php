<?php
const SKDEPOT_NAME = 'skdepot';

const SKDEPOT_VERSION = '1.0.0';

class Skdepot {

    private string $name = 'Skdepot';

    public function active(): void
    {
        if(!class_exists('Branch'))
        {
            response()->error('Bạn chưa có plugin chi nhánh');
        }

        $db = include 'database/database.php';

        $db->up();

        $branch = Branch::count();

        if(empty($branch)) {
            Branch::create([
                'name'      => 'Kho trung tâm',
                'address'   => Option::get('contact_address'),
                'email'     => Option::get('contact_mail'),
                'phone'     => Option::get('contact_phone'),
                'isDefault' => 1
            ]);
        }

        \Skdepot\Helper::createInventories();
    }

    public function uninstall(): void
    {
        $db = include 'database/database.php';

        $db->down();
    }
}

include_once 'autoload/autoload.php';