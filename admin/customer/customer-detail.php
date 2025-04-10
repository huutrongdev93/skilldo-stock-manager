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

    static function memberButton($info, $user): array
    {
        if($user->isMember == 0)
        {
            $info['member'] = [
                'icon'  => '',
                'label' => Admin::button('blue', [
                    'icon' => '<i class="fa-duotone fa-solid fa-arrow-right-long"></i>',
                    'text' => 'nhân viên',
                    //'tooltip' => 'Chuyển thành nhân viên',
                    'class' => 'js_btn_customer_to_member',
                    'data-id' => $user->id,
                    'data-ajax' => 'SkdepotCustomerAdminAjax::toMember',
                    'data-heading' => 'Chuyển Khách Hàng Thành Nhân Viên',
                    'data-description' => 'Xác nhận chuyển Khách Hàng này thành Nhân Viên',
                ]),
            ];
        }
        else {
            $info['member'] = [
                'icon'  => '<span class="text-danger"><i class="fa-duotone fa-solid fa-briefcase"></i></span>',
                'label' => '<span class="text-danger">&nbsp;nhân viên</span>',
            ];
        }

        return $info;
    }

    static function memberScript(): void
    {
        Plugin::view(SKDEPOT_NAME, 'admin/customer/detail/script');
    }
}

add_filter('admin_my_action_links', 'StockAdminCustomerDetail::tabs', 20, 2);
add_filter('customer_detail_info_box', 'StockAdminCustomerDetail::box', 20, 2);
add_filter('admin_user_detail_mini_info', 'StockAdminCustomerDetail::memberButton', 10, 2);
add_action('admin_page_after_user_detail', 'StockAdminCustomerDetail::memberScript');