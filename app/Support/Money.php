<?php

namespace App\Support;

class Money
{
    /** Whole pesos in words, e.g. 1250 → "One Thousand Two Hundred Fifty Pesos". */
    public static function inWords(int $pesos): string
    {
        if ($pesos === 0) {
            return 'Zero Pesos';
        }

        $words = self::convert($pesos);

        return trim($words) . ' Pesos';
    }

    private static function convert(int $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($n < 20) {
            return $ones[$n];
        }
        if ($n < 100) {
            return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
        }
        if ($n < 1000) {
            return trim($ones[intdiv($n, 100)] . ' Hundred ' . self::convert($n % 100));
        }

        foreach ([1_000_000_000 => 'Billion', 1_000_000 => 'Million', 1_000 => 'Thousand'] as $unit => $label) {
            if ($n >= $unit) {
                return trim(self::convert(intdiv($n, $unit)) . ' ' . $label . ' ' . self::convert($n % $unit));
            }
        }

        return '';
    }
}
