<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Pest\Plugin\Laravel\LaravelPlugin;
use Pest\Plugin\Mutation\MutationPlugin;

uses(LaravelPlugin::class, MutationPlugin::class)->in('Feature', 'Unit');

beforeEach(function (): void {
    Config::set('app.key', 'base64:testkey123456789012345678901234=');
});

afterEach(function (): void {
    // Cleanup
});

expect()->extend('toBeModel', function (string $modelClass) {
    expect($this->value)->toBeInstanceOf($modelClass);
    return $this;
});

expect()->extend('toHaveGlobalScope', function (string $scopeName) {
    $model = $this->value;
    $scopes = $model->getGlobalScopes();
    expect($scopes)->toHaveKey($scopeName);
    return $this;
});