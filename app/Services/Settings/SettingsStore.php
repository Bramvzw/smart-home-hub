<?php

namespace App\Services\Settings;

use App\Models\Setting;

/**
 * Reads and writes UI-editable settings, overlaying stored values on top of the
 * config()/env defaults.
 *
 * Usage pattern:
 *   $store->get('printer.low_filament_pct', (int) config('printer.low_filament_pct', 20))
 *
 * Values are cached in-memory for the lifetime of the instance (registered as a
 * singleton), so repeated reads within a request cost a single query.
 */
class SettingsStore
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        return array_key_exists($key, $this->cache) ? $this->cache[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        $this->load();
        $this->cache[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->load();

        return array_key_exists($key, $this->cache);
    }

    /**
     * Drop the in-memory cache so the next read hits the database again.
     */
    public function flush(): void
    {
        $this->cache = null;
    }

    private function load(): void
    {
        if ($this->cache !== null) {
            return;
        }

        try {
            $this->cache = Setting::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            // Table not migrated yet (or DB unavailable): fall back to defaults.
            $this->cache = [];
        }
    }
}
