<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * School year is now a single calendar year (trainees graduate every 6
     * months), e.g. "2026" instead of "2026–2027". Normalise any existing
     * range-format values to their starting year across every place it is stored.
     */
    public function up(): void
    {
        // applicants + batches + fee_items all carry a school_year string column.
        foreach (['applicants', 'batches', 'fee_items'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull('school_year')->get(['id', 'school_year']) as $row) {
                $single = $this->firstYear($row->school_year);
                if ($single !== null && $single !== $row->school_year) {
                    DB::table($table)->where('id', $row->id)->update(['school_year' => $single]);
                }
            }
        }

        // The admin-set "current school year" default.
        $sy = DB::table('settings')->where('key', 'acad_school_year')->value('value');
        if ($sy && ($single = $this->firstYear($sy)) !== null && $single !== $sy) {
            DB::table('settings')->where('key', 'acad_school_year')->update(['value' => $single]);
        }
    }

    public function down(): void
    {
        // Not reversible — the second year of the range is not stored.
    }

    /** "2026–2027" / "2026-2027" → "2026"; a bare "2026" is returned unchanged. */
    private function firstYear(string $value): ?string
    {
        if (preg_match('/^\s*(\d{4})\s*[–-]\s*\d{4}\s*$/u', $value, $m)) {
            return $m[1];
        }

        return null;
    }
};
