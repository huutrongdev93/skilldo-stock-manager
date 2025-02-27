<?php
namespace Stock\Model;

use SkillDo\DB;

Class StockTakeDetail extends \SkillDo\Model\Model
{
    protected string $table = 'stock_take_details';

    protected string $primaryKey = 'stock_take_detail_id';
}