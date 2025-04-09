<?php
use SkillDo\Validate\Rule;

Class SuppliersAdmin
{
    /**
     * @throws Exception
     */
    static function form(FormAdmin $form): FormAdmin {
        $form->leftTop
            ->addGroup('info','Thông Tin')
            ->text('code', [
                'label' => 'Mã nhà cung cấp',
                'placeholder' => 'Mã mặc định',
            ])
            ->text('name', [
                'label' => 'Tên nhà cung cấp',
                'validations'   => Rule::make()->notEmpty()
            ])
            ->textarea('excerpt', ['label' => 'Ghi chú']);

        $form->right
            ->addGroup('company', 'Thông tin công ty')
            ->email('company', ['label' => 'Tên công ty'])
            ->tel('tax', ['label' => 'Mã số thuế']);

        $form->right
            ->addGroup('information', 'Thông tin người đại diện')
            ->email('email', ['label' => 'Email', 'validations'   => Rule::make()->notEmpty()])
            ->tel('phone', ['label' => 'Số điện thoại', 'validations'   => Rule::make()->notEmpty()])
            ->text('address', ['label' => 'Địa chỉ']);
        $form->right
            ->addGroup('media', 'Media')
            ->image('image', ['label' => 'Ảnh đại diện']);

        return $form;
    }

    static function save($id, $insertData)
    {
        if(!empty($id))
        {
            if(empty($insertData['code']))
            {
                response()->error('Bạn chưa điền Mã nhà cung cấp');
            }

            $count = \Skdepot\Model\Suppliers::where('code', $insertData['code'])
                ->where('id', '<>', $id)
                ->count();
        }
        else
        {
            $count = 0;

            if(!empty($insertData['code']))
            {
                $count = \Skdepot\Model\Suppliers::where('code', $insertData['code'])->count();
            }
        }

        if($count > 0)
        {
            response()->error('Mã nhà cung cấp đã được sử dụng');
        }

        return \Skdepot\Model\Suppliers::insert($insertData);
    }
}
add_filter('manage_suppliers_input', 'SuppliersAdmin::form');
add_filter('form_submit_suppliers', 'SuppliersAdmin::save', 10, 2);
