<?php
namespace Stock\Table\DamageItems;
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

        $this->_column_headers['quantity'] = [
            'label'  => trans('Số lượng'),
            'column' => fn($item, $args) => ColumnText::make('quantity', $item, $args)
        ];

        $this->_column_headers['price'] = [
            'label'  => trans('Giá vốn'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)
        ];

        $this->_column_headers['sub_total'] = [
            'label'  => trans('Giá trị'),
            'column' => fn($item, $args) => ColumnText::make('sub_total', $item, $args)
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

        $this->_column_headers['price'] = [
            'label'  => trans('Giá vốn'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)->number()
        ];

        $this->_column_headers['quantity'] = [
            'label'  => trans('Số lượng'),
            'column' => fn($item, $args) => ColumnText::make('quantity', $item, $args)
        ];

        $this->_column_headers['total'] = [
            'label'  => trans('Thành tiền'),
            'column' => fn($item, $args) => ColumnText::make('total', $item, $args)->value(function ($item) {
                return $item->quantity*$item->price;
            })->number()
        ];

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        return [];
    }
}