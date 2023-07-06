<?php
Class Stock_Manager_Ajax {
    static public function inventoryLoad($ci, $model) {

        $result['message'] = 'Cập nhật dữ liệu thất bại.';

        $result['status'] = 'error';

        if (Request::Post()) {

            $current_item = (int)Request::Post('currentItem') - 1;

            $limit = 20;

            $args = Qr::set();

            $keyword = trim(Request::Post('keyword'));

            if(!empty($keyword)) {
                $args->where('product_name', 'like', '%'.$keyword.'%');
            }

            $branch_id = (int)Request::Post('branch');

            if($branch_id == 0) $branch_id = 1;

            $args->where('branch_id', $branch_id);

            $stock_status = Request::Post('status');

            if(!empty($stock_status)) {
                $args->where('status', $stock_status);
            }

            $total = Inventory::count($args);

            $config  = array (
                'currentPage'   => $current_item + 1,
                'totalRecords'  => $total,
                'limit'		    => $limit,
                'url'           => '#',
            );

            $pagination = new Pagination($config);

            $args->limit($limit)->offset($limit*$current_item);

            $inventories = Inventory::gets($args);

            foreach ($inventories as &$inventory) {
                $inventory->status_color = Inventory::status($inventory->status, 'color');
                $inventory->status_label = Inventory::status($inventory->status, 'label');
            }

            $result['list'] = $inventories;

            $result['pagination'] = $pagination->backend();

            $result['status'] = 'success';

            $result['message'] = 'Load dữ liệu thành công';

        }

        echo json_encode($result);

        return true;
    }
    static public function inventoryUpdate( $ci, $model ) {

        $result['status']  = 'error';

        $result['message'] = __('Lưu dữ liệu không thành công');

        if(Request::Post()) {

            if(!Auth::hasCap('inventory_edit')) {
                $result['message'] = 'Bạn không có quyền sử dụng chức năng này';
                echo json_encode($result);
                return true;
            }

            $inventory_update = [];

            $inventory      = Request::Post('inventory');

            $id             = (int)$inventory['id'];

            $inventory_old  = Inventory::get($id);

            if(!have_posts($inventory_old)) {
                $result['message'] = __('Tồn kho chưa tồn tại'); echo json_encode($result); return false;
            }

            if($inventory['type'] == 1) {
                $inventory_update['stock'] = $inventory_old->stock + $inventory['stock'];
            }
            else {
                $inventory_update['stock'] = $inventory['stock'];
            }

            if($inventory_update['stock'] < 0) $inventory_update['stock'] = 0;

            if(!is_skd_error(Inventory::update(
                $inventory_update,
                Qr::set($inventory_old->id),
                'inventory_update'
            ))) {

                $result['inventory']     = Inventory::get($id);

                $result['status']  = 'success';

                $result['message'] = __('Lưu dữ liệu thành công');
            }
        }

        echo json_encode($result);

        return false;
    }
    static public function inventoryHistory($ci, $model) {

        $result['message'] = 'Cập nhật dữ liệu thất bại.';

        $result['status'] = 'error';

        if (Request::post()) {

            $id = (int)Request::post('id');

            $inventories = InventoryHistory::gets(Qr::set('inventory_id', $id));

            $result['list'] = '';

            foreach ($inventories as $inventory) {
                $result['list'] .= '<p>'.$inventory->created.' : '.$inventory->message.'</p>';
            }

            $result['status'] = 'success';

            $result['message'] = 'Load dữ liệu thành công';

        }

        echo json_encode($result);

        return true;
    }
    static public function quickEditSave($ci, $model) {

        $result['message'] = 'Cập nhật dữ liệu thất bại.';

        $result['status'] = 'error';

        if (Request::post()) {

            $id = Request::post('id');

            $product = Product::get($id);

            if(have_posts($product)) {

                $productStock = Request::post('productStock');

                $stock = 0;

                foreach ($productStock as $branchId => $dataStock) {

                    $branch = Branch::get($branchId);

                    foreach ($dataStock as $productId => $item) {

                        $count = Inventory::count(Qr::set('product_id', $productId)->where('branch_id', $branchId));

                        if($count == 0) {

                            $inventoryAdd = [
                                'product_name'  => $product->title,
                                'product_code'  => $product->code,
                                'product_id'    => $product->id,
                                'parent_id'     => 0,
                                'branch_id'     => $branch->id,
                                'branch_name'   => $branch->name,
                                'stock'         => $item['stock'],
                            ];

                            if($productId != $product->id) {

                                $variation = Variation::get(Qr::set($productId));

                                $attr_name = '';

                                foreach ($variation->items as $attr_id) {
                                    $attribute = Attributes::getItem($attr_id);
                                    $attr_name .= ' - '.$attribute->title;
                                }

                                $inventoryAdd['product_name']   .= $attr_name;
                                $inventoryAdd['product_code']   = $variation->code;
                                $inventoryAdd['product_id']     = $variation->id;
                                $inventoryAdd['parent_id']      = $product->id;
                            }

                            $stock += $item['stock'];

                            Inventory::insert($inventoryAdd);
                        }
                        else {
                            Inventory::update(
                                ['stock' => $item['stock']],
                                Qr::set('product_id', $productId)->where('branch_id', $branchId),
                                'product_update_quick'
                            );

                            $stock += $item['stock'];
                        }
                    }
                }

                if($stock > 0) {
                    model('products')->update(['stock_status' => 'instock'], Qr::set($product->id));
                }
                else {
                    model('products')->update(['stock_status' => 'outstock'], Qr::set($product->id));
                }

                $result['status']   = 'success';

                $result['message']  = 'Cập nhật dữ liệu thành công';

                $result['data']     = $productStock;
            }
        }

        echo json_encode($result);

        return true;
    }
}
Ajax::admin('Stock_Manager_Ajax::inventoryLoad');
Ajax::admin('Stock_Manager_Ajax::inventoryUpdate');
Ajax::admin('Stock_Manager_Ajax::inventoryHistory');
Ajax::admin('Stock_Manager_Ajax::quickEditSave');


