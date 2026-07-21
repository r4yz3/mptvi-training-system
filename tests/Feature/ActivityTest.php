<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Applicant;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ProgramSeeder::class);
    }

    private function as(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    public function test_module_scoped_to_admin_and_secretary(): void
    {
        $this->actingAs($this->as('manager'))->get('/activity')->assertOk();
        $this->actingAs($this->as('cashier'))->get('/activity')->assertForbidden();
        $this->actingAs($this->as('coordinator'))->get('/activity')->assertForbidden();
    }

    public function test_creating_an_applicant_writes_an_activity_attributed_to_user(): void
    {
        $registrar = $this->as('registrar');

        $this->actingAs($registrar)->post('/applicants', [
            'last_name' => 'Reyes', 'first_name' => 'Pedro', 'barangay' => 'Bac',
            'contact' => '0918', 'sex' => 'Male', 'program_id' => Program::first()->id,
        ]);

        $log = Activity::where('subject_type', 'Applicant')->where('event', 'created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($registrar->id, $log->user_id);
        $this->assertStringContainsString('Created applicant', $log->description);
    }

    public function test_status_update_is_logged_but_timestamp_only_change_is_not(): void
    {
        $registrar = $this->as('registrar');
        $a = Applicant::create(['program_id' => Program::first()->id, 'status' => 'Registered', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09']);
        $createCount = Activity::where('subject_id', $a->id)->count();

        $this->actingAs($registrar);
        $a->touch(); // only updated_at — should NOT log
        $this->assertSame(0, Activity::where('subject_id', $a->id)->where('event', 'updated')->count());

        $a->update(['status' => 'Enrolled']); // real change — should log exactly one update
        $this->assertSame(1, Activity::where('subject_id', $a->id)->where('event', 'updated')->count());
    }
}
