<?php

class AdminInventoriesSystem
{
    static function register($tabs)
    {
        $tabs['inventories'] = [
            'label'         => 'Cấu hình kho hàng',
            'group'         => 'commerce',
            'description'   => 'Cấu hình sử dụng kho hàng',
            'callback'      => 'AdminInventoriesSystem::render',
            'icon'          => '<i class="fa-duotone fa-warehouse-full"></i>',
        ];
        return $tabs;
    }

    static function render(\SkillDo\Http\Request $request, $tab): void
    {
        $form = form();

        $brands = \Stock\Helper::getBranchAll()->pluck('name', 'id')->prepend('Lấy theo chi nhánh mặc định', 0)->toArray();

        $form->select2('inventoriesConfig[website]', $brands, [
            'label' => 'Chi nhánh website',
            'note'  => 'Chi nhánh để hiển thị số lượng ở website'
        ], \Stock\Config::get('website'));

        $order = [
            'one' => 'Cho phép mua hàng dựa trên kho tổng',
            'all' => 'Cho phép mua hàng dưa trên tất cả kho',
        ];

        $form->radio('inventoriesConfig[stockOrder]', $order, [
            'label' => 'Khách mua hàng'
        ], \Stock\Config::get('stockOrder'));

        $purchaseOrder = [
            'shipping'      => 'Khi đơn hàng ở trạng thái đang vận chuyển',
            'success'       => 'Khi đơn hàng ở trạng thái hoàn thành',
            'pay-shipping'  => 'Khi đơn hàng ở trạng thái đã thanh toán và đang vận chuyển',
            'pay-success'   => 'Khi đơn hàng ở trạng thái đã thanh toán và hoàn thành',
        ];

        $form->radio('inventoriesConfig[purchaseOrder]', $purchaseOrder, [
            'label' => 'Trừ kho'
        ], \Stock\Config::get('purchaseOrder'));

        $purchaseOrder = [
            'handmade' => 'Thao tác thủ công',
            'auto'     => 'Tự động chuyển số lượng các kho về kho bị thiếu',
        ];

        $form->radio('inventoriesConfig[lackStock]', $purchaseOrder, [
            'label' => 'Kho hàng thiếu sản phẩm'
        ], \Stock\Config::get('lackStock'));

        Admin::view('components/system-default', [
            'title'         => 'Cấu hình kho hàng',
            'description'   => 'Cấu hình sử dụng kho hàng',
            'form'          => $form
        ]);

        Admin::view('components/system-default', [
            'title' => 'Lưu ý',
            'description' => 'Cấu hình sử dụng kho hàng',
            'form' => function () {
                echo "<div class='box-content'>";
                echo '<p>Trong trường hợp bạn có nhiều kho hàng khi bạn chọn trừ kho <b>Khi khách vừa đặt hàng xong</b> nếu kho hàng hiện tại không đủ sản phẩm để trừ kho mà xử lý kho hàng không phải là "<b>Tự động chuyển số lượng các kho về kho bị thiếu</b>" thì kho sẽ không bị trừ';
                echo ' và bạn cũng không thể cập nhật trạng thái đơn hàng (trừ trạng thái hủy đơn hàng)</p>';
                echo '<p>Bạn phải nhập kho đủ số lượng đơn hàng cần sau đó xác nhận đơn hàng thì kho hàng sẽ bị trừ</p>';
                echo '</div>';
            }
        ]);
    }

    static function save(\SkillDo\Http\Request $request)
    {
        $inventoriesConfig = $request->input('inventoriesConfig');

        if(is_array($inventoriesConfig) && !empty($inventoriesConfig))
        {
            \Stock\Config::save($inventoriesConfig);
        }
    }
}

add_filter('skd_system_tab', 'AdminInventoriesSystem::register', 50);
add_action('admin_system_inventories_save', 'AdminInventoriesSystem::save');