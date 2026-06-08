<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_login_route_is_registered(): void
    {
        $this->assertSame(url('/login'), route('login'));
    }
}
