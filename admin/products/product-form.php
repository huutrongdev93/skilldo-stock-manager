<?php
use SkillDo\Form\Form;

class AdminStockProductForm {

    static function form(FormAdmin $form): FormAdmin
    {
        $form->right
            ->group('moreInfo')
            ->price('price_cost', ['value' => 0, 'label' => trans('Giá nhập')]);

        return $form;
    }

    static function formVariation(Form $form, $variationId, $variation): Form
    {
        $priceCost = 0;

        if(!empty($variationId))
        {
            $branch = \Skdepot\Helper::getBranchWebsite();

            $inventory = \Skdepot\Model\Inventory::where('product_id', $variationId);

            if(have_posts($branch))
            {
                $inventory->where('branch_id', $branch->id);
            }

            $inventory = $inventory->first();

            $priceCost = $inventory->price_cost ?? 0;
        }

        $form->price('variation[price_cost]', ['value' => $priceCost, 'label' => trans('Giá nhập'), 'start' => 3], $priceCost);

        return $form;
    }

    /**
     * Thêm price_cost từ inventory vào product khi edit sản phẩm
     * @param $object
     * @return mixed
     */
    static function dataEdit($object): mixed
    {
        $module = Cms::getData('module');

        if($module === 'products' && $object->hasVariation === 0)
        {
            $branch = \Skdepot\Helper::getBranchCurrent();

            $inventory = \Skdepot\Model\Inventory::where('product_id', $object->id)->where('branch_id', $branch->id)->first();

            if(have_posts($inventory))
            {
                $object->price_cost = $inventory->price_cost;
            }
        }

        return $object;
    }
}

add_filter('manage_products_input', 'AdminStockProductForm::form', 10);
add_filter('admin_product_variation_form_price', 'AdminStockProductForm::formVariation', 10, 3);
add_filter('sets_field_before', 'AdminStockProductForm::dataEdit', 10, 3);