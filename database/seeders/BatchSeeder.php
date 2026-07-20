<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Program;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $programs = Program::orderBy('id')->take(3)->get();
        $sessions = ['Morning', 'Afternoon', 'Morning'];
        $starts = ['2026-06-15', '2026-06-22', '2026-07-01'];
        $statuses = ['Open', 'Open', 'Planned'];

        foreach ($programs as $i => $program) {
            Batch::firstOrCreate(
                ['program_id' => $program->id, 'code' => '2026-A'],
                [
                    'class_session' => $sessions[$i],
                    'class_days' => 'Mon–Fri',
                    'school_year' => '2026',
                    'capacity' => $program->slots,
                    'trainer' => 'TESDA Trainer',
                    'venue' => 'MPTVI Campus',
                    'start_date' => $starts[$i],
                    'end_date' => Batch::computeEndDate($starts[$i], $program->hours, $sessions[$i], 'Mon–Fri'),
                    'status' => $statuses[$i],
                ],
            );
        }
    }
}
