<?php

use SkillDo\Validate\Rule;

class CashFlowAdminAjax
{
    static function partnerAdd(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'partner.name' => Rule::make('Tên')->notEmpty(),
            'partner.phone' => Rule::make('Số điện thoại')->notEmpty()->phone()->unique('cash_flow_partner', 'phone'),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $partner = [
            'name'  => $request->input('partner.name'),
            'phone' => $request->input('partner.phone'),
            'address' => $request->input('partner.address'),
            'city'  => (int)$request->input('partner.city'),
            'district'  => (int)$request->input('partner.district'),
            'ward'  => (int)$request->input('partner.ward'),
        ];

        $address = PrdCartHelper::buildAddress($partner['city'], $partner['district'], $partner['ward']);

        $partner['address_full'] = $partner['address'].', '.$address;

        $id = \Stock\Model\CashFlowPartner::create($partner);

        if(empty($id) || is_skd_error($id))
        {
            response()->error($id);
        }

        $partner['id'] = $id;

        response()->success('Thêm đối tượng thành công', $partner);
    }

    static function add(\SkillDo\Http\Request $request): void
    {
        $type = $request->input('type');

        $label = ($type == 'receipt') ? 'thu' : 'chi';

        $validate = $request->validate([
            'type' => Rule::make('Loại phiếu')->notEmpty()->in(['receipt', 'payment']),
            'branch_id' => Rule::make('Chi nhánh')->notEmpty()->integer()->min(1),
            'group_id' => Rule::make('Loại '.$label)->notEmpty()->integer()->min(1),
            'user_id' => Rule::make('Nhân viên '.$label)->notEmpty()->integer()->min(1),
            'amount' => Rule::make('giá trị '.$label)->notEmpty()->integer()->min(1),
            'partner_type' => Rule::make('Đối tượng')->notEmpty()->in(['O', 'S', 'C']),
        ]);

        if ($validate->fails()) {
            response()->error($validate->errors());
        }

        $cashFlow = [
            'branch_id' => $request->input('branch_id'),
            'group_id' => $request->input('group_id'),
            'user_id' => $request->input('user_id'),
            'amount' => $request->input('amount'),
            'partner_type' => $request->input('partner_type'),
            'status' => 'success',
        ];

        //Chi nhánh
        $branch = Branch::find($cashFlow['branch_id']);

        if(empty($branch))
        {
            response()->error('Chi nhánh đã đóng cửa hoặc không còn trên hệ thống');
        }

        $cashFlow['branch_name'] = $branch->name;

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            $count = \Stock\Model\CashFlow::where('code', $code)->count();

            if($count > 0)
            {
                response()->error('Mã phiếu này đã được sử dụng');
            }

            $cashFlow['code'] = $code;
        }

        //Loại thu / chi
        $group = \Stock\Model\CashFlowGroup::where('type', $type)
            ->whereKey($cashFlow['group_id'])
            ->first();

        if(!have_posts($group))
        {
            response()->error('Loại '.$label.' không tồn tại');
        }

        $cashFlow['group_name'] = $group->name;

        //Giá trị thu / chi
        if($type == 'receipt' && $cashFlow['amount'] < 0)
        {
            response()->error('Giá trị thu không được nhỏ hơn 0');
        }

        if($type == 'payment' && $cashFlow['amount'] > 0)
        {
            $cashFlow['amount'] *= -1;
        }

        //Nhân viên
        $user = User::whereKey($cashFlow['user_id'])->first();

        if(!have_posts($user))
        {
            response()->error('Nhân viên '.$label.' không tồn tại');
        }

        $cashFlow['user_name'] = $user->firstname.' '.$user->lastname;

