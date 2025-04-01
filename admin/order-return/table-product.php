<?php
namespace Stock\Table\OrderReturn;
use SkillDo\Table\Columns\ColumnText;
use SkillDo\Table\SKDObjectTable;

class ProductAdd extends SKDObjectTable
{
    function getColumns() {

        $this->_column_headers = [
            'code' => trans('Mã hàng'),
            'product_name' => trans('Tên hàng'),
            'quantity' => trans('Số lượng trả'),
            'price' => trans('Giá nhập lại'),
            'sub_total' => trans('Thành tiền'),
        ];

//        $this->_column_headers['code'] = [
//            'label'  => trans('Mã hàng'),
//            'column' => fn($item, $args) => ColumnText::make('code', $item, $args)
//        ];

//        $this->_column_headers['product_name'] = [
//            'label'  => trans('Tên hàng'),
//            'column' => fn($item, $args) => ColumnText::make('product_name', $item, $args)
//        ];

//        $this->_column_headers['quantity'] = [
//            'label'  => trans('Số lượng trả'),
//            'column' => fn($item, $args) => ColumnText::make('quantity', $item, $args)
//        ];

//        $this->_column_headers['price'] = [
//            'label'  => trans('Giá nhập lại'),
//            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)
//        ];
//
//        $this->_column_headers['sub_total'] = [
//            'label'  => trans('Thành tiền'),
//            'column' => fn($item, $args) => ColumnText::make('sub_total', $item, $args)
//        ];

        return $this->_column_headers;
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

        $this->_column_headers['quantity'] = [
            'label'  => trans('Số lượng'),
            'column' => fn($item, $args) => ColumnText::make('quantity', $item, $args)
        ];

        $this->_column_headers['price_sell'] = [
            'label'  => trans('Giá bán'),
            'column' => fn($item, $args) => ColumnText::make('price_sell', $item, $args)->number()
        ];

        $this->_column_headers['price'] = [
            'label'  => trans('Giá nhập lại'),
            'column' => fn($item, $args) => ColumnText::make('price', $item, $args)->number()
        ];

        $this->_column_headers['sub_total'] = [
            'label'  => trans('Thành tiền'),
            'column' => fn($item, $args) => ColumnText::make('sub_total', $item, $args)->number()
        ];

        return $this->_column_headers;
    }

    function actionButton($item, $module, $table): array
    {
        return [];
    }
}