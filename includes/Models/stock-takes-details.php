<?php
namespace Skdepot\Model;

use SkillDo\DB;

Class StockTakeDetail extends \SkillDo\Model\Model
{
    protected string $table = 'skdepot_stock_take_details';

    protected string $primaryKey = 'stock_take_detail_id';
}