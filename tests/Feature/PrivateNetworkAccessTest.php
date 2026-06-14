<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivateNetworkAccessTest extends TestCase
{
    public function test_it_allows_requests_from_configured_private_networks(): void
    {
        config([
            'network.private_access.enabled' => true,
            'network.private_access.allowed_cidrs' => ['127.0.0.1/32', '192.168.68.0/24'],
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '192.168.68.23'])
            ->get('/')
            ->assertOk();
    }

    public function test_it_blocks_requests_outside_configured_private_networks(): void
    {
        config([
            'network.private_access.enabled' => true,
            'network.private_access.allowed_cidrs' => ['127.0.0.1/32', '192.168.68.0/24'],
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/')
            ->assertForbidden();
    }
}
