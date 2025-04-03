<?php

use SkillDo\Table\Columns\ColumnText;

class StockCustomerAdminTable {

    static function header( $columns ): array
    {
        $columnsNew = [];

        foreach ($columns as $key => $column) {
            if($key == 'order_total') {
                $columnsNew['debt'] = [
                    'label' => 'Nợ hiện tại',
                    'column' => fn($item, $args) => ColumnText::make('debt', $item, $args)->number()
                ];
            }
            $columnsNew[$key] = $column;
        }

        return $columnsNew;
    }
}

add_filter( 'manage_user_columns', 'StockCustomerAdminTable::header');