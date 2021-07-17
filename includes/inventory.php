<?php
Class Inventory {

    static public function get($args = []) {

        $model = get_model()->settable('inventories')->settable_metabox('metabox');

        if(is_numeric($args)) $args = array( 'where' => array('id' => (int)$args));

        if(!have_posts($args)) $args = [];

        $args = array_merge( array('where' => [], 'params' => [] ), $args );

        $inventories = $model->get_data( $args, 'Inventory' );

        return apply_filters('get_branch', $inventories, $args);
    }

    static public function getBy( $field, $value, $params = [] ) {

        $field = Str::clear( $field );

        $value = Str::clear( $value );

        $args = array( 'where' => array( $field => $value));

        if(have_posts($params)) $arg['params'] = $params;

        return apply_filters('get_inventories_by', static::get($args), $field, $value );
    }

    static public function gets( $args = [] ) {

        $model 	= get_model()->settable('inventories')->settable_metabox('metabox');

        if(!have_posts($args)) $args = [];

        $args = array_merge(['where' => [], 'params' => []], $args );

        $inventories = $model->gets_data($args, 'branch');

        return apply_filters( 'gets_inventories', $inventories, $args );
    }

    static public function getsBy( $field, $value, $params = [] ) {

        $field = Str::clear( $field );

        $value = Str::clear( $value );

        $args = ['where' => array( $field => $value )];

        if( have_posts($params) ) $arg['params'] = $params;

        return apply_filters( 'gets_inventories_by', static::gets($args), $field, $value );
    }

    static public function count( $args = [] ) {

        if( is_numeric($args) ) $args = array( 'where' => array('id' => (int)$args));

        if( !have_posts($args) ) $args = [];

        $args = array_merge( array('where' => [], 'params' => [] ), $args );

        $model = get_model()->settable('inventories')->settable_metabox('inventories_metadata');

        $inventories = $model->count_data($args, 'inventories');

        return apply_filters('count_inventories', $inventories, $args );
    }

    static public function insert( $inventories = [] ) {

        $model = get_model()->settable('inventories');

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

        if ($update) {

            $model->settable('inventories')->update_where( $data, compact( 'id'));

            $inventories_id = (int) $id;
        }
        else {
            $inventories_id = $model->settable('inventories')->add( $data );
        }

        return $inventories_id;
    }

    static public function update($inventory = []) {

        if(empty($inventory['id'])) {
            $inventory_old = static::get(['where' => ['product_id' => $inventory['product_id'],'branch_id' => $inventory['branch_id'],]]);
        }
        else {
            $inventory_old = static::get($inventory['id']);
        }

        if(have_posts($inventory_old)) {
            $inventory['id']        = $inventory_old->id;
        }

        if(!isset($inventory['status']) && $inventory['stock'] == 0) {
            $inventory['status'] = 'outstock';
        }
        else {
            $inventory['status'] = 'instock';
        }

        $result = static::insert($inventory);

        if(!is_skd_error($result)) {

            if(!have_posts($inventory_old)) {

                $inventory_old = static::get($result);
            }

            $product = Product::get(['where' => ['id' => $inventory_old->product_id, 'type <>' => 'trash']]);

            if(have_posts($product)) {

                $count = get_model()->settable('inventories')->operatorby(['product_id' => $inventory_old->product_id], 'stock');

                $stock_status = (!empty((int)$count->stock)) ? 'instock' : 'outstock';

                if($product->stock_status != $stock_status) {

                    get_model()->settable('products')->update_where(['stock_status' => $stock_status], ['id' => $product->id]);
                }
            }
        }

        return $result;
    }

    static public function delete( $inventoriesID = 0) {

        $ci =& get_instance();

        $inventoriesID = (int)Str::clear($inventoriesID);

        if( $inventoriesID == 0 ) return false;

        $model = get_model('home')->settable('inventories');

        $inventories  = static::get( $inventoriesID );

        if(have_posts($inventories) ) {

            $ci->data['module']   = 'inventories';

            do_action('delete_inventories', $inventoriesID );

            if($model->delete_where(['id'=> $inventoriesID])) {
                do_action('delete_inventories_success', $inventoriesID );
                //delete gallerys
                Metadata::deleteByMid('inventories', $inventoriesID);
                return [$inventoriesID];
            }
        }

        return false;
    }

    static public function deleteList( $inventoriesID = []) {

        if(have_posts($inventoriesID)) {

            $model      = get_model('home')->settable('inventories');

            $inventoriess = static::gets(['where_in' => ['field' => 'id', 'data' => $inventoriesID]]);

            if($model->delete_where_in(['field' => 'id', 'data' => $inventoriesID])) {

                $where_in = ['field' => 'object_id', 'data' => $inventoriesID];

                do_action('delete_inventories_list_trash_success', $inventoriesID );

                //delete language
                $model->settable('language')->delete_where_in($where_in, ['object_type' => 'inventories']);

                //delete router
                $model->settable('routes')->delete_where_in($where_in, ['object_type' => 'inventories']);

                //delete router
                foreach ($inventoriess as $key => $inventories) {
                    Gallery::deleteItemByObject($inventories->id, 'inventories');
                    Metadata::deleteByMid('inventories', $inventories->id);
                }

                //delete menu
                $model->settable('menu')->delete_where_in($where_in, ['object_type' => 'inventories']);

                //xóa liên kết
                $model->settable('relationships')->delete_where_in($where_in, ['object_type' => 'inventories']);

                return $inventoriesID;
            }
        }

        return false;
    }

    static public function getMeta( $inventories_id, $key = '', $single = true) {
        return Metadata::get('inventory', $inventories_id, $key, $single);
    }

    static public function updateMeta($inventories_id, $meta_key, $meta_value) {
        return Metadata::update('inventory', $inventories_id, $meta_key, $meta_value);
    }

    static public function deleteMeta($inventories_id, $meta_key = '', $meta_value = '') {
        return Metadata::delete('inventory', $inventories_id, $meta_key, $meta_value);
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