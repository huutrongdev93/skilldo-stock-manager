<?php
class AdminSuppliersButton {

    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::route('admin.suppliers.new')]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    static function bulkAction(array $actionList): array
    {
        return $actionList;
    }

    static function formButton($module): void
    {
        $buttons = [];

        $view = Url::segment(3);

        if($view == 'add') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.suppliers'),
                'class' => ['btn-back-to-redirect']
            ]);
        }

        if($view == 'edit') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('add', ['href' => Url::route('admin.suppliers.new'), 'text' => '', 'tooltip' => 'Thêm mới']);
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.suppliers'),
                'text' => '',
                'tooltip' => 'Quay lại',
                'class' => ['btn-back-to-redirect']
            ]);
        }

        $buttons = apply_filters('suppliers_form_buttons', $buttons);

        Admin::view('include/form/form-action', ['buttons' => $buttons, 'module' => $module]);
    }
}
add_filter('table_suppliers_header_buttons', 'AdminSuppliersButton::tableHeaderButton');
add_filter('table_suppliers_bulk_action_buttons', 'AdminSuppliersButton::bulkAction', 30);
add_action('form_suppliers_action_button', 'AdminSuppliersButton::formButton');