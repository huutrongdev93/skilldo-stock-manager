<?php
Class ProductDetailInventory {

    public function __construct() {
        add_action('controllers_product_detail_data', 	'ProductDetailInventory::productDetailData', 30);
        add_action('product_detail_info',  'ProductDetailInventory::status', 1, 1);
    }

    static function productDetailData($object): void
    {
        if(have_posts($object) && $object->hasVariation == 1) {

            if($object->stock_status == 'outstock') {

                $object->product_default_id = 0;

                $object->isActivePurchase = false;

                foreach ($object->attributes as $key => $attribute) {
                    $object->attributes[$key]->isActive = false;
                }
            }

            $stockStatus = [];

            foreach($object->variations as $variation) {

                if($variation->stock_status == 'outstock') {

                    foreach($object->variationAttributes as $variationAttributeKey => $variationAttributeValue) {

                        if (empty(array_diff($variationAttributeValue, $variation->attributes['listId'])) && empty(array_diff($variation->attributes['listId'], $variationAttributeValue))) {
                            unset($object->variationAttributes[$variationAttributeKey]);
                        }
                    }
                }
                else {
                    $stockStatus[$variation->id] = $variation->id;
                }
            }

            $object->variationAttributes = array_values($object->variationAttributes);

            if(have_posts($stockStatus)) {

                $object->product_default_id = $stockStatus[$object->product_default_id] ?? Arr::first($stockStatus);

                foreach ($object->variations as $variation) {

                    if($variation->id == $object->product_default_id) {
                        $object->price      = $variation->price;
                        $object->price_sale = $variation->price_sale;
                        $object->product_default = $variation;
                        $object->attributes_default = $variation->attributes['listId'];
                        break;
                    }
                }
            }
        }
    }

    static function status($object): void
    {
        Plugin::view('stock-manager', 'status', [
            'object' => $object
        ]);
    }
}

new ProductDetailInventory();