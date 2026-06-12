<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\CustomField;
use App\Models\FormSection;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\FormSectionSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(FormSectionSeeder::class);
    }

    private function as(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    public function test_form_builder_is_admin_only(): void
    {
        $this->actingAs($this->as('admin'))->get('/settings/form-builder')->assertOk();
        $this->actingAs($this->as('manager'))->get('/settings/form-builder')->assertForbidden();
        $this->actingAs($this->as('registrar'))->get('/settings/form-builder')->assertForbidden();
    }

    public function test_admin_can_create_custom_field_with_slug_key(): void
    {
        $this->actingAs($this->as('admin'))->post('/settings/form-builder/fields', [
            'label' => 'Preferred Schedule', 'type' => 'select', 'section' => 'sec-course',
            'options_text' => "Morning\nAfternoon\nWeekend", 'required' => true, 'enabled' => true,
        ])->assertRedirect();

        $f = CustomField::first();
        $this->assertSame('preferred_schedule', $f->key);
        $this->assertSame(['Morning', 'Afternoon', 'Weekend'], $f->options);
        $this->assertTrue($f->required);
    }

    public function test_section_can_be_toggled(): void
    {
        $section = FormSection::where('key', 'sec-family')->first();
        $this->assertTrue($section->enabled);

        $this->actingAs($this->as('admin'))->put("/settings/form-builder/sections/{$section->id}/toggle")->assertRedirect();
        $this->assertFalse($section->fresh()->enabled);
    }

    public function test_custom_field_value_is_saved_on_registration(): void
    {
        CustomField::create(['label' => 'T-shirt size', 'key' => 'tshirt_size', 'type' => 'select',
            'options' => ['S', 'M', 'L'], 'section' => 'sec-additional', 'required' => false, 'enabled' => true]);

        $this->actingAs($this->as('registrar'))->post('/applicants', [
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob',
            'contact' => '0917', 'sex' => 'Male', 'program_id' => Program::first()->id,
            'custom' => ['tshirt_size' => 'M'],
        ])->assertRedirect();

        $a = Applicant::where('last_name', 'Cruz')->first();
        $this->assertSame('M', $a->custom_data['tshirt_size']);
    }

    public function test_required_custom_field_is_enforced(): void
    {
        CustomField::create(['label' => 'Emergency code', 'key' => 'emergency_code', 'type' => 'text',
            'section' => 'sec-additional', 'required' => true, 'enabled' => true]);

        $this->actingAs($this->as('registrar'))->post('/applicants', [
            'last_name' => 'X', 'first_name' => 'Y', 'barangay' => 'Z',
            'contact' => '09', 'sex' => 'Male', 'program_id' => Program::first()->id,
            // custom.emergency_code omitted
        ])->assertSessionHasErrors('custom.emergency_code');
    }

    public function test_disabled_custom_field_is_not_required(): void
    {
        CustomField::create(['label' => 'Old field', 'key' => 'old_field', 'type' => 'text',
            'section' => 'sec-additional', 'required' => true, 'enabled' => false]);

        $this->actingAs($this->as('registrar'))->post('/applicants', [
            'last_name' => 'Ok', 'first_name' => 'Fine', 'barangay' => 'Z',
            'contact' => '09', 'sex' => 'Male', 'program_id' => Program::first()->id,
        ])->assertRedirect();
    }

    private function applicantWith(string $key, $value): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id, 'status' => 'Registered', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09',
            'custom_data' => [$key => $value],
        ]);
    }

    public function test_show_in_list_field_appears_in_index_rows(): void
    {
        CustomField::create(['label' => 'Shirt', 'key' => 'shirt', 'type' => 'select',
            'options' => ['S', 'M'], 'section' => 'sec-additional', 'enabled' => true, 'show_in_list' => true]);
        $this->applicantWith('shirt', 'M');

        $this->actingAs($this->as('admin'))->get('/applicants')
            ->assertInertia(fn ($p) => $p->has('options.listCustom', 1)
                ->where('applicants.data.0.custom.shirt', 'M'));
    }

    public function test_filterable_custom_field_filters_the_list(): void
    {
        CustomField::create(['label' => 'Sched', 'key' => 'sched', 'type' => 'select',
            'options' => ['AM', 'PM'], 'section' => 'sec-additional', 'enabled' => true, 'filterable' => true]);
        $this->applicantWith('sched', 'AM');
        $this->applicantWith('sched', 'PM');

        // Only the AM applicant should remain after filtering.
        $this->actingAs($this->as('admin'))->get('/applicants?cf_sched=AM')
            ->assertInertia(fn ($p) => $p->has('applicants.data', 1)->has('options.filterCustom', 1));
    }

    public function test_csv_export_includes_custom_fields(): void
    {
        CustomField::create(['label' => 'Blood note', 'key' => 'blood_note', 'type' => 'text',
            'section' => 'sec-additional', 'enabled' => true]);
        $this->applicantWith('blood_note', 'O-positive');

        $res = $this->actingAs($this->as('admin'))->get('/reports/applicants.csv');
        $res->assertOk();
        $content = $res->streamedContent();
        $this->assertStringContainsString('Blood note', $content);
        $this->assertStringContainsString('O-positive', $content);
    }

    public function test_admin_can_relabel_and_hide_builtin_field(): void
    {
        $this->actingAs($this->as('admin'))
            ->put('/settings/form-builder/builtin/religion', ['label' => 'Faith', 'enabled' => false, 'required' => false])
            ->assertRedirect();

        $map = \App\Support\BuiltinFields::map();
        $this->assertSame('Faith', $map['religion']['label']);
        $this->assertFalse($map['religion']['enabled']);
    }

    public function test_making_builtin_field_required_is_enforced(): void
    {
        $this->actingAs($this->as('admin'))
            ->put('/settings/form-builder/builtin/religion', ['enabled' => true, 'required' => true]);

        // religion now required → registration without it fails
        $this->actingAs($this->as('registrar'))->post('/applicants', [
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob',
            'contact' => '0917', 'sex' => 'Male', 'program_id' => Program::first()->id,
        ])->assertSessionHasErrors('religion');

        // with religion → succeeds
        $this->actingAs($this->as('registrar'))->post('/applicants', [
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob',
            'contact' => '0917', 'sex' => 'Male', 'program_id' => Program::first()->id,
            'religion' => 'Roman Catholic',
        ])->assertRedirect();
    }

    public function test_locked_field_cannot_be_disabled_or_made_optional(): void
    {
        // Try to hide & un-require a system-critical field
        $this->actingAs($this->as('admin'))
            ->put('/settings/form-builder/builtin/last_name', ['label' => 'Surname', 'enabled' => false, 'required' => false])
            ->assertRedirect();

        $map = \App\Support\BuiltinFields::map();
        $this->assertSame('Surname', $map['last_name']['label']); // relabel allowed
        $this->assertTrue($map['last_name']['enabled']);           // but stays shown
        $this->assertTrue($map['last_name']['required']);          // and required
    }

    public function test_dashboard_breakdown_counts_select_options(): void
    {
        CustomField::create(['label' => 'Sched', 'key' => 'sched', 'type' => 'select',
            'options' => ['AM', 'PM'], 'section' => 'sec-additional', 'enabled' => true, 'show_on_dashboard' => true]);
        $this->applicantWith('sched', 'AM');
        $this->applicantWith('sched', 'AM');
        $this->applicantWith('sched', 'PM');

        $this->actingAs($this->as('admin'))->get('/dashboard')
            ->assertInertia(fn ($p) => $p->has('customBreakdowns', 1)
                ->where('customBreakdowns.0.label', 'Sched')
                ->where('customBreakdowns.0.items.0.value', 2)
                ->where('customBreakdowns.0.items.1.value', 1));
    }
}
