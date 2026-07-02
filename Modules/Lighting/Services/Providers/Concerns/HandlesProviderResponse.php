<?php

namespace Modules\Lighting\Services\Providers\Concerns;

use RuntimeException;

/**
 * Shared response handling for provider API clients: decode the JSON body,
 * verify success via a provider-specific predicate, and extract the payload.
 * Only the provider's own code/message is ever included in thrown messages —
 * never credentials or tokens.
 */
trait HandlesProviderResponse
{
    /**
     * @param  callable(mixed $data, mixed $code): bool  $isSuccessful
     * @param  callable(mixed $data): array  $extractResult
     */
    private function extractProviderResult($response, string $providerLabel, callable $isSuccessful, callable $extractResult): array
    {
        $data = $response->json();
        $code = is_array($data) ? ($data['code'] ?? $response->status()) : $response->status();

        if (! $isSuccessful($data, $code)) {
            throw new RuntimeException("{$providerLabel} API request failed (code {$code}).");
        }

        return $extractResult($data);
    }
}
