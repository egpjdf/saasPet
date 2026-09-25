<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Trial = 'trial';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Suspended => 'Suspensa',
            self::Trial => 'Em Teste',
            self::Cancelled => 'Cancelada',
        };
    }

    public static function active(): array
    {
        return [self::Active, self::Trial];
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Trial], true);
    }
}