        //Đối tượng nộp chi
        //NCC
        if($cashFlow['partner_type'] == 'S')
        {
            $partner_value = (int)$request->input('partner_value_supplier');

            $partner = \Stock\Model\Suppliers::whereKey($partner_value)->first();

            if(!have_posts($partner))
            {
                response()->error('Nhà cung cấp không tồn tại');
            }

            $cashFlow['partner_id'] = $partner->id;
            $cashFlow['partner_code'] = $partner->code;
            $cashFlow['partner_name'] = $partner->name;
            $cashFlow['phone'] = $partner->phone;
        }
        //Khách hàng
        if($cashFlow['partner_type'] == 'C')
        {
            $partner_value = (int)$request->input('partner_value_customer');

            $partner = User::whereKey($partner_value)->first();

            if(!have_posts($partner))
            {
                response()->error('Khách hàng không tồn tại');
            }

            $cashFlow['partner_id'] = $partner->id;
            $cashFlow['partner_code'] = $partner->username;
            $cashFlow['partner_name'] = $partner->firstname.' '.$partner->lastname;
            $cashFlow['phone'] = $partner->phone;
        }

        //Other
        if($cashFlow['partner_type'] == 'O')
        {
            $partner_value = (int)$request->input('partner_value_other');

            $partner = \Stock\Model\CashFlowPartner::whereKey($partner_value)->first();

            if(!have_posts($partner))
            {
                response()->error('Khách hàng không tồn tại');
            }

            $cashFlow['partner_id'] = $partner->id;
            $cashFlow['partner_code'] = $partner->id;
            $cashFlow['partner_name'] = $partner->name;
            $cashFlow['phone'] = $partner->phone;
            $cashFlow['address'] = $partner->address_full;
        }

        $time = $request->input('time');

        if(!empty($time))
        {
            $time = str_replace('/', '-', $time);

            $time = strtotime($time);

            if($time > time())
            {
                response()->error('Thời gian không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $cashFlow['time'] = $time;

        $id = \Stock\Model\CashFlow::create($cashFlow);

        if(empty($id) || is_skd_error($id))
        {
            response()->error($id);
        }

        response()->success('Tạo phiêu '.$label.' thành công');
    }

    static function detail(\SkillDo\Http\Request $request): void
    {
        $id = $request->input('id');

        $object = \Stock\Model\CashFlow::find($id);

        if(empty($object))
        {
            response()->error('phiếu thu/chi không có trên hệ thống');
        }

        $object->status_label = \Stock\Status\CashFlow::tryFrom($object->status)->label();

        $object->partner_type_name = match ($object->partner_type) {
            'C' => 'Khách hàng',
            'S' => 'Nhà cung cấp',
            'O' => 'Khác',
        };

        //target note
        $object->target_note = '';

        if(!empty($object->target_code) && $object->target_type == 'Order')
        {
            $object->target_note = 'Phiếu thu tự động được tạo gắn với đơn hàng <a href="'.Url::admin('plugins/order/detail/'.$object->target_id).'" target="_blank">'.$object->target_code.'</a>';
        }

        if(!empty($object->target_code) && $object->target_type == \Stock\Prefix::purchaseOrder->value)
        {
            $object->target_note = 'Phiếu chi tự động được tạo gắn với phiếu nhập hàng <a href="'.Url::admin('plugins/order/detail/'.$object->target_id).'" target="_blank">'.$object->target_code.'</a>';
        }

        if(!empty($object->target_code) && $object->target_type == \Stock\Prefix::purchaseReturn->value)
        {
            $object->target_note = 'Phiếu thu tự động được tạo gắn với phiếu trả hàng nhập <a href="'.Url::admin('plugins/order/detail/'.$object->target_id).'" target="_blank">'.$object->target_code.'</a>';
        }

        $childrens = \Stock\Model\CashFlow::widthChildren()
            ->where('parent_id', $object->id)
            ->get()
            ->map(function($child){
                return $child->toObject();
            });

        $object->targets = $childrens;

        response()->success('load dữ liệu thành công', [
            'item' => $object->toObject()
        ]);
    }
}

Ajax::admin('CashFlowAdminAjax::partnerAdd');
Ajax::admin('CashFlowAdminAjax::add');
Ajax::admin('CashFlowAdminAjax::detail');