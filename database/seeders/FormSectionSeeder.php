<?php

namespace Database\Seeders;

use App\Models\FormSection;
use Illuminate\Database\Seeder;

class FormSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('form.sections') as $i => $s) {
            FormSection::firstOrCreate(
                ['key' => $s['key']],
                ['label' => $s['label'], 'enabled' => true, 'sort_order' => $i],
            );
        }
    }
}
