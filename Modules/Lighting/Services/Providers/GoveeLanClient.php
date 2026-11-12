<?php

namespace Modules\Lighting\Services\Providers;

use RuntimeException;

/**
 * Govee LAN API transport: JSON commands over UDP straight to the device
 * (port 4003), no cloud round trip and no 10-commands-per-minute limit.
 *
 * Requires "LAN Control" switched on in the Govee Home app and the device's
 * IP in GOVEE_LAN_DEVICES ("<device id>@<ip>", comma separated). UDP is
 * fire-and-forget: the device does not acknowledge commands, so the state
 * still comes from the cloud poll.
 */
class GoveeLanClient
{
    private const PORT = 4003;

    /** @var array<string, string>|null device id => IP, parsed once per request */
    private ?array $ips = null;

    private float $lastSentAt = 0.0;

    public function hasDevice(string $id): bool
    {
        return $this->ipFor($id) !== null;
    }

    public function turn(string $id, bool $on): void
    {
        $this->send($id, 'turn', ['value' => $on ? 1 : 0]);
    }

    public function brightness(string $id, int $percent): void
    {
        // The LAN API rejects 0; "off" goes through turn().
        $this->send($id, 'brightness', ['value' => max(1, min(100, $percent))]);
    }

    public function color(string $id, int $r, int $g, int $b): void
    {
        $this->send($id, 'colorwc', ['color' => ['r' => $r, 'g' => $g, 'b' => $b], 'colorTemInKelvin' => 0]);
    }

    public function colorTemp(string $id, int $kelvin): void
    {
        $this->send($id, 'colorwc', ['color' => ['r' => 0, 'g' => 0, 'b' => 0], 'colorTemInKelvin' => $kelvin]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function send(string $id, string $cmd, array $data): void
    {
        $ip = $this->ipFor($id) ?? throw new RuntimeException('Govee device has no LAN address.');

        // Back-to-back datagrams can be dropped by the strip's controller;
        // a short gap keeps power → colour → brightness sequences intact.
        $gapMs = max(0, (int) config('lighting.govee.lan_command_gap_ms', 60));
        $elapsedMs = (microtime(true) - $this->lastSentAt) * 1000;
        if ($this->lastSentAt > 0.0 && $elapsedMs < $gapMs) {
            usleep((int) round(($gapMs - $elapsedMs) * 1000));
        }

        $this->transmit($ip, json_encode(['msg' => ['cmd' => $cmd, 'data' => $data]], JSON_THROW_ON_ERROR));
        $this->lastSentAt = microtime(true);
    }

    /** Sends one datagram. Separate so tests can capture payloads. */
    protected function transmit(string $ip, string $payload): void
    {
        $socket = @stream_socket_client('udp://'.$ip.':'.self::PORT, $errno, $error, 1);

        if ($socket === false) {
            throw new RuntimeException("Govee LAN send failed ({$errno}).");
        }

        try {
            if (@fwrite($socket, $payload) !== strlen($payload)) {
                throw new RuntimeException('Govee LAN send failed.');
            }
        } finally {
            fclose($socket);
        }
    }

    private function ipFor(string $id): ?string
    {
        if ($this->ips === null) {
            $this->ips = [];
            foreach (explode(',', (string) config('lighting.govee.lan_devices', '')) as $entry) {
                // Device ids contain colons, so the IP is split off at the last "@".
                $at = strrpos($entry, '@');
                if ($at === false) {
                    continue;
                }
                $device = trim(substr($entry, 0, $at));
                $ip = trim(substr($entry, $at + 1));
                if ($device !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    $this->ips[$device] = $ip;
                }
            }
        }

        return $this->ips[$id] ?? null;
    }
}
