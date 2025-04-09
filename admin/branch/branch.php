<?php

use SkillDo\Table\Columns\ColumnView;

class StockBranchCustom
{
    static function tableHeader( $columns ): array
    {
        $columnsNew = [];

        foreach ($columns as $key => $column) {
            $columnsNew[$key] = $column;
            if($key == 'isDefault') {
                $columnsNew['isWebsite'] = [
                    'label' => 'Website',
                    'column' => fn($item, $args) => ColumnView::make('isWebsite', $item, $args)->html(function ($column) {
                        echo '<div class="form-check"><input type="radio" class="js_branch_btn_website form-check-input" data-id="'.$column->item->id.'" '.($column->item->id == \Skdepot\Config::get('website') ? 'checked' : '').'></div>';
                    }),
                ];
            }
        }

        return $columnsNew;
    }

    static function tableScript(): void
    {
        Plugin::view(SKDEPOT_NAME, 'admin/branch/index');
    }
}

add_filter( 'manage_branch_columns', 'StockBranchCustom::tableHeader');
add_action( 'admin_after_branch_table_view', 'StockBranchCustom::tableScript');