<?php

namespace Modules\Briefing\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class PrismBriefingTextGenerator implements \Modules\Briefing\Contracts\BriefingTextGenerator
{
    public function generate(array $sections, string $systemPrompt, string $prompt): string
    {
        $apiKey = (string) config('ai.anthropic.api_key', '');

        if ($apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $response = Prism::text()
            ->using(Provider::Anthropic, (string) config('briefing.ai.model', 'claude-sonnet-4-6'), [
                'api_key' => $apiKey,
                'version' => (string) config('ai.anthropic.version', '2023-06-01'),
                'url' => (string) config('ai.anthropic.url', 'https://api.anthropic.com/v1'),
                'anthropic_beta' => config('ai.anthropic.anthropic_beta'),
            ])
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->withMaxTokens((int) config('briefing.ai.max_tokens', 700))
            ->usingTemperature((float) config('briefing.ai.temperature', 0.5))
            ->asText();

        $body = trim($response->text);

        if ($body === '') {
            throw new RuntimeException('AI returned an empty briefing.');
        }

        return $body;
    }
}
