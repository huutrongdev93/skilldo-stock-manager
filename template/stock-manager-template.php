<?php
Class ProductDetailInventory {

    public function __construct() {
        add_action('product_detail_info',  array($this, 'status'), 20, 1);
        add_action('product_detail_info',  array($this, 'script'), 100, 1);
    }

    public function status($object) {
        include_once 'status.php';
    }

    public function script($object) {
        $variations = Variation::gets(Qr::set('parent_id', $object->id));
        $stock_inventory_data = [];
        $stock_inventory_data[$object->id] = [
            'status' => $object->stock_status,
            'label' => Inventory::status($object->stock_status,'label')
        ];
        foreach ($variations as $variation) {
            if(!empty($variation->stock_status)) {
                $stock_inventory_data[$variation->id] = [
                    'status' => $variation->stock_status,
                    'label' => Inventory::status($variation->stock_status,'label')
                ];
            }
        }
        include_once 'script.php';
    }
}

new ProductDetailInventory();