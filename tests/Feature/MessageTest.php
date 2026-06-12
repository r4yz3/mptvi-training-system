<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessageTest extends TestCase
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

    public function test_send_and_receive_message(): void
    {
        $registrar = $this->as('registrar');
        $cashier = $this->as('cashier');

        $this->actingAs($registrar)->post("/messages/{$cashier->id}", ['body' => 'Hello there'])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $registrar->id, 'recipient_id' => $cashier->id, 'body' => 'Hello there',
        ]);
    }

    public function test_opening_thread_marks_messages_read_and_clears_badge(): void
    {
        $registrar = $this->as('registrar');
        $cashier = $this->as('cashier');
        Message::create(['sender_id' => $registrar->id, 'recipient_id' => $cashier->id, 'body' => 'Ping']);

        // cashier sees an unread badge
        $this->actingAs($cashier)->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->where('badges.messages', 1));

        // opening the thread marks read
        $this->actingAs($cashier)->get("/messages/{$registrar->id}")->assertOk();
        $this->assertNotNull(Message::first()->fresh()->read_at);

        // badge now clear
        $this->actingAs($cashier)->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->where('badges.messages', 0));
    }

    public function test_conversation_list_excludes_self(): void
    {
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->get('/messages')
            ->assertInertia(fn (Assert $p) => $p->component('Messages/Index')
                ->has('conversations', 4)); // 5 staff − self
    }
}
