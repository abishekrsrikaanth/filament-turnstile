<?php

namespace l3aro\FilamentTurnstile\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Closure;
use l3aro\FilamentTurnstile\Facades\FilamentTurnstileFacade;

class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = FilamentTurnstileFacade::verify($value);

        if ($response->success) {
            return;
        }

        // errorCodes is nullable, and it is null for every failure that is not
        // Cloudflare rejecting the token: a timeout, a 5xx, anything that leaves
        // `error-codes` out of the body. Without the return, that path reports the
        // unexpected-error message and then falls into the loop below with null,
        // which is a TypeError and a 500 rather than a failed validation.
        if (empty($response->errorCodes)) {
            $fail('filament-turnstile::errors.unexpected')->translate();

            return;
        }

        foreach ($response->errorCodes as $errorCode) {
            $fail('filament-turnstile::errors.' . $errorCode)->translate();
        }
    }
}
