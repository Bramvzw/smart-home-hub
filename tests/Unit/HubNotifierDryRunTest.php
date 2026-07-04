<?php

namespace Tests\Unit;

use App\Services\Ntfy\HubNotifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HubNotifierDryRunTest extends TestCase
{
    public function test_dry_run_suppresses_the_push(): void
    {
        config(['ntfy.dry_run' => true]);
        Http::fake();

        (new HubNotifier('https://ntfy.sh', 'topic', ''))->send('Title', 'Message');

        Http::assertNothingSent();
    }

    public function test_without_dry_run_the_push_goes_out(): void
    {
        config(['ntfy.dry_run' => false]);
        Http::fake(['ntfy.sh/*' => Http::response('', 200)]);

        (new HubNotifier('https://ntfy.sh', 'topic', ''))->send('Title', 'Message');

        Http::assertSentCount(1);
    }
}
