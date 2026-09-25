<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspaceRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Member => 'Membro',
            self::Viewer => 'Visualizador',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Admin => [
                'manage_members',
                'manage_settings',
                'manage_billing',
                'view_reports',
                'manage_calendar',
                'manage_pos',
                'manage_inventory',
                'manage_pets',
                'manage_tutors',
            ],
            self::Member => [
                'view_reports',
                'manage_calendar',
                'manage_pos',
                'manage_inventory',
                'manage_pets',
                'manage_tutors',
            ],
            self::Viewer => [
                'view_reports',
                'view_calendar',
                'view_pets',
                'view_tutors',
            ],
        };
    }

    public function canManageMembers(): bool
    {
        return $this === self::Admin;
    }

    public function canManageSettings(): bool
    {
        return $this === self::Admin;
    }

    public function canManageBilling(): bool
    {
        return $this === self::Admin;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::Member;
    }
}