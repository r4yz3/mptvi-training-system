<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    private function as(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    public function test_all_roles_can_view_events(): void
    {
        foreach (['admin', 'manager', 'registrar', 'cashier', 'coordinator'] as $role) {
            $this->actingAs($this->as($role))->get('/events')->assertOk();
        }
    }

    public function test_only_event_manage_can_create(): void
    {
        $payload = ['title' => 'Orientation', 'type' => 'Orientation', 'date' => '2026-07-01'];

        // cashier lacks event.manage
        $this->actingAs($this->as('cashier'))->post('/events', $payload)->assertForbidden();

        // manager has event.manage
        $this->actingAs($this->as('manager'))->post('/events', $payload)->assertRedirect();
        $this->assertDatabaseHas('events', ['title' => 'Orientation']);
    }

    public function test_event_can_be_deleted_by_manager(): void
    {
        $e = Event::create(['title' => 'X', 'type' => 'General', 'date' => '2026-07-01']);
        $this->actingAs($this->as('admin'))->delete("/events/{$e->id}")->assertRedirect();
        $this->assertDatabaseMissing('events', ['id' => $e->id]);
    }
}
