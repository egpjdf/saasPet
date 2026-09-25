<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspaceUserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Active => 'Ativo',
            self::Revoked => 'Revogado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}