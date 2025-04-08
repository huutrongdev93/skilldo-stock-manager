<?php

use SkillDo\Validate\Rule;

class StockBranchAjax
{
    static function website(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Id chi nhánh')->notEmpty()->integer()->min(1),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $id = (int)$request->input('id');

        $object = Branch::whereKey($id)->first();

        if(!have_posts($object))
        {
            response()->error('Chi nhánh không tồn tại hoặc đã dừng hoạt động');
        }

        $website = \Stock\Config::get('website');

        if($object->id == $website)
        {
            response()->error('Chi nhánh này đang là chi nhánh mặc định của website');
        }

        \Stock\Config::update('website', $object->id);

        response()->success(trans('ajax.update.success'));
    }
}

Ajax::admin('StockBranchAjax::website');