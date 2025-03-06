<?php
namespace Stock\Status;

use Illuminate\Support\Collection;

enum Supplier: string
{
    case use         = 'use';
    case stop        = 'stop';

    public function label(): string
    {
        return match ($this) {
            self::use    => 'Đang sử dụng',
            self::stop   => 'Ngừng sử dụng',
            default => null,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::use    => '#6e7173',
            self::stop   => '#186caa',
            default => null,
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::use    => 'green',
            self::stop   => 'red',
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