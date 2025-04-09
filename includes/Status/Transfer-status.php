<?php
namespace Skdepot\Status;

use Illuminate\Support\Collection;

enum Transfer: string
{
    case draft      = 'draft';
    case process    = 'process';
    case success    = 'success';
    case cancel     = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::draft    => 'Phiếu tạm',
            self::process  => 'Đang chuyển',
            self::success  => 'Đã chuyển',
            self::cancel   => 'Hủy',
            default => null,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::draft    => '#6e7173',
            self::process  => '#186caa',
            self::success  => '#186caa',
            self::cancel   => '#fdbd41',
            default => null,
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::draft    => 'yellow',
            self::process  => 'blue',
            self::success  => 'green',
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