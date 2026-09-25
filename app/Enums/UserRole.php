<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case PlatformAdmin = 'platform_admin';
    case OrgAdmin = 'org_admin';
    case WorkspaceAdmin = 'workspace_admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'Admin da Plataforma',
            self::OrgAdmin => 'Admin da Organização',
            self::WorkspaceAdmin => 'Admin do Workspace',
            self::Member => 'Membro',
            self::Viewer => 'Visualizador',
        };
    }

    public static function platformRoles(): array
    {
        return [self::PlatformAdmin];
    }

    public static function organizationRoles(): array
    {
        return [self::OrgAdmin];
    }

    public static function workspaceRoles(): array
    {
        return [self::WorkspaceAdmin, self::Member, self::Viewer];
    }

    public function isPlatformAdmin(): bool
    {
        return $this === self::PlatformAdmin;
    }

    public function isOrgAdmin(): bool
    {
        return $this === self::OrgAdmin;
    }

    public function isWorkspaceAdmin(): bool
    {
        return $this === self::WorkspaceAdmin;
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::PlatformAdmin, self::OrgAdmin, self::WorkspaceAdmin], true);
    }

    public function canManageBilling(): bool
    {
        return in_array($this, [self::PlatformAdmin, self::OrgAdmin], true);
    }
}