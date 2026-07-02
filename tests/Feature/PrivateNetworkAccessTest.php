<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivateNetworkAccessTest extends TestCase
{
    public function test_it_allows_requests_from_configured_private_networks(): void
    {
        config([
            'network.private_access.enabled' => true,
            'network.private_access.allowed_cidrs' => ['127.0.0.1/32', '192.168.1.0/24'],
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.23'])
            ->get('/')
            ->assertOk();
    }

    public function test_it_blocks_requests_outside_configured_private_networks(): void
    {
        config([
            'network.private_access.enabled' => true,
            'network.private_access.allowed_cidrs' => ['127.0.0.1/32', '192.168.1.0/24'],
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/')
            ->assertForbidden();
    }

    public function test_it_blocks_a_spoofed_forwarded_for_header_from_a_public_ip(): void
    {
        config([
            'network.private_access.enabled' => true,
            'network.private_access.allowed_cidrs' => ['127.0.0.1/32', '192.168.1.0/24'],
        ]);

        // The immediate connection comes from a public IP that is not a trusted
        // proxy, so the spoofed X-Forwarded-For claiming a private IP must be
        // ignored and the request rejected.
        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
            ])
            ->get('/')
            ->assertForbidden();
    }
}
