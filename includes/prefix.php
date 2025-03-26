<?php
namespace Stock;

use Illuminate\Support\Collection;

enum Prefix: string
{
    case purchaseOrder      = 'PN'; //phiếu nhập
    case purchaseReturn     = 'THN'; //phiếu trả hàng
    case damageItem         = 'XH'; //Phiếu xuất hủy hàng
    case adjustment         = 'CB'; //Phiếu điều chỉnh ncc
    case transfer           = 'TRF'; //Phiếu chuyển hàng

    static function has(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}