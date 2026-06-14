<?php

namespace Modules\Lighting\Services\Providers;

use Modules\Lighting\Contracts\LightProvider;
use Modules\Lighting\Data\Light;
use Modules\Lighting\Support\Color;

class TuyaProvider implements LightProvider
{
    public function __construct(
        private readonly TuyaApiClient $client,
        private readonly TuyaTokenService $token,
    ) {}

    public function key(): string
    {
        return 'tuya';
    }

    public function label(): string
    {
        return 'Calex';
    }

    public function isConfigured(): bool
    {
        $tuya = (array) config('lighting.tuya');

        return ! empty($tuya['client_id']) && ! empty($tuya['client_secret']);
    }

    public function lights(): array
    {
        // UID-less: returns every device linked to the project, so the app-account
        // UID never has to be supplied by hand.
        $result = $this->client->get('/v1.0/iot-01/associated-users/devices', $this->token->accessToken());
        $devices = $result['devices'] ?? (is_array($result) ? $result : []);

        $lights = [];
        foreach (is_array($devices) ? $devices : [] as $device) {
            $status = $this->statusMap($device['status'] ?? []);
            if (! array_key_exists('switch_led', $status)) {
                continue; // not a light
            }
            $lights[] = $this->map($device, $status);
        }

        return $lights;
    }

    public function setPower(string $id, bool $on): void
    {
        $this->command($id, [['code' => 'switch_led', 'value' => $on]]);
    }

    public function setBrightness(string $id, int $percent): void
    {
        $value = max(10, min(1000, (int) round($percent * 10)));
        $this->command($id, [['code' => 'bright_value_v2', 'value' => $value]]);
    }

    public function setColor(string $id, string $hex): void
    {
        // Tuya expects colour_data_v2 as a JSON object {h,s,v}, not a stringified one.
        $this->command($id, [
            ['code' => 'work_mode', 'value' => 'colour'],
            ['code' => 'colour_data_v2', 'value' => Color::hexToTuyaHsv($hex)],
        ]);
    }

    private function command(string $id, array $commands): void
    {
        $this->client->post("/v1.0/devices/{$id}/commands", ['commands' => $commands], $this->token->accessToken());
    }

    private function map(array $device, array $status): Light
    {
        $supportsColor = array_key_exists('colour_data_v2', $status);

        $color = null;
        if ($supportsColor && is_string($status['colour_data_v2']) && $status['colour_data_v2'] !== '') {
            $hsv = json_decode($status['colour_data_v2'], true);
            if (is_array($hsv) && isset($hsv['h'], $hsv['s'], $hsv['v'])) {
                $color = Color::tuyaHsvToHex((int) $hsv['h'], (int) $hsv['s'], (int) $hsv['v']);
            }
        }

        $brightness = isset($status['bright_value_v2'])
            ? (int) round(((int) $status['bright_value_v2']) / 10)
            : 0;

        return new Light(
            provider: $this->key(),
            id: (string) ($device['id'] ?? ''),
            name: (string) ($device['name'] ?? 'Lamp'),
            on: (bool) ($status['switch_led'] ?? false),
            brightness: max(0, min(100, $brightness)),
            color: $color,
            reachable: (bool) ($device['online'] ?? false),
            supportsColor: $supportsColor,
        );
    }

    /**
     * @param  array<int, array{code?: string, value?: mixed}>  $status
     * @return array<string, mixed>
     */
    private function statusMap(array $status): array
    {
        $map = [];
        foreach ($status as $entry) {
            if (isset($entry['code'])) {
                $map[$entry['code']] = $entry['value'] ?? null;
            }
        }

        return $map;
    }
}
