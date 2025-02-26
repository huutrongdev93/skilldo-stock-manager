<?php
namespace Stock\Model;

use Ecommerce\Enum\Order\Status;

Class Inventory extends \SkillDo\Model\Model
{
    protected string $table = 'inventories';

    static function deleteById($inventoriesID = 0): array|bool
    {
        return static::whereKey($inventoriesID)->remove();
    }
}