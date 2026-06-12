<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            ['title' => 'SMAW Batch 2026-A Orientation', 'type' => 'Orientation', 'date' => '2026-06-15', 'time' => '8:00 AM', 'location' => 'MPTVI Hall'],
            ['title' => 'EIM Institutional Assessment', 'type' => 'Assessment', 'date' => '2026-08-05', 'time' => '9:00 AM', 'location' => 'Assessment Center'],
            ['title' => 'Document submission deadline', 'type' => 'Deadline', 'date' => '2026-06-30', 'location' => 'Registrar'],
            ['title' => 'Independence Day (no classes)', 'type' => 'Holiday', 'date' => '2026-06-12'],
        ];
        foreach ($events as $e) {
            Event::firstOrCreate(['title' => $e['title']], $e);
        }
    }
}
