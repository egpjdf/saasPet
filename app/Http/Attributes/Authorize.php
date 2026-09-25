<?php

declare(strict_types=1);

namespace App\Http\Attributes;

use Attribute;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Authorize
{
    public function __construct(
        public string $ability,
        public ?string $model = null,
        public ?string $guard = null,
        public ?string $message = null,
    ) {}

    public function authorize(Application $app): bool
    {
        $guard = $this->guard ? $app[Guard::class . $this->guard] : $app[Guard::class];

        $user = $guard->user();

        if (! $user instanceof Authenticatable) {
            return false;
        }

        if ($this->model) {
            $modelClass = $this->model;

            if (! class_exists($modelClass)) {
                return false;
            }

            $model = app($modelClass);

            if (! $model) {
                return false;
            }

            return $user->can($this->ability, $model);
        }

        return $user->can($this->ability);
    }
}