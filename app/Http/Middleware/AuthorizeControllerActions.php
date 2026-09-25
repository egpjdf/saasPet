<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Attributes\Authorize;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionAttribute;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeControllerActions
{
    public function __construct(
        private Application $app,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = Route::current();

        if (! $route) {
            return $next($request);
        }

        $action = $route->getAction();

        if (! isset($action['controller'])) {
            return $next($request);
        }

        [$controllerClass, $controllerMethod] = explode('@', $action['controller']);

        if (! class_exists($controllerClass) || ! method_exists($controllerClass, $controllerMethod)) {
            return $next($request);
        }

        $reflectionMethod = new ReflectionMethod($controllerClass, $controllerMethod);
        $reflectionClass = new ReflectionClass($controllerClass);

        $attributes = [
            ...$reflectionMethod->getAttributes(Authorize::class),
            ...$reflectionClass->getAttributes(Authorize::class),
        ];

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            if (! $instance->authorize($this->app)) {
                $message = $instance->message ?? 'This action is unauthorized.';

                return response()->json([
                    'message' => $message,
                    'error' => 'UNAUTHORIZED',
                ], 403);
            }
        }

        return $next($request);
    }
}