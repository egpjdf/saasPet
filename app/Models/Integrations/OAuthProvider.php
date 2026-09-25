<?php

declare(strict_types=1);

namespace App\Models\Integrations;

use App\Models\Organization;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OAuthProvider extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'oauth_providers';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'provider', // google, microsoft, github, apple
        'name',
        'client_id',
        'client_secret',
        'redirect_uri',
        'scopes',
        'enabled',
        'config', // additional provider-specific config
    ];

    protected $casts = [
        'scopes' => 'array',
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    protected $hidden = [
        'client_secret',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function getDecryptedSecret(): string
    {
        return decrypt($this->client_secret);
    }

    public function setClientSecret(string $secret): void
    {
        $this->client_secret = encrypt($secret);
    }
}