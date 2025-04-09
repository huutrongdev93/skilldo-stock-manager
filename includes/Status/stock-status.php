<?php
namespace Skdepot\Status;

use Illuminate\Support\Collection;

enum Inventory: string
{
    case in         = 'instock'; //còn hàng
    case out        = 'outstock'; //hết hàng
    case off        = 'onbackorder'; //không còn kinh doanh

    public function label(): string
    {
        $label = match ($this) {
            self::in    => trans('stock.status.instock'),
            self::out   => trans('stock.status.outstock'),
            self::off   => trans('stock.status.onbackorder'),
            default => null,
        };

        return apply_filters('stock_status_label', $label, $this->value);
    }

    public function color(): string
    {
        $label = match ($this) {
            self::in    => '#6e7173',
            self::out   => '#186caa',
            self::off   => '#fdbd41',
            default => null,
        };

        return apply_filters('stock_status_color', $label, $this->value);
    }

    public function badge(): string
    {
        $label = match ($this) {
            self::in    => 'green',
            self::out   => 'red',
            self::off   => 'yellow',
            default => null,
        };

        return apply_filters('stock_status_badge', $label, $this->value);
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