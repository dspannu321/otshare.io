<?php

namespace Tests\Unit;

use App\Support\SessionCookieName;
use Tests\TestCase;

class SessionCookieNameTest extends TestCase
{
    public function test_no_prefix_when_disabled(): void
    {
        config(['session.cookie_host_prefix' => false]);

        $this->assertSame('otshare_session', SessionCookieName::prefixed('otshare_session'));
    }

    public function test_host_prefix_when_root_path_and_empty_domain(): void
    {
        config([
            'session.cookie_host_prefix' => true,
            'session.path' => '/',
            'session.domain' => null,
        ]);

        $this->assertSame('__Host-otshare_session', SessionCookieName::prefixed('otshare_session'));
    }

    public function test_secure_prefix_when_domain_is_set(): void
    {
        config([
            'session.cookie_host_prefix' => true,
            'session.path' => '/',
            'session.domain' => '.otshare.io',
        ]);

        $this->assertSame('__Secure-otshare_session', SessionCookieName::prefixed('otshare_session'));
    }

    public function test_does_not_double_prefix(): void
    {
        config([
            'session.cookie_host_prefix' => true,
            'session.path' => '/',
            'session.domain' => null,
        ]);

        $this->assertSame('__Host-foo', SessionCookieName::prefixed('__Host-foo'));
    }
}
