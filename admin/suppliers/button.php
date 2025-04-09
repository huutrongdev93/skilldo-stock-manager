<?php
class AdminSuppliersButton
{
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
add_action('form_suppliers_action_button', 'AdminSuppliersButton::formButton');