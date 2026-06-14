<?php

namespace App\Console\Commands;

use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--dry : Report potential savings without writing}';

    protected $description = 'Resize & re-compress all previously uploaded images (photos, signatures, attachments, logos).';

    /** disk => [directories], with per-group max dimension. */
    private array $targets = [
        ['disk' => 'public', 'dir' => 'applicant-photos', 'max' => 800],
        ['disk' => 'local', 'dir' => 'message-attachments', 'max' => 1600],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $totalBefore = 0;
        $totalAfter = 0;
        $count = 0;

        foreach ($this->targets as $t) {
            $disk = Storage::disk($t['disk']);
            if (! $disk->exists($t['dir'])) {
                continue;
            }
            foreach ($disk->allFiles($t['dir']) as $path) {
                $bytes = $disk->get($path);
                $before = strlen($bytes);
                $opt = ImageOptimizer::bytes($bytes, $t['max']);
                $after = strlen($opt);

                if ($after < $before) {
                    $totalBefore += $before;
                    $totalAfter += $after;
                    $count++;
                    if (! $dry) {
                        $disk->put($path, $opt);
                    }
                    $this->line(sprintf('  %s  %s → %s', $path, $this->fmt($before), $this->fmt($after)));
                }
            }
        }

        // Public logos
        foreach (['mptvi-logo.png', 'magsaysay-logo.png'] as $logo) {
            $abs = public_path($logo);
            if (is_file($abs)) {
                $bytes = file_get_contents($abs);
                $before = strlen($bytes);
                $opt = ImageOptimizer::bytes($bytes, 512);
                if (strlen($opt) < $before) {
                    $totalBefore += $before;
                    $totalAfter += strlen($opt);
                    $count++;
                    if (! $dry) {
                        file_put_contents($abs, $opt);
                    }
                    $this->line(sprintf('  %s  %s → %s', $logo, $this->fmt($before), $this->fmt(strlen($opt))));
                }
            }
        }

        $saved = $totalBefore - $totalAfter;
        $this->newLine();
        $this->info(sprintf(
            '%s %d image(s): %s → %s (saved %s%s).',
            $dry ? 'Would optimize' : 'Optimized',
            $count,
            $this->fmt($totalBefore),
            $this->fmt($totalAfter),
            $this->fmt($saved),
            $totalBefore > 0 ? ', ' . round($saved / $totalBefore * 100) . '%' : '',
        ));

        return self::SUCCESS;
    }

    private function fmt(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
