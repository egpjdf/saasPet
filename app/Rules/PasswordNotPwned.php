<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class PasswordNotPwned implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sha1 = sha1($value);
        $prefix = substr($sha1, 0, 5);
        $suffix = substr($sha1, 5);

        $response = Http::timeout(3)->get("https://api.pwnedpasswords.com/range/{$prefix}");

        if (! $response->successful()) {
            // Fail open - if API is down, don't block
            return;
        }

        $lines = explode("\n", $response->body());

        foreach ($lines as $line) {
            [$hashSuffix, $count] = explode(':', trim($line));

            if (strtoupper($hashSuffix) === strtoupper($suffix)) {
                $fail('This password has appeared in data breaches. Please choose a different password.');
                return;
            }
        }
    }
}