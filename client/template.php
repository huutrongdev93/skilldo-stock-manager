<?php
Class ProductDetailInventory {

    public function __construct() {
        add_action('controllers_product_detail_data', 	'ProductDetailInventory::productDetailData', 30);
        add_action('product_detail_info',  'ProductDetailInventory::status', 1, 1);
    }

    static function productDetailData($object): void
    {
        $branch = \Stock\Helper::getBranchWebsite();

        if(have_posts($object) && have_posts($branch))
        {
            if($object->hasVariation == 0)
            {
                $inventory = \Stock\Model\Inventory::where('branch_id', $branch->id)
                    ->where('product_id', $object->id)
                    ->first();

                $object->inventory = $inventory;

                $object->stock_status = $inventory->status;

                if($object->stock_status == \Stock\Status\Inventory::out->value)
                {
                    $object->isActivePurchase = false;
                }
            }
            else
            {
                $inventories = \Stock\Model\Inventory::where('branch_id', $branch->id)
                    ->where('parent_id', $object->id)
                    ->get()
                    ->keyBy('product_id');

                $stockStatus = [];

                foreach($object->variations as $variation) {

                    $inventory = $inventories[$variation->id];

                    $variation->inventory = $inventory;

                    $variation->stock_status = $inventory->status;

                    if($variation->stock_status == \Stock\Status\Inventory::out->value)
                    {
                        foreach($object->variationAttributes as $variationAttributeKey => $variationAttributeValue)
                        {
                            if (empty(array_diff($variationAttributeValue, $variation->attributes['listId'])) && empty(array_diff($variation->attributes['listId'], $variationAttributeValue)))
                            {
                                unset($object->variationAttributes[$variationAttributeKey]);
                            }
                        }
                    }
                    else {
                        $stockStatus[$variation->id] = $variation->id;
                    }
                }

                $object->variationAttributes = array_values($object->variationAttributes);

                if(have_posts($stockStatus))
                {

                    $object->product_default_id = $stockStatus[$object->product_default_id] ?? Arr::first($stockStatus);

                    foreach ($object->variations as $variation) {

                        if($variation->id == $object->product_default_id) {
                            $object->price      = $variation->price;
                            $object->price_sale = $variation->price_sale;
                            $object->product_default = $variation;
                            $object->attributes_default = $variation->attributes['listId'];
                            $object->stock_status = $variation->stock_status;
                            break;
                        }
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