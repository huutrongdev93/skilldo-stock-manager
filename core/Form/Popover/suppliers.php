<?php
use SkillDo\Form\PopoverHandle;
use SkillDo\Http\Request;
class SuppliersPopover extends PopoverHandle
{
    public function __construct()
    {
        $this->setModule('suppliers');
    }

    public function search(Request $request): array
    {
        $items = [];

        $args = Qr::select('id', 'name', 'image')->limit($this->limit)->offset($this->page* $this->limit);

        if(!empty($this->keyword)) {

            $args->where('name', 'like', '%' . $this->keyword . '%');
        }

        $objects = \Stock\Model\Suppliers::gets($args);

        if(have_posts($objects)) {
            foreach ($objects as $value) {
                $items[]  = [
                    'id'        => $value->id,
                    'name'      => $value->name,
                    'image'     => Image::medium($value->image)->link(),
                ];
            }
        }

        return $items;
    }

    public function value(Request $request, $listId): array
    {
        $items = [];

        if(have_posts($listId)) {

            $objects = \Stock\Model\Suppliers::gets(Qr::whereIn('id', $listId)->select('id', 'name', 'image'));

            foreach ($objects as $value) {

                $items[]  = [
                    'id'        => $value->id,
                    'name'      => $value->name,
                    'image'     => Image::medium($value->image)->link(),
                ];
            }
        }

        return $items;
    }
}