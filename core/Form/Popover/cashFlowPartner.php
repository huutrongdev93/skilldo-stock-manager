<?php
use SkillDo\Form\PopoverHandle;
use SkillDo\Http\Request;
class CashFlowPartnerPopover extends PopoverHandle
{
    public function __construct()
    {
        $this->setModule('CashFlowPartner');
        $this->setTemplateId('valueNoImg', 'popover_advance_cash_flow_partner_load_template');
        $this->setTemplateId('searchNoImg', 'popover_advance_cash_flow_partner_search_template');
    }

    public function search(Request $request): array
    {
        $items = [];

        $args = Qr::select('id', 'name', 'phone')->limit($this->limit)->offset($this->page* $this->limit);

        if(!empty($this->keyword)) {

            $args->where('name', 'like', '%' . $this->keyword . '%');
        }

        $objects = \Skdepot\Model\CashFlowPartner::gets($args);

        if(have_posts($objects)) {
            foreach ($objects as $value) {
                $items[]  = [
                    'id'        => $value->id,
                    'name'      => $value->name,
                    'phone'     => $value->phone,
                ];
            }
        }

        return $items;
    }

    public function value(Request $request, $listId): array
    {
        $items = [];

        if(have_posts($listId)) {

            $objects = \Skdepot\Model\CashFlowPartner::whereKey($listId)->select('id', 'name', 'phone')->get();

            foreach ($objects as $value) {

                $items[]  = [
                    'id'        => $value->id,
                    'name'      => $value->name,
                    'phone'     => $value->phone,
                ];
            }
        }

        return $items;
    }

    public function templateValueNoImg(): string
    {
        return Plugin::partial(SKDEPOT_NAME, 'popover/cash-flow-partner');
    }

    public function templateSearchNoImg(): string
    {
        return Plugin::partial(SKDEPOT_NAME, 'popover/cash-flow-partner');
    }
}