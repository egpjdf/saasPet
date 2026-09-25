<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspaceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
            self::Archived => 'Arquivado',
        };
    }

    public static function active(): array
    {
        return [self::Active];
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}