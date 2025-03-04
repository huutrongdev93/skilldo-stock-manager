<?php
use SkillDo\Http\Request;

class CashFlowController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        Cms::setData('table', (new \Stock\Table\CashFlow()));

        Cms::setData('title', 'Sổ quỹ');

        Cms::setData('formReceipt', $this->form('receipt'));

        Cms::setData('formPayment', $this->form('payment'));

        Cms::setData('formPartner', $this->formPartner('payment'));

        $this->template->setView(STOCK_NAME.'/views/admin/cash-flow/index', 'plugin');

        $this->template->render();
    }

    public function form($type): \SkillDo\Form\Form
    {
        $labels = [
            'receipt' => [
                'group_id'      => 'Loại thu',
                'user'          => 'Nhân viên thu',
                'partner_type'  => 'Đối tượng nộp',
                'partner_value' => 'Tên người nộp',
            ],
            'payment' => [
                'group_id'      => 'Loại chi',
                'user'          => 'Nhân viên chi',
                'partner_type'  => 'Đối tượng nhận',
                'partner_value' => 'Tên người nhận',
            ]
        ];

        $branches = \Branch::all()->pluck('name', 'id')->toArray();

        $groups = \Stock\Model\CashFlowGroup::where('type', $type)
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        $partnerType = \Stock\CashFlowHelper::partnerType()
            ->pluck('name', 'key')
            ->toArray();

        $suppliers = \Stock\Model\Suppliers::all()->pluck('name', 'id')->toArray();

        $form = form()
            ->startDefault('<div class="stock-form-group form-group">')
            ->endDefault('</div>');

        $form->select2('branch_id', $branches, [
            'label' => 'Chi nhánh'
        ]);

        $form->text('code', [
            'label' => 'Mã phiếu',
            'placeholder' => 'Mã phiếu tự động'
        ]);

        $form->datetime('time', [
            'label' => 'Thời gian',
        ], date('d/m/Y H:i', time()));

        $form->select2('group_id', $groups, ['label' => $labels[$type]['group_id']]);

        $form->price('amount', ['label' => 'Giá trị'], 0);

        $form->popoverAdvance('user_id', [
            'label' => $labels[$type]['user'],
            'search' => 'user',
            'multiple' => false,
            'noImage' => true,
        ], Auth::id());

        $form->select('partner_type', $partnerType, ['label' => $labels[$type]['partner_type']], 'O');

        $form->none(Plugin::partial(STOCK_NAME, 'admin/cash-flow/add/partner-value', [
            'type' => $type,
            'label' => $labels[$type]['partner_value'],
            'suppliers' => $suppliers
        ]));

        $form->textarea('note', ['label' => 'Ghi chú']);

        return $form;
    }

    public function formPartner(): \SkillDo\Form\Form
    {
        $provinces = Skilldo\Location::provincesOptions();

        $provinces = Arr::prepend($provinces, 'Chọn tỉnh thành', '');

        $form = form();
        $form->text('partner[name]', ['label' => 'Họ và tên']);
        $form->phone('partner[phone]', ['label' => 'Số điện thoại','start' => 6]);
        $form->text('partner[address]', ['label' => 'Địa chỉ','start' => 6]);
        $form->select2('partner[city]', $provinces, [
            'label' => 'Tỉnh thành',
            'start' => 6,
            'data-input-address' => 'city',
            'data-id' => Arr::first($provinces)
        ]);
        $form->select2('partner[district]', [], [
            'label' => 'Quận huyện',
            'start' => 6,
            'data-input-address' => 'district',
        ]);
        $form->select2('partner[ward]', [], [
            'label' => 'Phường xã',
            'start' => 6,
            'data-input-address' => 'ward',
        ]);
        return $form;
    }
}