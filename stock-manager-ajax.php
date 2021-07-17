<?php
Class Stock_Manager_Ajax {
    static public function inventoryLoad($ci, $model) {

        $result['message'] = 'Cập nhật dữ liệu thất bại.';

        $result['status'] = 'error';

        if (InputBuilder::Post()) {

            $current_item = (int)InputBuilder::Post('currentItem') - 1;

            $page_size    = 20;

            $args = [
                'params' => ['limit' => $page_size, 'start' => $page_size*$current_item]
            ];

            $where = [];

            $keyword = trim(InputBuilder::Post('keyword'));
            if(!empty($keyword)) $args['where_like'] = ['product_name' => [$keyword]];

            $branch_id = (int)InputBuilder::Post('branch');
            if($branch_id == 0) $branch_id = 1;
            $where['branch_id'] = $branch_id;

            $stock_status = InputBuilder::Post('status');
            if(!empty($stock_status)) $where['status'] = $stock_status;

            $args['where'] = $where;

            $inventories = Inventory::gets($args);

            foreach ($inventories as &$inventory) {
                $inventory->status_color = Inventory::status($inventory->status, 'color');
                $inventory->status_label = Inventory::status($inventory->status, 'label');
            }

            $result['list'] = $inventories;

            $result['pagination'] = '';

            unset($args['params']);

            $total = Inventory::count($args);

            $config  = array (
                'current_page'  => $current_item + 1,
                'total_rows'    => $total,
                'number'		=> $page_size,
                'url'           => '',
            );

            $pagination = new paging($config);

            $result['pagination'] = $pagination->html_fontend();

            $result['status'] = 'success';

            $result['message'] = 'Load dữ liệu thành công';

        }

        echo json_encode($result);

        return true;
    }
    static public function inventoryUpdate( $ci, $model ) {

        $result['status']  = 'error';

        $result['message'] = __('Lưu dữ liệu không thành công');

        if(InputBuilder::Post()) {

            if(!Auth::hasCap('inventory_edit')) {
                $result['message'] = 'Bạn không có quyền sử dụng chức năng này';
                echo json_encode($result);
                return true;
            }

            $inventory_update = [];

            $inventory      = InputBuilder::Post('inventory');

            $id             = (int)$inventory['id'];

            $inventory_old  = Inventory::get($id);

            if(!have_posts($inventory_old)) {
                $result['message'] = __('Tồn kho chưa tồn tại'); echo json_encode($result); return false;
            }

            $inventory_update['id'] = $inventory_old->id;

            if($inventory['type'] == 1) {

                $inventory_update['stock'] = $inventory_old->stock + $inventory['stock'];
            }
            else {

                $inventory_update['stock'] = $inventory['stock'];
            }

            if($inventory_update['stock'] < 0) $inventory_update['stock'] = 0;

            if(!is_skd_error(Inventory::update($inventory_update))) {

                $result['inventory']     = Inventory::get($id);

                $result['status']  = 'success';

                $result['message'] = __('Lưu dữ liệu thành công');
            }
        }

        echo json_encode($result);

        return false;
    }
}
Ajax::admin('Stock_Manager_Ajax::inventoryLoad');
Ajax::admin('Stock_Manager_Ajax::inventoryUpdate');


