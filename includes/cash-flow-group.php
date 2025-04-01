<?php
namespace Stock\CashFlowGroup;

enum Transaction: string
{
    case orderSuccess       = 'orderSuccess';
    case orderReturn        = 'orderReturn';
    case supplierPayment    = 'supplierPayment';
    case supplierReceipt    = 'supplierReceipt';

    public function label(): string
    {
        return match ($this) {
            self::orderSuccess      => 'Thu tiền khách trả',
            self::orderReturn       => 'Chi tiền trả khách',
            self::supplierPayment   => 'Chi tiền trả NCC',
            self::supplierReceipt   => 'Thu tiền NCC hoàn trả',
        };
    }

    public function id(): ?int
    {
        return match ($this) {
            self::orderSuccess      => -1,
            self::orderReturn       => -2,
            self::supplierPayment   => -3,
            self::supplierReceipt   => -4,
        };
    }

    static function has(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}