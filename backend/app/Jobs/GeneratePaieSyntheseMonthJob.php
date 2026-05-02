<?php

namespace App\Jobs;

use App\Http\Controllers\Api\PaieController;
use App\Models\PaieSyntheseMensuelle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GeneratePaieSyntheseMonthJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $uniqueFor = 600;

    public function __construct(public string $mois)
    {
        $this->onQueue('read-models');
    }

    public function handle(PaieController $paie): void
    {
        try {
            $paie->refreshPaieSyntheseMonth($this->mois);
        } catch (\Throwable $e) {
            PaieSyntheseMensuelle::query()
                ->where('mois', $this->mois)
                ->update([
                    'synthese_status' => 'stale',
                    'error_message' => $e->getMessage(),
                    'refreshed_by' => 'queue',
                ]);

            throw $e;
        } finally {
            Cache::forget($this->cacheKey());
        }
    }

    public function uniqueId(): string
    {
        return $this->mois;
    }

    private function cacheKey(): string
    {
        return "read-model:paie-synthese:{$this->mois}";
    }
}
