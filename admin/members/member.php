<?php

use SkillDo\Validate\Rule;

class SkdepotMember
{
    static function register($tabs)
    {
        $tabs['skdepotMember'] = [
            'label'         => 'Nhân viên',
            'description'   => 'Quản lý nhân viên bán hàng',
            'href'          => Url::route('admin.member'),
            'icon'          => '<i class="fa-duotone fa-solid fa-user-tie"></i>',
            'form'          => false
        ];

        return $tabs;
    }

    static function form(FormAdmin $form): FormAdmin {

        $object = Cms::getData('object');

        $states = SkillDo\Location::provincesOptions();

        $states = Arr::prepend($states, 'Chọn tỉnh thành', '');

        $districtOptions = [];

        $wardOptions = [];

        if(!empty($object->id))
        {
            $object->city = User::getMeta($object->id, 'city', true);

            if(!empty($object->city))
            {
                $districtOptions = \Skilldo\Location::districtsOptions($object->city);

                $object->districts = User::getMeta($object->id, 'districts', true);

                if(!empty($object->districts))
                {
                    $wardOptions = \Skilldo\Location::wardsOptions($object->districts);

                    $object->ward = User::getMeta($object->id, 'ward', true);
                }
            }
        }
        else
        {
            $form->leftTop
                ->addGroup('accountLogin', 'Thông tin đăng nhập')
                ->text('username', [
                    'label' => 'Tên đăng nhập',
                    'placeholder' => 'Tên đăng nhập',
                    'validations' => Rule::make()
                        ->notEmpty()
                        ->string()
                        ->between(5, 30)
                        ->alphaNum()
                ])
                ->password('password', [
                    'label' => 'Nhập mật khẩu',
                    'placeholder' => 'Nhập mật khẩu',
                    'start' => 6,
                    'validations' => Rule::make()->notEmpty()
                ])
                ->password('re_password', [
                    'label' => 'Nhập lại mật khẩu',
                    'placeholder' => 'Nhập lại mật khẩu',
                    'start' => 6,
                    'validations' => Rule::make()
                        ->notEmpty()
                        ->identical('password', 'input')
                ]);
        }

        $form->leftTop
            ->addGroup('accountInfo', 'Thông tin cơ bản')
            ->text('firstname', ['label' => 'Họ và tên đệm', 'placeholder' => 'Họ và tên đệm', 'start' => 6])
            ->text('lastname', ['label' => 'Tên', 'placeholder' => 'Tên', 'start' => 6])
            ->email('email', [
                'label' => 'Email',
                'placeholder' => 'Email',
                'start' => 6,
                'validations' => Rule::make()
                    ->notEmpty()
                    ->email()
                    ->unique('users', 'email')
            ])
            ->tel('phone', [
                'label' => 'Số Điện thoại',
                'placeholder' => 'Số Điện thoại',
                'start' => 6,
                'validations' => Rule::make()
                    ->notEmpty()
                    ->phone()
                    ->unique('users', 'phone')
            ])
            ->text('address', [
                'label' => trans('address'),
                'placeholder' => "Địa chỉ của bạn"
            ])
            ->select2(
                'city', $states, [
                'label' => trans('checkout.field.city'),
                'data-input-address' => 'city',
                'start' => 4
            ])
            ->select2(
                'districts', $districtOptions, [
                'data-input-address' => 'district',
                'label'     => trans('checkout.field.district'),
                'start'     => 4
            ])
            ->select2(
                'ward', $wardOptions, [
                'data-input-address' => 'ward',
                'label'     => trans('checkout.field.ward'),
                'start'     => 4
            ]);

        $form->right
            ->addGroup('accountNote', 'Ghi chú')
            ->textarea('note', ['placeholder' => 'Ghi chú']);

        return $form;
    }
}

add_filter('skd_system_tab', 'SkdepotMember::register', 50);
add_filter('manage_member_input', 'SkdepotMember::form');