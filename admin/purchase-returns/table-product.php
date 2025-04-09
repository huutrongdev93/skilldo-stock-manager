<?php
namespace Skdepot\Table\PurchaseReturn;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;

class ProductAdd extends SKDObjectTable
{
    function getColumns() {

        $this->_column_headers = [
            'code'          => trans('Mã hàng'),
            'product_name'  => trans('Tên hàng'),
            'quantity'      => trans('Số lượng'),
            'cost'         => trans('Giá nhập'),
            'price'         => trans('Giá trả lại'),
            'subtotal'         => trans('Thành tiền'),
            'action'        => trans('table.action')
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

        $this->_column_headers['product_code'] = [
            'label'  => trans('Mã hàng'),
            'column' => fn($item, $args) => ColumnText::make('product_code', $item, $args)
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
            'label'  => trans('Giá trả lại'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)->number()
        ];

        $this->_column_headers['cost'] = [
            'label'  => trans('Giá nhập'),
            'column' => fn($item, $args) => ColumnText::make('cost', $item, $args)->number()
        ];

        $this->_column_headers['subtotal'] = [
            'label'  => trans('Thành tiền'),
            'column' => fn($item, $args) => ColumnText::make('subtotal', $item, $args)->number()
        ];

        return $this->_column_headers;
    }
}