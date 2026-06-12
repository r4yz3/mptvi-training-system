<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_redirects_guests_to_login(): void
    {
        // The app has no public landing page — '/' routes guests to the login screen.
        $this->get('/')->assertRedirect('/login');
    }
}
