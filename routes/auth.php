<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Billing\PortalController;
use App\Http\Controllers\Billing\WebhookController;
use App\Http\Controllers\Integrations\OAuthController;
use App\Http\Controllers\LGPD\LGPDController;
use App\Http\Controllers\Notifications\NotificationController;
use Illuminate\Support\Facades\Route;

// Guest routes (no auth required)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Webhook endpoints (no auth required - verified via signature)
Route::post('webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('webhooks/paddle', [WebhookController::class, 'paddle'])->name('webhooks.paddle');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email verification
    Route::get('email/verify', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('email/verification-notification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Two-factor authentication
    Route::get('two-factor-challenge', [TwoFactorController::class, 'show'])->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorController::class, 'store']);

    Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('two-factor', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::get('two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');

    // Billing portal
    Route::get('billing/portal', [PortalController::class, 'redirect'])->name('billing.portal');
    Route::get('billing/portal/stripe', [PortalController::class, 'stripe'])->name('billing.portal.stripe');
    Route::get('billing/portal/paddle', [PortalController::class, 'paddle'])->name('billing.portal.paddle');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/{id}', [NotificationController::class, 'show'])->name('show');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('/{id}/archive', [NotificationController::class, 'archive'])->name('archive');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreference'])->name('preferences.update');
        Route::get('/unsubscribe/{token}', [NotificationController::class, 'unsubscribe'])->name('unsubscribe');
    });

    // OAuth
    Route::prefix('oauth')->name('oauth.')->group(function () {
        Route::get('{provider}/redirect', [OAuthController::class, 'redirect'])->name('redirect');
        Route::get('{provider}/callback', [OAuthController::class, 'callback'])->name('callback');
        Route::delete('{provider}', [OAuthController::class, 'disconnect'])->name('disconnect');
    });

    // LGPD Compliance
    Route::prefix('lgpd')->name('lgpd.')->group(function () {
        // Consent
        Route::post('consent', [LGPDController::class, 'recordConsent'])->name('consent.record');
        Route::delete('consent/{consentId}', [LGPDController::class, 'revokeConsent'])->name('consent.revoke');
        Route::get('consents', [LGPDController::class, 'getConsents'])->name('consents');

        // Data Export
        Route::get('export', [LGPDController::class, 'export'])->name('export');
        Route::get('export/organization', [LGPDController::class, 'exportOrganization'])->name('export.organization');

        // Data Deletion
        Route::post('deletion-request', [LGPDController::class, 'requestDeletion'])->name('deletion.request');

        // DPA
        Route::get('dpa', [LGPDController::class, 'dpa'])->name('dpa');
        Route::get('dpa/download/{organization}', [LGPDController::class, 'downloadDpa'])->name('dpa.download');

        // Retention Policies
        Route::get('retention-policies', [LGPDController::class, 'retentionPolicies'])->name('retention-policies');
        Route::put('retention-policies/{id}', [LGPDController::class, 'updateRetentionPolicy'])->name('retention-policies.update');
    });
});

// 2FA required for admin routes
Route::middleware(['auth:sanctum', 'tenant', \App\Http\Middleware\EnsureTwoFactorEnabled::class])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('billing/portal', [PortalController::class, 'redirect'])->name('billing.portal');
        // Admin routes that require 2FA
    });

    Route::prefix('{organization}')->name('organization.')->group(function () {
        Route::middleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class)->group(function () {
            Route::get('billing/portal', [PortalController::class, 'redirect'])->name('billing.portal');
            // Org admin routes that require 2FA
        });
    });
});