<?php
Class Inventory extends Model {

    static string $table = 'inventories';

    static public function insert( $inventories = [] ) {

        if (!empty($inventories['id']) ) {

            $id             = (int) $inventories['id'];

            $update        = true;

            $old_inventories = static::get($id);

            if(!$old_inventories) return new SKD_Error( 'invalid_inventories_id', __( 'ID bài viết không chính xác.'));

            $inventories['product_id']   =  (!empty($inventories['product_id'])) ? Str::clear($inventories['product_id']) : $old_inventories->product_id;

            $inventories['parent_id']   =  (!empty($inventories['parent_id'])) ? Str::clear($inventories['parent_id']) : $old_inventories->parent_id;

            $inventories['product_name']   =  (!empty($inventories['product_name'])) ? Str::clear($inventories['product_name']) : $old_inventories->product_name;

            $inventories['product_code']   =  (!empty($inventories['product_code'])) ? Str::clear($inventories['product_code']) : $old_inventories->product_code;

            $inventories['branch_id']   =  (!empty($inventories['branch_id'])) ? Str::clear($inventories['branch_id']) : $old_inventories->branch_id;

            $inventories['branch_name'] =  (!empty($inventories['branch_name'])) ? Str::clear($inventories['branch_name']) : $old_inventories->branch_name;

            $inventories['stock']    =  (isset($inventories['stock'])) ? Str::clear($inventories['stock']) : $old_inventories->stock;

            $inventories['reserved'] =  (isset($inventories['reserved'])) ? Str::clear($inventories['reserved']) : $old_inventories->reserved;

            $inventories['status']    =  (!empty($inventories['status'])) ? Str::clear($inventories['status']) : $old_inventories->status;
        }
        else {
            $update = false;
        }

        $product_id =  (!empty($inventories['product_id'])) ? (int)Str::clear($inventories['product_id']) : 0;

        $parent_id =  (!empty($inventories['parent_id'])) ? (int)Str::clear($inventories['parent_id']) : 0;

        $product_name  =  (!empty($inventories['product_name'])) ? Str::clear($inventories['product_name']) : '';

        $product_code  =  (!empty($inventories['product_code'])) ? Str::clear($inventories['product_code']) : '';

        $branch_id =  (!empty($inventories['branch_id'])) ? (int)Str::clear($inventories['branch_id']) : 0;

        $branch_name =  (!empty($inventories['branch_name'])) ? Str::clear($inventories['branch_name']) : '';

        $stock =  (isset($inventories['stock'])) ? (int)Str::clear($inventories['stock']) : 0;

        $reserved    =  (isset($inventories['reserved'])) ? (int)Str::clear($inventories['reserved']) : $stock;

        $status  =  (!empty($inventories['status'])) ? Str::clear($inventories['status']) : 'instock';

        $data = compact( 'product_id', 'parent_id', 'product_name', 'product_code', 'branch_id', 'branch_name', 'stock', 'reserved', 'status');

        $data = apply_filters( 'pre_insert_inventory_data', $data, $inventories, $update ? $old_inventories : null );

        $model = model(static::$table);

        if ($update) {

            $model->update( $data, Qr::set($id));
        }
        else {
            $id = $model->add($data);
        }

        return $id;
    }

    static public function update($update, $args = []) {

        if(is_array($args)) $args = Qr::convert($args);

        $inventory_old = static::get($args);

        if(have_posts($inventory_old)) {
            $update['id'] = $inventory_old->id;
        }

        if(!isset($update['status']) && isset($update['stock'])) {
            if($update['stock'] == 0) {
                $update['status'] = 'outstock';
            }
            else {
                $update['status'] = 'instock';
            }
        }

        $result = static::insert($update);

        if(!is_skd_error($result)) {

            if(!have_posts($inventory_old)) {
                $inventory_old = static::get($result);
            }

            $product = Product::get(Qr::set($inventory_old->product_id)->where('type', '<>', 'null'));

            if(have_posts($product)) {

                $count = model('inventories')->gets(Qr::set('product_id', $inventory_old->product_id))->sum('stock');

                $stock_status = (!empty($count)) ? 'instock' : 'outstock';

                if($product->stock_status != $stock_status) {
                    model('products')->update(['stock_status' => $stock_status], Qr::set($product->id));
                }
            }
        }

        return $result;
    }

    static public function delete( $inventoriesID = 0) {

        $ci =& get_instance();

        $inventoriesID = (int)Str::clear($inventoriesID);

        if( $inventoriesID == 0 ) return false;

        $model = model(static::$table);

        $inventories  = static::get($inventoriesID);

        if(have_posts($inventories)) {

            $ci->data['module']   = static::$table;

            do_action('delete_'.static::$table, $inventoriesID);

            if($model->delete(Qr::set($inventoriesID))) {
                do_action('delete_'.static::$table.'_success', $inventoriesID );
                //delete galleries
                Metadata::deleteByMid(static::$table, $inventoriesID);
                return [$inventoriesID];
            }
        }

        return false;
    }

    static public function deleteList( $inventoriesID = []) {

        if(have_posts($inventoriesID)) {

            $model      = model(static::$table);

            $inventories = static::gets(Qr::set()->whereIn('id', $inventoriesID));

            if($model->delete(Qr::set()->whereIn('id', $inventoriesID))) {

                $args = Qr::set('object_type', 'inventories')->whereIn('object_id', $inventoriesID);

                do_action('delete_inventories_list_trash_success', $inventoriesID );

                //delete language
                $model->settable('language')->delete($args);

                //delete router
                $model->settable('routes')->delete($args);

                //delete router
                foreach ($inventories as $inventory) {
                    Gallery::deleteItemByObject($inventory->id, 'inventories');
                    Metadata::deleteByMid('inventories', $inventory->id);
                }

                //delete menu
                $model->settable('menu')->delete($args);

                //xóa liên kết
                $model->settable('relationships')->delete($args);

                return $inventoriesID;
            }
        }

        return false;
    }

    static public function status($key = '', $type = '') {
        $status = [
            'instock' => [
                'label' => __('Còn hàng', 'instock'),
                'color' => '#BAE0BD',
            ],
            'outstock' => [
                'label' => __('Hết hàng', 'outstock'),
                'color' => '#ffc1c1',
            ],
            'onbackorder' => [
                'label' => __('Không còn kinh doanh', 'onbackorder'),
                'color' => '#eaa600',
            ],
        ];
        if(!empty($key) && !empty($type) && isset($status[$key])) {
            if(!empty($status[$key][$type])) return apply_filters('inventory_status_'.$type, $status[$key][$type], $key, $type);
            return apply_filters( 'inventory_status', $status[$key], $key, $type);
        }
        return apply_filters( 'inventory_status', $status, $key);
    }
}