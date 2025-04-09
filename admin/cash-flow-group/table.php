<?php
namespace Skdepot\Table;

use Admin;
use SkillDo\Http\Request;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;
use Url;

class CashFlowGroup extends SKDObjectTable
{
    protected string $module = 'cash_flow_group';

    protected mixed $model = \Skdepot\Model\CashFlowGroup::class;

    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['cb'] = 'cb';

        $this->_column_headers['name'] = [
            'label'  => trans('Tên nhóm'),
            'column' => fn($item, $args) => ColumnText::make('name', $item, $args)
        ];

        $this->_column_headers['created'] = [
            'label'  => trans('Thời gian'),
            'column' => fn($item, $args) => ColumnText::make('created', $item, $args)->datetime('d/m/Y h:s')
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }
}

class CashFlowGroupReceipt extends CashFlowGroup
{
    protected string $module = 'cash_flow_group_receipt';

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $buttons[] = Admin::button('blue', [
            'href' => Url::route('admin.cashFlow.group.receipt.edit', ['id' => $item->id]),
            'icon' => Admin::icon('edit')
        ]);

        $buttons[] = Admin::btnDelete([
            'id' => $item->id,
            'model' => \Skdepot\Model\CashFlowGroup::class,
            'description' => trans('message.page.confirmDelete', ['title' => html_escape($item->name)])
        ]);

        return apply_filters('admin_'.$this->module.'_table_columns_action', $buttons);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::route('admin.cashFlow.group.receipt.new')]);

        $buttons[] = Admin::button('reload');

        return $buttons;
    }

    public function queryFilter(\Qr $query, Request $request): \Qr
    {
        $query->where('type', 'receipt');

        $keyword = $request->input('keyword');

        if (!empty($keyword))
        {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        return $query;
    }
}

class CashFlowGroupPayment extends CashFlowGroup
{
    protected string $module = 'cash_flow_group_payment';

    function actionButton($item, $module, $table): array
    {
        $buttons = [];

        $buttons[] = Admin::button('blue', [
            'href' => Url::route('admin.cashFlow.group.payment.edit', ['id' => $item->id]),
            'icon' => Admin::icon('edit')
        ]);

        $buttons[] = Admin::btnDelete([
            'id' => $item->id,
            'model' => \Skdepot\Model\CashFlowGroup::class,
            'description' => trans('message.page.confirmDelete', ['title' => html_escape($item->name)])
        ]);

        return apply_filters('admin_'.$this->module.'_table_columns_action', $buttons);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::route('admin.cashFlow.group.payment.new')]);

        $buttons[] = Admin::button('reload');

        return $buttons;
    }

    public function queryFilter(\Qr $query, Request $request): \Qr
    {
        $query->where('type', 'payment');

        $keyword = $request->input('keyword');

        if (!empty($keyword))
        {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        return $query;
    }
}