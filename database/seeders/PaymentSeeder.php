<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $cashier = User::role('cashier')->first();
        // Learners at Paid+ stages are fully paid; a few Qualified made partial payments.
        $fullyPaid = ['Paid', 'In training', 'For assessment', 'Certified'];

        foreach (Applicant::with('program')->get() as $i => $a) {
            $fee = $a->fee();
            if ($fee <= 0) {
                continue;
            }
            if (in_array($a->status, $fullyPaid, true)) {
                Payment::firstOrCreate(
                    ['applicant_id' => $a->id, 'or_number' => 'OR-' . (20400 + $a->id)],
                    [
                        'amount' => $fee, 'category' => config('lpf.training_fee_category'), 'type' => 'Full', 'method' => 'Cash',
                        'paid_at' => now()->subDays(40 - ($i % 30))->toDateString(),
                        'cashier_id' => $cashier?->id,
                    ],
                );
            } elseif ($a->status === 'Qualified' && $i % 3 === 0) {
                Payment::firstOrCreate(
                    ['applicant_id' => $a->id, 'or_number' => 'OR-' . (20600 + $a->id)],
                    [
                        'amount' => intdiv($fee, 2), 'category' => config('lpf.training_fee_category'), 'type' => 'Down', 'method' => 'GCash',
                        'paid_at' => now()->subDays(10)->toDateString(),
                        'cashier_id' => $cashier?->id,
                    ],
                );
            }
        }
    }
}
