<?php

namespace App\Jobs;

use App\Http\Controllers\Api\CaisseController;
use App\Models\CaisseSyntheseJournaliere;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateCaisseSyntheseDayJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $uniqueFor = 600;

    public function __construct(public string $jour)
    {
        $this->onQueue('read-models');
    }

    public function handle(CaisseController $caisse): void
    {
        try {
            $caisse->refreshCaisseSyntheseDay($this->jour);
        } catch (\Throwable $e) {
            CaisseSyntheseJournaliere::query()
                ->whereDate('jour', $this->jour)
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
        return $this->jour;
    }

    private function cacheKey(): string
    {
        return "read-model:caisse-synthese:{$this->jour}";
    }
}
