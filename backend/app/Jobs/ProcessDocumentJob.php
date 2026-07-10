<?php

namespace App\Jobs;

use App\Services\Documents\DocumentProcessingService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $documentId,
    ) {
    }

    public function handle(DocumentProcessingService $documentProcessingService): void
    {
        $documentProcessingService->process($this->tenantId, $this->documentId);
    }
}
