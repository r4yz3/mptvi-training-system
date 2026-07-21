<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ApplicantSeeder extends Seeder
{
    public function run(): void
    {
        $programs = Program::orderBy('id')->pluck('id')->all();
        if (empty($programs)) {
            return;
        }

        $barangays = ['Poblacion', 'Bacungan', 'Dalumay', 'Tacul', 'San Isidro', 'Malawanit',
            'Kanapulo', 'Glamang', 'Tigbao', 'New Ilocos', 'Bagumbayan', 'Mabini', 'Upper Bala'];
        $educ = ['Elementary Graduate', 'High School Graduate', 'Senior High Graduate',
            'College Level', 'College Graduate', 'TVET Graduate'];

        // [first, last, sex, age, civil, status, voter]
        $people = [
            ['Maria', 'Santos', 'Female', 24, 'Single', 'Certified', true],
            ['Pedro', 'Reyes', 'Male', 29, 'Married', 'In training', true],
            ['Ana', 'Lim', 'Female', 21, 'Single', 'Paid', true],
            ['Jose', 'Bautista', 'Male', 33, 'Married', 'Enrolled', false],
            ['Liza', 'Gomez', 'Female', 19, 'Single', 'Registered', true],
            ['Mark', 'Villanueva', 'Male', 27, 'Single', 'For assessment', true],
            ['Grace', 'Ocampo', 'Female', 22, 'Single', 'Registered', true],
            ['Ramon', 'Cruz', 'Male', 41, 'Married', 'Disqualified', false],
            ['Jenny', 'Rosales', 'Female', 26, 'Married', 'Enrolled', true],
            ['Allan', 'Dizon', 'Male', 35, 'Married', 'Paid', true],
            ['Cristina', 'Flores', 'Female', 20, 'Single', 'Enrolled', true],
            ['Rey', 'Mendoza', 'Male', 23, 'Single', 'In training', true],
            ['Divine', 'Pascua', 'Female', 28, 'Married', 'Registered', true],
            ['Joel', 'Aquino', 'Male', 31, 'Married', 'In training', false],
            ['Karen', 'Bautista', 'Female', 18, 'Single', 'Registered', true],
            ['Noel', 'Garcia', 'Male', 38, 'Married', 'Paid', true],
            ['Sheila', 'Marquez', 'Female', 25, 'Single', 'For assessment', true],
            ['Eduardo', 'Lim', 'Male', 45, 'Married', 'Certified', true],
            ['Patricia', 'Yap', 'Female', 23, 'Single', 'Registered', true],
            ['Carlo', 'Ramos', 'Male', 30, 'Married', 'Enrolled', true],
            ['Rowena', 'Castro', 'Female', 27, 'Single', 'Paid', true],
            ['Dennis', 'Torres', 'Male', 36, 'Married', 'In training', true],
            ['Michelle', 'Aguilar', 'Female', 24, 'Single', 'Registered', true],
            ['Ferdinand', 'Navarro', 'Male', 33, 'Married', 'Registered', false],
        ];

        $advanced = ['Paid', 'In training', 'For assessment', 'Certified'];
        $i = 0;

        foreach ($people as $idx => [$first, $last, $sex, $age, $civil, $status, $voter]) {
            $i++;
            $programId = $programs[$idx % count($programs)];
            $isAdvanced = in_array($status, $advanced, true);
            $birthYear = 2026 - $age;
            // Advanced-stage learners are assigned to a batch of their program (for Training/Assessment).
            $batchId = $isAdvanced ? Batch::where('program_id', $programId)->value('id') : null;

            Applicant::firstOrCreate(
                ['first_name' => $first, 'last_name' => $last],
                [
                    'program_id' => $programId,
                    'batch_id' => $batchId,
                    'status' => $status,
                    'active' => true,
                    'registered_at' => now()->subDays(60 - $i)->toDateString(),
                    'middle_name' => 'Santos',
                    'barangay' => $barangays[$idx % count($barangays)],
                    'city' => 'Magsaysay',
                    'province' => 'Davao del Sur',
                    'region' => 'Region XI (Davao Region)',
                    'contact' => sprintf('0917-555-%04d', 100 + $i),
                    'email' => strtolower("{$first}.{$last}@example.com"),
                    'nationality' => 'Filipino',
                    'voter' => $voter,
                    'sex' => $sex,
                    'civil_status' => $civil,
                    'birthdate' => sprintf('%d-06-15', $birthYear),
                    'age' => $age,
                    'education' => $educ[$idx % count($educ)],
                    'class_session' => $idx % 2 === 0 ? 'Morning' : 'Afternoon',
                    'school_year' => '2026',
                    'classifications' => $voter ? ['4Ps Beneficiary'] : [],
                    'privacy_consent' => true,
                ],
            );
        }
    }
}
