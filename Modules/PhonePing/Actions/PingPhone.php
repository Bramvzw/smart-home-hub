<?php

namespace Modules\PhonePing\Actions;

use Illuminate\Http\Client\RequestException;
use Modules\PhonePing\Data\PingResult;
use Modules\PhonePing\Services\NtfyClient;

class PingPhone
{
    public function __construct(private readonly NtfyClient $ntfy) {}

    public function __invoke(): PingResult
    {
        if (! $this->ntfy->isConfigured()) {
            return new PingResult(false, 'No ntfy topic configured.');
        }

        try {
            $this->ntfy->send('📍 Where are you?', 'Pinged from Smart Home Hub.');
        } catch (RequestException $e) {
            return new PingResult(false, 'ntfy returned HTTP '.$e->response->status().'.');
        } catch (\Throwable) {
            return new PingResult(false, 'Could not reach ntfy.');
        }

        return new PingResult(true, 'Ping sent.');
    }
}
