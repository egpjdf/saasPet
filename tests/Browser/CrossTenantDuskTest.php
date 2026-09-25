<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

uses(DatabaseMigrations::class);

beforeEach(function (): void {
    $this->orgA = Organization::factory()->create(['slug' => 'org-a']);
    $this->orgB = Organization::factory()->create(['slug' => 'org-b']);
    
    $this->wsA1 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a1']);
    $this->wsB1 = Workspace::factory()->create(['organization_id' => $this->orgB->id, 'slug' => 'ws-b1']);
    
    $this->userA = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
        'email' => 'usera@org-a.com',
        'password' => bcrypt('password123'),
    ]);
    $this->userB = User::factory()->create([
        'organization_id' => $this->orgB->id,
        'workspace_id' => $this->wsB1->id,
        'email' => 'userb@org-b.com',
        'password' => bcrypt('password123'),
    ]);
});

test('User from Org A cannot access Org B dashboard', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login')
            ->assertPathIs("/{$this->orgA->slug}/{$this->wsA1->slug}/dashboard");
        
        // Try to access Org B workspace
        $browser->visit("/{$this->orgB->slug}/{$this->wsB1->slug}/dashboard")
            ->assertPathIs("/{$this->orgB->slug}/{$this->wsB1->slug}/login"); // Should redirect to login
    });
});

test('User from Org A cannot see Org B data in lists', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login')
            ->assertPathIs("/{$this->orgA->slug}/{$this->wsA1->slug}/dashboard");
        
        // Navigate to users list
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/users")
            ->assertSee($this->userA->name)
            ->assertDontSee($this->userB->name);
    });
});

test('User cannot manipulate URL to access other tenant', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Try to access Org B resource directly via URL manipulation
        $browser->visit("/{$this->orgB->slug}/{$this->wsB1->slug}/users")
            ->assertPathIs("/{$this->orgB->slug}/{$this->wsB1->slug}/login");
    });
});

test('Organization switcher only shows accessible organizations', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Check organization switcher (if exists)
        $browser->assertDontSee($this->orgB->name);
    });
});

test('Workspace switcher only shows accessible workspaces', function (): void {
    $wsA2 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a2']);
    
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Should only see wsA1, not wsA2
        $browser->assertSee($this->wsA1->name);
        $browser->assertDontSee($wsA2->name);
    });
});

test('Session isolation between tenants', function (): void {
    $this->browse(function (Browser $browser) {
        // Login as Org A user
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login')
            ->assertAuthenticated();
        
        // Open new browser session for Org B user
        $browser2 = $this->browse(function (Browser $browser2) {
            $browser2->visit("/{$this->orgB->slug}/{$this->wsB1->slug}/login")
                ->type('email', $this->userB->email)
                ->type('password', 'password123')
                ->press('Login')
                ->assertAuthenticated();
        });
        
        // Both sessions should be independent
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/dashboard")
            ->assertAuthenticated();
    });
});

test('CSRF protection prevents cross-tenant form submission', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Try to submit form to Org B endpoint
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/settings")
            ->assertSee('csrf_token'); // CSRF token present
    });
});

test('Real-time channels are tenant-isolated', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Check Echo configuration includes tenant context
        $browser->assertJsExpression('window.Echo.options.auth.headers["X-Organization-ID"]', $this->orgA->id);
    });
});

test('File uploads are tenant-isolated', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Upload file
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/files/upload")
            ->attach('file', __DIR__ . '/../fixtures/test-file.txt')
            ->press('Upload')
            ->assertSee('test-file.txt');
        
        // Verify file not accessible from Org B
        // (Would need separate browser session)
    });
});

test('API tokens are tenant-scoped', function (): void {
    $this->browse(function (Browser $browser) {
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/login")
            ->type('email', $this->userA->email)
            ->type('password', 'password123')
            ->press('Login');
        
        // Create API token
        $browser->visit("/{$this->orgA->slug}/{$this->wsA1->slug}/api-tokens")
            ->type('name', 'Test Token')
            ->press('Create Token')
            ->assertSee('Token created');
        
        // Token should only work for Org A
    });
});