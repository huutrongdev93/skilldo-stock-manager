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
    case stockTake          = 'KK'; //Phiếu kiểm kho
    case orderReturn        = 'TH'; //Phiếu trả hàng
    //Prefix cho phiếu thu chi
    case cashFlowOrder          = 'TTDH'; //Phiếu thu khách thanh toán đơn hàng
    case cashFlowOrderReturn    = 'TTTH'; //phiếu chi khi trả hàng từ đơn hàng
    case cashFlowPurchaseOrder  = 'TTPN'; //Thanh toán phiếu nhập
    case cashFlowPurchaseReturn = 'PTTHN'; //phiếu thu khi trả hàng nhập

    static function has(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}