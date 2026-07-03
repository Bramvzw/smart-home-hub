<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicAuthenticationTest extends TestCase
{
    public function test_it_allows_requests_when_basic_auth_is_not_configured(): void
    {
        config([
            'network.basic_auth.username' => '',
            'network.basic_auth.password' => '',
        ]);

        $this->get('/')->assertOk();
    }

    public function test_it_rejects_requests_without_credentials_when_configured(): void
    {
        config([
            'network.basic_auth.username' => 'admin',
            'network.basic_auth.password' => 'secret',
        ]);

        $this->get('/')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="Smart Home Hub"');
    }

    public function test_it_rejects_requests_with_wrong_credentials(): void
    {
        config([
            'network.basic_auth.username' => 'admin',
            'network.basic_auth.password' => 'secret',
        ]);

        $this
            ->withServerVariables(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'wrong'])
            ->get('/')
            ->assertUnauthorized();
    }

    public function test_it_allows_requests_with_correct_credentials(): void
    {
        config([
            'network.basic_auth.username' => 'admin',
            'network.basic_auth.password' => 'secret',
        ]);

        $this
            ->withServerVariables(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'secret'])
            ->get('/')
            ->assertOk();
    }

    public function test_the_health_endpoint_stays_open_for_container_healthchecks(): void
    {
        config([
            'network.basic_auth.username' => 'admin',
            'network.basic_auth.password' => 'secret',
        ]);

        $this->get('/up')->assertOk();
    }
}
