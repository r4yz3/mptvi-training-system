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
                [...$p, 'fee' => 1000, 'active' => true],
            );
        }
    }
}
