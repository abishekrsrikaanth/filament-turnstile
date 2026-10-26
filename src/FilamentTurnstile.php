<?php

namespace l3aro\FilamentTurnstile;

use Illuminate\Support\Facades\Http;
use Throwable;

class FilamentTurnstile
{
    protected static ?string $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(string $responseCode): FilamentTurnstileResponse
    {
        // Cloudflare being unreachable is not the same as a visitor failing the
        // challenge, and it must not be worse for them. retry() throws once its
        // attempts are spent, and a refused connection throws before any of them
        // land, so without this the exception leaves verify() and the form the
        // rule is guarding returns a 500. Failing closed with no error codes puts
        // it on the rule's unexpected path, which is what that message is for:
        // the visitor is asked to try again instead of meeting an error page.
        try {
            $response = Http::retry(3, 10)
                ->asJson()
                ->acceptJson()
                ->post(self::$url, [
                    'secret' => config('filament-turnstile.secret'),
                    'response' => $responseCode,
                ]);
        } catch (Throwable) {
            return FilamentTurnstileResponse::make(false, null);
        }

        return FilamentTurnstileResponse::make(
            $response->ok() && $response->json('success'),
            $response->json('error-codes'),
        );
    }

    public function getResetEventName(): string
    {
        return config('filament-turnstile.reset_event');
    }
}
