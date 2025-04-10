<?php
class MemberAdminButton
{
    static function formButton($module): void
    {
        $buttons = [];

        $view = Url::segment(3);

        if($view == 'new')
        {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.member'),
                'class' => ['btn-back-to-redirect']
            ]);
        }

        if($view == 'edit')
        {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('back', [
                'href' => Url::route('admin.member'),
                'class' => ['btn-back-to-redirect']
            ]);
        }

        $buttons = apply_filters('member_form_buttons', $buttons);

        Admin::view('include/form/form-action', ['buttons' => $buttons, 'module' => $module]);
    }
}
add_action('form_member_action_button', 'MemberAdminButton::formButton');