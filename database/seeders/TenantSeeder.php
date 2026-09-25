<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationStatus;
use App\Enums\UserRole;
use App\Enums\WorkspaceStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Platform Admin (sem organization_id/workspace_id - usa global scope bypass)
        User::factory()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@saaspet.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::PlatformAdmin,
            'email_verified_at' => now(),
            'organization_id' => null,
            'workspace_id' => null,
        ]);

        // Organization de demonstração
        $org = Organization::factory()->create([
            'slug' => 'demo-petshop',
            'name' => 'Demo Petshop',
            'status' => OrganizationStatus::Active,
            'settings' => [
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt_BR',
                'currency' => 'BRL',
            ],
        ]);

        // Workspace principal
        $workspace = Workspace::factory()->create([
            'organization_id' => $org->id,
            'slug' => 'matriz',
            'name' => 'Matriz',
            'status' => WorkspaceStatus::Active,
            'settings' => [
                'address' => 'Rua das Flores, 123 - São Paulo/SP',
                'phone' => '(11) 99999-9999',
            ],
        ]);

        // Organization Admin
        User::factory()->create([
            'name' => 'Org Admin',
            'email' => 'orgadmin@demo-petshop.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::OrgAdmin,
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'workspace_id' => $workspace->id,
        ]);

        // Workspace Admin
        User::factory()->create([
            'name' => 'Workspace Admin',
            'email' => 'wsadmin@demo-petshop.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::WorkspaceAdmin,
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'workspace_id' => $workspace->id,
        ]);

        // Member
        User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@demo-petshop.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Member,
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'workspace_id' => $workspace->id,
        ]);

        // Segundo workspace para testar isolamento
        $workspace2 = Workspace::factory()->create([
            'organization_id' => $org->id,
            'slug' => 'filial-1',
            'name' => 'Filial 1',
            'status' => WorkspaceStatus::Active,
        ]);

        User::factory()->create([
            'name' => 'Filial 1 Admin',
            'email' => 'filial1@demo-petshop.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::WorkspaceAdmin,
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'workspace_id' => $workspace2->id,
        ]);

        // Segunda organização para testar isolamento cross-org
        $org2 = Organization::factory()->create([
            'slug' => 'outro-petshop',
            'name' => 'Outro Petshop',
            'status' => OrganizationStatus::Active,
        ]);

        $workspace3 = Workspace::factory()->create([
            'organization_id' => $org2->id,
            'slug' => 'matriz',
            'name' => 'Matriz',
            'status' => WorkspaceStatus::Active,
        ]);

        User::factory()->create([
            'name' => 'Outro Org Admin',
            'email' => 'admin@outro-petshop.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::OrgAdmin,
            'email_verified_at' => now(),
            'organization_id' => $org2->id,
            'workspace_id' => $workspace3->id,
        ]);

        $this->command->info('TenantSeeder concluído:');
        $this->command->info('- Platform Admin: platform@saaspet.com / password123');
        $this->command->info('- Org Admin: orgadmin@demo-petshop.com / password123');
        $this->command->info('- Workspace Admin: wsadmin@demo-petshop.com / password123');
        $this->command->info('- Member: member@demo-petshop.com / password123');
        $this->command->info('- Filial 1 Admin: filial1@demo-petshop.com / password123');
        $this->command->info('- Outro Org Admin: admin@outro-petshop.com / password123');
    }
}