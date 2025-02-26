<?php
namespace Stock\Model;

Class PurchaseOrderDetail extends \SkillDo\Model\Model
{
    protected string $table = 'inventories_purchase_orders_details';

    protected string $primaryKey = 'purchase_order_detail_id';
}