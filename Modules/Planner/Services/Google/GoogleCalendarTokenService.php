<?php

namespace Modules\Planner\Services\Google;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Modules\Planner\Models\GoogleCalendarToken;

class GoogleCalendarTokenService
{
    public function authorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Session::put('google_calendar_oauth_state', $state);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => (string) config('planner.google.client_id'),
            'redirect_uri' => (string) config('planner.google.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', ['https://www.googleapis.com/auth/calendar.events', 'https://www.googleapis.com/auth/calendar.readonly']),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): GoogleCalendarToken
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => (string) config('planner.google.client_id'),
            'client_secret' => (string) config('planner.google.client_secret'),
            'redirect_uri' => (string) config('planner.google.redirect'),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ])->throw()->json();

        return $this->store($response);
    }

    public function accessToken(): ?string
    {
        $token = GoogleCalendarToken::query()->first();

        if (! $token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast() && $token->refresh_token) {
            $token = $this->refresh($token);
        }

        return $token->access_token;
    }

    public function refresh(GoogleCalendarToken $token): GoogleCalendarToken
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => (string) config('planner.google.client_id'),
            'client_secret' => (string) config('planner.google.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
        ])->throw()->json();

        return $this->store(array_merge($response, ['refresh_token' => $token->refresh_token]));
    }

    public function connected(): bool
    {
        return GoogleCalendarToken::query()->exists();
    }

    private function store(array $data): GoogleCalendarToken
    {
        return GoogleCalendarToken::query()->updateOrCreate(['id' => 1], [
            'access_token' => $data['access_token'] ?? null,
            'refresh_token' => $data['refresh_token'] ?? GoogleCalendarToken::query()->first()?->refresh_token,
            'expires_at' => isset($data['expires_in']) ? CarbonImmutable::now()->addSeconds((int) $data['expires_in'] - 60) : null,
        ]);
    }
}
