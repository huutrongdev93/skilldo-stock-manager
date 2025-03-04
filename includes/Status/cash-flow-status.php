<?php
namespace Stock\Status;

use Illuminate\Support\Collection;

enum CashFlow: string
{
    case success      = 'success'; //hoàn thành
    case cancel       = 'cancel'; //hoàn thành

    public function label(): string
    {
        return match ($this) {
            self::success  => trans('Đã thanh toán'),
            self::cancel  => trans('Đã hủy'),
            default => null,
        };
    }

    public function color(): string
    {
        return match ($this)
        {
            self::success   => '#186caa',
            self::cancel   => '#fdbd41',
            default => null,
        };
    }

    public function badge(): string
    {
        return match ($this)
        {
            self::success   => 'green',
            self::cancel   => 'red',
            default => null,
        };
    }

    static function options(): Collection
    {
        return new Collection(array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases()));
    }

    static function has(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}