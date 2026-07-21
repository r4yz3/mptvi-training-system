<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // MPTVI's actual TESDA-accredited offerings (from their enrollment poster).
        // School is TESDA-funded; it collects only a 1,000-peso miscellaneous fee.
        $programs = [
            ['title' => 'Shielded Metal Arc Welding (SMAW) NC II',    'qualification' => 'Metals & Engineering',       'level' => 'NC II',  'hours' => 268, 'slots' => 25],
            ['title' => 'Electrical Installation & Maintenance NC II', 'qualification' => 'Construction',               'level' => 'NC II',  'hours' => 402, 'slots' => 25],
            ['title' => 'Hydraulic Excavator (HEO) NC II',            'qualification' => 'Heavy Equipment Operation',  'level' => 'NC II',  'hours' => 156, 'slots' => 15],
            ['title' => 'Driving NC II',                              'qualification' => 'Land Transport',             'level' => 'NC II',  'hours' => 118, 'slots' => 15],
            ['title' => 'Bookkeeping NC III',                         'qualification' => 'Business & Finance',         'level' => 'NC III', 'hours' => 292, 'slots' => 25],
            ['title' => 'Visual Graphics Design NC III',              'qualification' => 'Information Technology',      'level' => 'NC III', 'hours' => 487, 'slots' => 25],
            ['title' => 'Housekeeping NC II',                         'qualification' => 'Tourism',                    'level' => 'NC II',  'hours' => 436, 'slots' => 25],
        ];

        foreach ($programs as $p) {
            Program::firstOrCreate(
                ['title' => $p['title']],
                [...$p, 'training_type' => Program::SCHOOL_BASED, 'fee' => 1000, 'active' => true],
            );
        }

        // Community-Based Training — free soft-skills offerings (no payment).
        $community = [
            ['title' => 'Basic Communication & Workplace Etiquette', 'qualification' => 'Soft Skills', 'level' => 'Non-NC', 'hours' => 24, 'slots' => 40],
            ['title' => 'Financial Literacy & Personal Budgeting',   'qualification' => 'Soft Skills', 'level' => 'Non-NC', 'hours' => 16, 'slots' => 40],
            ['title' => 'Job Readiness & Interview Skills',          'qualification' => 'Soft Skills', 'level' => 'Non-NC', 'hours' => 20, 'slots' => 40],
        ];
        foreach ($community as $p) {
            Program::firstOrCreate(
                ['title' => $p['title']],
                [...$p, 'training_type' => Program::COMMUNITY_BASED, 'fee' => 0, 'active' => true],
            );
        }

        $this->seedExtraFees();
        $this->seedSubjects();
    }

    /**
     * Starter extra-fee amounts (per program, per school year) so the cashier can
     * track uniform / assessment fees out of the box. Admins edit these at
     * Settings → Fees. Community-based (free) programs charge no extras.
     */
    private function seedExtraFees(): void
    {
        $sy = '2026';
        foreach (Program::where('training_type', Program::SCHOOL_BASED)->get() as $program) {
            foreach (['School uniform' => 500, 'Assessment fee' => 1000] as $category => $amount) {
                \App\Models\FeeItem::firstOrCreate(
                    ['program_id' => $program->id, 'school_year' => $sy, 'category' => $category],
                    ['amount' => $amount],
                );
            }
        }
    }

    /**
     * Starter Major/Minor subjects (with units) for a couple of flagship programs
     * so grading works out of the box. Admins edit these at Settings → Subjects.
     * [title, category, units]
     */
    private function seedSubjects(): void
    {
        $sets = [
            'Shielded Metal Arc Welding (SMAW) NC II' => [
                ['Welding Fundamentals & Safety', 'Major', 3],
                ['Shielded Metal Arc Welding — Plates', 'Major', 5],
                ['Shielded Metal Arc Welding — Pipes', 'Major', 5],
                ['Blueprint Reading & Interpretation', 'Major', 3],
                ['Workplace Communication', 'Minor', 2],
                ['Occupational Safety & Health', 'Minor', 2],
            ],
            'Housekeeping NC II' => [
                ['Guest Room Servicing', 'Major', 5],
                ['Public Area Maintenance', 'Major', 4],
                ['Laundry & Linen Services', 'Major', 3],
                ['Industry Knowledge & Hygiene', 'Minor', 2],
                ['Workplace Communication', 'Minor', 2],
            ],
        ];

        foreach ($sets as $title => $subjects) {
            $program = Program::where('title', $title)->first();
            if (! $program) {
                continue;
            }
            foreach (array_values($subjects) as $sort => [$subjectTitle, $category, $units]) {
                $program->subjects()->firstOrCreate(
                    ['title' => $subjectTitle],
                    ['category' => $category, 'units' => $units, 'sort' => $sort],
                );
            }
        }
    }
}
