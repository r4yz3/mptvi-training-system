<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsEducationTest extends TestCase
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

    public function test_page_is_admin_only(): void
    {
        $this->actingAs($this->as('coordinator'))->get('/settings/education')->assertForbidden();

        $this->actingAs($this->as('admin'))->get('/settings/education')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Settings/Education')
                ->has('levels', 5)
                ->where('statuses', ['Graduate', 'Undergraduate', 'Ongoing']));
    }

    public function test_admin_can_edit_levels_and_statuses(): void
    {
        $this->actingAs($this->as('admin'))->put('/settings/education', [
            'levels' => [
                ['key' => 'elementary', 'label' => 'Primary School'],   // renamed
                ['key' => 'college', 'label' => 'College / Vocational'],
                ['key' => '', 'label' => 'ALS / Alternative Learning'], // new row → slug key
            ],
            'statuses' => ['Graduate', 'Ongoing', 'Dropped '],
        ])->assertRedirect()->assertSessionHas('success');

        // Overrides are merged over config/lpf.php on boot.
        Setting::applyConfigOverrides();

        $levels = config('lpf.education_levels');
        $this->assertCount(3, $levels);
        $this->assertSame('Primary School', $levels[0]['label']);
        $this->assertSame('als_alternative_learning', $levels[2]['key']);
        $this->assertSame(['Graduate', 'Ongoing', 'Dropped'], config('lpf.education_statuses'));

        // The registration form picks up the edited grid.
        $this->actingAs($this->as('registrar'))->get('/applicants/create')
            ->assertInertia(fn (Assert $p) => $p->has('options.education_levels', 3)
                ->where('options.education_levels.0.label', 'Primary School'));
    }

    public function test_grid_requires_at_least_one_level_and_status(): void
    {
        $this->actingAs($this->as('admin'))->put('/settings/education', [
            'levels' => [], 'statuses' => [],
        ])->assertSessionHasErrors(['levels', 'statuses']);
    }
}
