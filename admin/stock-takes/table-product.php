<?php
namespace Stock\Table\StockTake;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;

class ProductAdd extends SKDObjectTable
{
    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['code'] = [
            'label'  => trans('Mã hàng'),
            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
        ];

        $this->_column_headers['product_name'] = [
            'label'  => trans('Tên hàng'),
            'column' => fn($item, $args) => ColumnText::make('product_name', $item, $args)
        ];

        $this->_column_headers['stock'] = [
            'label'  => trans('Tồn kho'),
            'column' => fn($item, $args) => ColumnText::make('stock', $item, $args)
        ];

        $this->_column_headers['quantity'] = [
            'label'  => trans('Số lượng'),
            'column' => fn($item, $args) => ColumnText::make('quantity', $item, $args)
        ];

        $this->_column_headers['adjustment_quantity'] = [
            'label'  => trans('SL lệch'),
            'column' => fn($item, $args) => ColumnText::make('adjustment_quantity', $item, $args)
        ];

        $this->_column_headers['adjustment_price'] = [
            'label'  => trans('Giá trị lệch'),
            'column' => fn($item, $args) => ColumnText::make('adjustment_price', $item, $args)
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        return [];
    }
}

class ProductDetail extends SKDObjectTable
{
    function getColumns() {

        $this->_column_headers = [];

        $this->_column_headers['code'] = [
            'label'  => trans('Mã hàng'),
            'column' => fn($item, $args) => ColumnText::make('product_code', $item, $args)
        ];

        $this->_column_headers['product_name'] = [
            'label'  => trans('Tên hàng'),
            'column' => fn($item, $args) => ColumnText::make('product_name', $item, $args)
        ];

        $this->_column_headers['stock'] = [
            'label'  => trans('Tồn kho'),
            'column' => fn($item, $args) => ColumnText::make('stock', $item, $args)
        ];

        $this->_column_headers['actual_quantity'] = [
            'label'  => trans('Thực tế'),
            'column' => fn($item, $args) => ColumnText::make('actual_quantity', $item, $args)
        ];

        $this->_column_headers['adjustment_quantity'] = [
            'label'  => trans('SL lệch'),
            'column' => fn($item, $args) => ColumnText::make('adjustment_quantity', $item, $args)
        ];

        $this->_column_headers['adjustment_price'] = [
            'label'  => trans('Giá trị lệch'),
            'column' => fn($item, $args) => ColumnText::make('adjustment_price', $item, $args)->number()
        ];

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        return [];
    }
}