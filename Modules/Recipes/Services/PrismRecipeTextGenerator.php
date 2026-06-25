<?php

namespace Modules\Recipes\Services;

use Modules\Recipes\Contracts\RecipeTextGenerator;
use Modules\Recipes\Data\GeneratedRecipeSet;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class PrismRecipeTextGenerator implements RecipeTextGenerator
{
    public function generate(array $offers, int $count, int $servings): GeneratedRecipeSet
    {
        $apiKey = (string) config('ai.anthropic.api_key', '');

        if ($apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $model = (string) config('recipes.ai.model', 'claude-sonnet-4-6');
        $response = Prism::text()
            ->using(Provider::Anthropic, $model, [
                'api_key' => $apiKey,
                'version' => (string) config('ai.anthropic.version', '2023-06-01'),
                'url' => (string) config('ai.anthropic.url', 'https://api.anthropic.com/v1'),
                'anthropic_beta' => config('ai.anthropic.anthropic_beta'),
            ])
            ->withSystemPrompt($this->systemPrompt())
            ->withPrompt($this->prompt($offers, $count, $servings))
            ->withMaxTokens((int) config('recipes.ai.max_tokens', 2000))
            ->usingTemperature((float) config('recipes.ai.temperature', 0.45))
            ->asText();

        $payload = $this->decodeJson($response->text);

        if (! isset($payload['recipes']) || ! is_array($payload['recipes'])) {
            throw new RuntimeException('AI recipe response did not contain a recipes array.');
        }

        return new GeneratedRecipeSet(
            recipes: array_values($payload['recipes']),
            model: $model,
        );
    }

    private function systemPrompt(): string
    {
        return implode(' ', [
            'Je bent een Nederlandse thuiskok en meal-planner.',
            'Maak snelle, simpele recepten voor 1-2 personen op basis van supermarkt-aanbiedingen.',
            'Gebruik vooral afgeprijsde ingredienten, maar voeg normale voorraadkast-items toe waar nodig.',
            'Geef strikt JSON terug, zonder markdown.',
        ]);
    }

    private function prompt(array $offers, int $count, int $servings): string
    {
        $payload = json_encode($offers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return <<<PROMPT
Maak {$count} snelle recepten in het Nederlands voor {$servings} persoon/personen.
Elk recept moet simpel zijn, ongeveer 15-35 minuten duren en een eigen boodschappenlijst hebben.
Markeer ingredienten uit de aanbiedingen met "on_offer": true en de winkelcode in "store".

Geef exact deze JSON-vorm terug:
{
  "recipes": [
    {
      "title": "string",
      "description": "string",
      "servings": 2,
      "time_minutes": 25,
      "estimated_cost": 6.40,
      "ingredients": [{"name": "string", "amount": "string", "on_offer": true, "store": "ah"}],
      "steps": ["string"],
      "shopping_list": [{"name": "string", "amount": "string", "on_offer": true, "store": "ah"}]
    }
  ]
}

Aanbiedingen:
{$payload}
PROMPT;
    }

    private function decodeJson(string $text): array
    {
        $text = trim($text);
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end < $start) {
            throw new RuntimeException('AI recipe response was not JSON.');
        }

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI recipe JSON did not decode to an object.');
        }

        return $decoded;
    }
}
