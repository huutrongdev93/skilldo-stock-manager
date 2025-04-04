<?php
namespace Stock\Status;

use Illuminate\Support\Collection;

enum DamageItem: string
{
    case draft        = 'draft'; //bản nháp
    case success      = 'complete'; //hoàn thành
    case cancel       = 'cancel'; //hoàn thành

    public function label(): string
    {
        return match ($this) {
            self::draft    => trans('Phiếu tạm'),
            self::success  => trans('Hoàn thành'),
            self::cancel  => trans('Đã hủy'),
            default => null,
        };
    }

    public function color(): string
    {
        return match ($this)
        {
            self::draft    => '#6e7173',
            self::success   => '#186caa',
            self::cancel   => '#fdbd41',
            default => null,
        };
    }

    public function badge(): string
    {
        return match ($this)
        {
            self::draft    => 'yellow',
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