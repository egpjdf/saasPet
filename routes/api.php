<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Tenant routes (organization + workspace)
    Route::middleware(['tenant'])->group(function () {
        // Organization Admin routes (/{org}/)
        Route::prefix('{organization}')->name('organization.')->group(function () {
            Route::get('/', fn () => response()->json(['organization' => request()->attributes->get('organization')]))->name('dashboard');
            
            // Workspace CRUD (Organization Admin)
            Route::apiResource('workspaces', \App\Http\Controllers\Organization\WorkspaceController::class)
                ->names('workspaces')
                ->parameters(['workspaces' => 'workspace']);
            
            // Workspace Members (Org Admin / Workspace Admin)
            Route::prefix('workspaces/{workspace}')->name('workspaces.')->group(function () {
                Route::apiResource('members', \App\Http\Controllers\Organization\WorkspaceMemberController::class)
                    ->names('members')
                    ->parameters(['members' => 'workspace_user']);
                
                Route::post('members/{workspace_user}/resend-invite', [\App\Http\Controllers\Organization\WorkspaceMemberController::class, 'resendInvite'])
                    ->name('members.resend-invite');
            });

            // Workspace routes (/{org}/{ws}/)
            Route::prefix('{workspace}')->name('workspace.')->group(function () {
                Route::get('/', fn () => response()->json([
                    'organization' => request()->attributes->get('organization'),
                    'workspace' => request()->attributes->get('workspace'),
                ]))->name('dashboard');
            });
        });
    });

    // Platform Admin routes (/admin)
    Route::prefix('admin')->name('admin.')->middleware(['platform.admin'])->group(function () {
        Route::get('/', fn () => response()->json(['platform_admin' => true]))->name('dashboard');
        
        // Organization CRUD (Platform Admin)
        Route::apiResource('organizations', \App\Http\Controllers\Platform\OrganizationController::class)
            ->names('organizations')
            ->parameters(['organizations' => 'organization']);
    });

    // Public invite accept (signed URL, no auth required)
    Route::get('invite/accept/{workspace_user}', [\App\Http\Controllers\Organization\WorkspaceInviteController::class, 'accept'])
        ->name('workspace.invite.accept')
        ->middleware('signed');

    // User profile
    Route::get('/user', function () {
        return response()->json(auth()->user());
    })->name('user.profile');
});

// Public health check
Route::get('/health', fn () => response()->json(['status' => 'ok']));