<?php
namespace Skdepot\Table;
use Admin;
use Role;
use Qr;
use SkillDo\Form\Form;
use SkillDo\Table\Columns\ColumnView;
use SkillDo\Table\SKDObjectTable;
use SkillDo\Http\Request;
use UserHelper;
use Url;

class Member extends SKDObjectTable {

    protected string $module = 'member';

    protected mixed $model = \SkillDo\Model\User::class;

    protected string $table = 'users';

    function getColumns() {

        $this->_column_headers = [
            'cb'       => 'cb',
            'username' => [
                'label' => trans('user.account'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnView::make('username', $item, $args)->html(function(ColumnView $column) {
                    echo '<p class="mb-1">'.$column->item->username.'</p>';
                })
            ],
            'fullname' => [
                'label'  => trans('user.fullname'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('fullname', $item, $args)->value(fn($item) => $item->firstname.' '.$item->lastname)
            ],
            'contact'  => [
                'label'  => trans('general.contact'),
                'column' => fn($item, $args) => \SkillDo\Table\Columns\ColumnView::make('contact', $item, $args)->html(function(ColumnView $column) {
                    echo '<p class="mb-1">'.$column->item->email.'</p>';
                    echo '<p class="mb-0 text-secondary">'.$column->item->phone.'</p>';
                })
            ],
            'status'   => [
                'label' => trans('user.status'),
                'column' => fn($item, $args) =>
                \SkillDo\Table\Columns\ColumnBadge::make('status', $item, $args)
                    ->color(fn (string $state): string => UserHelper::status($state.'.badge'))
                    ->label(fn (string $state): string => UserHelper::status($state.'.label'))
                    ->attributes(fn ($item): array => [
                        'data-id' => $item->id,
                        'data-status' => $item->status,
                    ])
                    ->class(['js_btn_user_status'])
            ],
            'role'     => [
                'label' => trans('user.role'),
                'column'=> fn($item, $args) => \SkillDo\Table\Columns\ColumnText::make('role', $item, $args)
                    ->value(fn($item) => Role::get($item->role)->getName()),
            ],
        ];

        $this->_column_headers = apply_filters( "manage_member_columns", $this->_column_headers );

        $this->_column_headers['action'] = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $buttons[] = Admin::button('blue', [
            'href'      => Url::route('admin.member.edit', ['id' => $item->id]),
            'class'     => ['btn-sm'],
            'icon'      => Admin::icon('edit'),
            'tooltip'   => trans('button.update'),
        ]);

        return apply_filters('admin_member_table_columns_action', $buttons, $item);
    }

    function headerFilter(Form $form, Request $request)
    {
        $form->text('email', ['placeholder' => trans('general.email').'...', 'start' => 3], request()->input('email'));

        $form->text('phone', ['placeholder' => trans('general.phone').'...', 'start' => 3], request()->input('phone'));

        $form->select2('status', ['' => 'Tất cả trạng thái', ...UserHelper::statusOption()], ['start' => 3], request()->input('status'));
        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_member_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {
        $form->text('keyword', ['placeholder' => trans('user.fullname').'...'], request()->input('keyword'));

        return apply_filters('admin_member_table_form_search', $form);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('green', [
            'icon' => Admin::icon('add'),
            'text' => 'Nhân viên',
            'href' => Url::route('admin.member.new'),
        ]);

        $buttons[] = Admin::button('reload');

        return $buttons;
    }

    public function queryFilter(Qr $query, \SkillDo\Http\Request $request): Qr
    {
        $query->where('isMember', 1);

        $keyword = trim($request->input('keyword'));

        if(!empty($keyword)) {
            $query->where(function ($qr) use ($keyword) {
                $qr->whereRaw('concat(firstname," ", lastname) like \'%'.$keyword.'%\'');
                $qr->orWhere('username', 'like', $keyword.'%');
                $qr->orWhere('username', 'like', '%'.$keyword);
            });
        }
        if(!empty($phone)) {
            $query->where('phone', 'like', '%'.$phone.'%');
        }
        if(!empty($email)) {
            $query->where('email', 'like', '%'.$email.'%');
        }

        return $query;
    }
}