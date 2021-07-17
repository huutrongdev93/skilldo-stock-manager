<?php
Class Stock_Manager_Role {
    static public function group( $group ) {
        $group['inventory'] = [
            'label' => __('Kho hàng'),
            'capbilities' => array_keys(Stock_Manager_Role::capabilities())
        ];
        return $group;
    }
    static public function label( $label ) {
        $label = array_merge( $label, Stock_Manager_Role::capabilities() );
        return $label;
    }
    static public function capabilities() {
        $label['branch_list']      = 'Xem thông tin chi nhánh';
        $label['branch_add']       = 'Thêm chi nhánh';
        $label['branch_edit']      = 'Cập nhật thông tin chi nhánh';

        $label['inventory_list']   = 'Xem thông tin kho';
        $label['inventory_edit']   = 'Cập nhật kho hàng';

        return $label;
    }
}
add_filter( 'user_role_editor_group', 'Stock_Manager_Role::group');
add_filter( 'user_role_editor_label', 'Stock_Manager_Role::label');
