<?php

use SkillDo\Table\Columns\ColumnText;

class StockAdminCustomerDetail
{
    static function tabs($tabs): array
    {
        $tabs['debt'] = [
            'label' 	=> 'Nợ khách cần trả',
            'icon'		=> '<i class="fa-duotone fa-gear"></i>',
            'callback'	=> 'StockAdminCustomerDetail::tabDebt',
        ];

        return $tabs;
    }

    static function tabDebt($user): void
    {
        if(have_posts($user)) {

            $tableDebt = new \Skdepot\Table\Customer\Debt();

            $tableDebt->userId = $user->id;

            Plugin::view(SKDEPOT_NAME, 'admin/customer/detail/tab-debt', [
                'user'      => $user,
                'table'    => $tableDebt,
            ]);
        }
    }

    static function box($infoBox, $user): array
    {
        $infoBox['debt'] = [
            'value' => number_format($user->debt),
            'description' => 'Nợ hiện tại'
        ];

        return $infoBox;
    }
}

add_filter('admin_my_action_links', 'StockAdminCustomerDetail::tabs', 20, 2);
add_filter('customer_detail_info_box', 'StockAdminCustomerDetail::box', 20, 2);