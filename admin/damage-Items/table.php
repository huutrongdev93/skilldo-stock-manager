<?php
namespace Skdepot\Table;

use Admin;
use Branch;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnBadge;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;
use Url;

class DamageItem extends SKDObjectTable
{
    protected string $module = 'damage_items';

    protected mixed $model = \Skdepot\Model\DamageItem::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['code'] = [
            'label'  => trans('Mã xuất hủy'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['damage_date'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('damage_date', $item, $args)
        ];

        $this->_column_headers['branch_name'] = [
            'label'  => trans('Chi nhánh'),
            'column' => fn($item, $args) => ColumnText::make('branch_name', $item, $args)
        ];

        $this->_column_headers['damage'] = [
            'label'  => trans('Người hủy'),
            'column' => fn($item, $args) => ColumnText::make('damage_name', $item, $args)
        ];

        $this->_column_headers['note'] = [
            'label'  => trans('ghi chú'),
            'column' => fn($item, $args) => ColumnText::make('note', $item, $args)
        ];

        $this->_column_headers['subtotal'] = [
            'label'  => trans('Tổng giá trị'),
            'column' => fn($item, $args) => ColumnText::make('subtotal', $item, $args)->number()
        ];

        $this->_column_headers['status'] = [
            'label'  => trans('Trạng thái'),
            'column' => fn($item, $args) => ColumnBadge::make('status', $item, $args)
                ->color(function (string $status) {
                    return \Skdepot\Status\DamageItem::tryFrom($status)->badge();
                })
                ->label(function (string $status) {
                    return \Skdepot\Status\DamageItem::tryFrom($status)->label();
                })
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $data = [
            'code'        => $item->code,
            'damage_date' => $item->damage_date,
            'branch_name' => $item->branch_name,
            'user_created_name' => $item->user_created_name,
            'damage_name' => $item->damage_name,
            'status' => Admin::badge(\Skdepot\Status\DamageItem::tryFrom($item->status)->badge(), \Skdepot\Status\DamageItem::tryFrom($item->status)->label()),
            'subtotal' => \Prd::price($item->subtotal),
        ];

        $buttons[] = Admin::button('blue', [
            'icon' => '<i class="fa-sharp-duotone fa-solid fa-eye"></i>',
            'tooltip' => 'Chi tiết',
            'data-id' => $item->id,
            'data-bill' => htmlspecialchars(json_encode($data)),
            'class' => 'js_damage_items_btn_detail'
        ]);

        if($item->status === \Skdepot\Status\DamageItem::draft->value)
        {
            $buttons[] = Admin::button('blue', [
                'icon' => Admin::icon('edit'),
                'href' => \Url::route('admin.damage.items', ['id' => $item->id]),
                'tooltip' => 'Cập nhật',
            ]);

            $buttons['cancel'] = Admin::btnConfirm('red', [
                'icon'      => Admin::icon('close'),
                'tooltip'   => 'Đồng ý',
                'id'        => $item->id,
                'model'     =>  \Skdepot\Model\DamageItem::class,
                'ajax'      => 'DamageItemsAdminAjax::cancel',
                'heading'   => 'Đồng ý',
                'description' => 'Bạn có chắc chắn muốn xác nhận hủy phiếu xuất hàng này?',
                'attr' => [
                    'callback-success' => 'damageItemsIndexHandle.cancelSuccess',
                ]
            ]);
        }

        $buttons['action'] = \Plugin::partial(SKDEPOT_NAME, 'admin/damage-items/table-action', ['item' => $item]);

        return apply_filters('admin_'.$this->module.'_table_columns_action', $buttons);
    }

    function headerFilter(Form $form, Request $request)
    {
        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        $time = $request->input('time');

        if(empty($time))
        {
            $time = '01'.date('/m/Y').' - '.date('t/m/Y');
        }

        $form->text('keyword', ['placeholder' => 'Mã phiếu'], $request->input('keyword'));
        $form->daterange('time', [], $time);
        $form->select2('status', \Skdepot\Status\DamageItem::options()->pluck('label', 'value')
            ->prepend('Tất cả trạng thái', '')
            ->toArray(), [], request()->input('status'));

        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Tạo phiếu',
            'href' => Url::route('admin.damage.items.new')
        ]);

        $buttons[] = Admin::button('blue', [
            'icon' => Admin::icon('download'),
            'text' => 'Xuất file',
            'id' => 'js_btn_export_list'
        ]);

        $buttons[] = Admin::button('reload');

        return $buttons;
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $time = trim($request->input('time'));

        if(!empty($time))
        {
            $time = explode(' - ', $time);

            if(count($time) == 2)
            {
                $timeStart  = strtotime(str_replace('/', '-', $time[0]).' 00:00:00');

                $timeEnd    = strtotime(str_replace('/', '-', $time[1]).' 00:00:00');

                $query->where('damage_date', '>=', $timeStart);

                $query->where('damage_date', '<=', $timeEnd);
            }
        }

        $keyword = trim($request->input('keyword'));

        if(!empty($keyword))
        {
            $query->where('code', $keyword);
        }

        $status = $request->input('status');

        if(!empty($status))
        {
            $query->where('status', $status);
        }

        $branch = \Skdepot\Helper::getBranchCurrent();

        if(!empty($branch))
        {
            $query->where('branch_id', $branch->id);
        }

        return $query;
    }

    public function queryDisplay(Qr $query, \SkillDo\Http\Request $request, $data = []): Qr
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query
            ->orderBy('created', 'desc');

        return $query;
    }

    public function dataDisplay($objects)
    {
        foreach ($objects as $object)
        {
            $object->damage_date = !empty($object->damage_date) ? $object->damage_date : strtotime($object->created);
            $object->damage_date = date('d/m/Y H:s', $object->damage_date);

            $userCreated = \User::find($object->user_created);
            $object->user_created_name = (have_posts($userCreated)) ? $userCreated->firstname.' '.$userCreated->lastname : '';
        }

        return $objects;
    }
}