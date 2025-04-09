<?php
namespace Skdepot\Table\Transfer;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;

class ProductSendAdd extends SKDObjectTable
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

        $this->_column_headers['send_quantity'] = [
            'label'  => trans('SL chuyển'),
            'column' => fn($item, $args) => ColumnText::make('send_quantity', $item, $args)
        ];

        $this->_column_headers['price'] = [
            'label'  => trans('Giá chuyển'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)
        ];

        $this->_column_headers['send_price'] = [
            'label'  => trans('Thành tiền'),
            'column' => fn($item, $args) => ColumnText::make('send_price', $item, $args)
        ];

        $this->_column_headers['action']   = trans('table.action');

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        return [];
    }
}

class ProductReceiveAdd extends SKDObjectTable
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

        $this->_column_headers['send_quantity'] = [
            'label'  => trans('SL chuyển'),
            'column' => fn($item, $args) => ColumnText::make('send_quantity', $item, $args)
        ];

        $this->_column_headers['receive_quantity'] = [
            'label'  => trans('SL nhận'),
            'column' => fn($item, $args) => ColumnText::make('receive_quantity', $item, $args)
        ];

        $this->_column_headers['price'] = [
            'label'  => trans('Giá chuyển'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)
        ];

        $this->_column_headers['receive_price'] = [
            'label'  => trans('Thành tiền'),
            'column' => fn($item, $args) => ColumnText::make('receive_price', $item, $args)
        ];

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

        $this->_column_headers['send_quantity'] = [
            'label'  => trans('Số lượng chuyển'),
            'column' => fn($item, $args) => ColumnText::make('send_quantity', $item, $args)
        ];

        $this->_column_headers['receive_quantity'] = [
            'label'  => trans('Số lượng nhận'),
            'column' => fn($item, $args) => ColumnText::make('receive_quantity', $item, $args)
        ];

        $this->_column_headers['price'] = [
            'label'  => trans('Giá chuyển/nhận'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)->number()
        ];

        $this->_column_headers['send_price'] = [
            'label'  => trans('Thành tiền chuyển'),
            'column' => fn($item, $args) => ColumnText::make('send_price', $item, $args)->number()
        ];

        $this->_column_headers['receive_price'] = [
            'label'  => trans('Thành tiền nhận'),
            'column' => fn($item, $args) => ColumnText::make('receive_price', $item, $args)->number()
        ];

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        return [];
    }
}