<?php

namespace App\Jobs;

use App\Models\AlatImport;
use App\Services\AlatImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ImportAlatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public function __construct(public int $importId) {}

    public function handle(AlatImportService $alatImportService): void
    {
        $import = AlatImport::query()->with('user.role')->find($this->importId);
        if (! $import) {
            return;
        }

        $alatImportService->processImport($import);
    }

    public function failed(Throwable $exception): void
    {
        $import = AlatImport::query()->find($this->importId);
        if (! $import) {
            return;
        }

        app(AlatImportService::class)->markAsFailed($import, $exception->getMessage());
    }
}
