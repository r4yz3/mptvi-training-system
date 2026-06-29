<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ProgramSeeder::class);
    }

    private function registrar(): User
    {
        return User::role('registrar')->firstOrFail();
    }

    private function base(): array
    {
        return [
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob',
            'contact' => '0917', 'sex' => 'Male', 'program_id' => Program::first()->id,
        ];
    }

    public function test_education_history_is_saved_per_level(): void
    {
        $this->actingAs($this->registrar())->post('/applicants', $this->base() + [
            'education_history' => [
                'elementary' => ['school' => 'Magsaysay Central ES', 'started' => '2005', 'graduated' => '2011', 'status' => 'Graduate'],
                'college' => ['school' => 'MPTVI', 'started' => '2018', 'graduated' => '', 'status' => 'Ongoing'],
            ],
        ])->assertRedirect();

        $a = Applicant::where('last_name', 'Cruz')->firstOrFail();
        $this->assertSame('Magsaysay Central ES', $a->education_history['elementary']['school']);
        $this->assertSame('Graduate', $a->education_history['elementary']['status']);
        $this->assertSame('Ongoing', $a->education_history['college']['status']);
    }

    public function test_empty_rows_are_dropped(): void
    {
        $this->actingAs($this->registrar())->post('/applicants', $this->base() + [
            'education_history' => [
                'elementary' => ['school' => 'Some ES', 'started' => '', 'graduated' => '', 'status' => 'Graduate'],
                'junior_high' => ['school' => '', 'started' => '', 'graduated' => '', 'status' => ''],
            ],
        ])->assertRedirect();

        $a = Applicant::where('last_name', 'Cruz')->firstOrFail();
        $this->assertArrayHasKey('elementary', $a->education_history);
        $this->assertArrayNotHasKey('junior_high', $a->education_history);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs($this->registrar())->post('/applicants', $this->base() + [
            'education_history' => [
                'elementary' => ['school' => 'X', 'status' => 'Wizard'],
            ],
        ])->assertSessionHasErrors('education_history.elementary.status');
    }

    public function test_unknown_levels_are_ignored(): void
    {
        $this->actingAs($this->registrar())->post('/applicants', $this->base() + [
            'education_history' => [
                'kindergarten' => ['school' => 'Tiny Tots', 'status' => 'Graduate'],
                'college' => ['school' => 'MPTVI', 'status' => 'Graduate'],
            ],
        ])->assertRedirect();

        $a = Applicant::where('last_name', 'Cruz')->firstOrFail();
        $this->assertArrayNotHasKey('kindergarten', $a->education_history);
        $this->assertArrayHasKey('college', $a->education_history);
    }

    public function test_form_options_expose_levels_and_statuses(): void
    {
        $this->actingAs($this->registrar())->get('/applicants/create')
            ->assertInertia(fn ($p) => $p
                ->has('options.education_levels', 5)
                ->where('options.education_statuses', ['Graduate', 'Undergraduate', 'Ongoing'])
                ->has('options.layout.fields'));
    }

    public function test_education_history_field_is_registered_in_form_builder(): void
    {
        $keys = collect(\App\Support\BuiltinFields::all())->pluck('key');
        $this->assertTrue($keys->contains('education_history'));

        $field = collect(\App\Support\FormLayout::formFields())->firstWhere('key', 'education_history');
        $this->assertNotNull($field);
        $this->assertSame('education_history', $field['widget']);
    }
}
