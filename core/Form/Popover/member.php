<?php
use SkillDo\Form\PopoverHandle;
use SkillDo\Http\Request;
class MemberPopover extends PopoverHandle
{
    public function __construct()
    {
        $this->setModule('member');
    }

    public function search(Request $request): array
    {
        $items = [];

        $query = \SkillDo\Model\User::where('isMember', 1)
            ->select('id', 'username', 'firstname', 'lastname', 'email', 'phone')
            ->limit($this->limit)
            ->offset($this->page* $this->limit);

        if(!empty($this->keyword))
        {
            $query->where(function ($qr) {
                $qr->whereRaw('concat(firstname," ", lastname) like \'%'.$this->keyword.'%\'');
                $qr->orWhere('username', 'like', $this->keyword.'%');
                $qr->orWhere('username', 'like', '%'.$this->keyword);
            });
        }

        $objects = $query->get();

        if(have_posts($objects)) {
            foreach ($objects as $value) {
                $items[]  = [
                    'id'        => $value->id,
                    'name'  => $value->firstname.' '.$value->lastname,
                    'username'  => $value->username,
                    'contact'   => (!empty($value->phone)) ? $value->phone : $value->email,
                ];
            }
        }

        return $items;
    }

    public function value(Request $request, $listId): array
    {
        $items = [];

        if(have_posts($listId)) {

            $objects = \SkillDo\Model\User::where('isMember', 1)
                ->whereKey($listId)
                ->select('id', 'username', 'firstname', 'lastname', 'email', 'phone')
                ->get();

            foreach ($objects as $value)
            {
                $items[]  = [
                    'id'        => $value->id,
                    'name'      => $value->firstname.' '.$value->lastname,
                    'username'  => $value->username,
                    'contact'   => (!empty($value->phone)) ? $value->phone : $value->email,
                ];
            }
        }

        return $items;
    }
}