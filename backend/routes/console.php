<?php

use App\Services\QdrantService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('qdrant:cleanup {collection? : Optional collection name override} {--force : Skip the confirmation prompt} {--points-only : Remove only the points and keep the collection}', function (?string $collection = null) {
    $collectionName = $collection !== null && $collection !== '' ? $collection : config('services.qdrant.collection');
    $pointsOnly = (bool) $this->option('points-only');
    $action = $pointsOnly ? 'svuotare' : 'pulire e ricreare';

    if (! $this->option('force') && ! $this->confirm("{$action} la collezione Qdrant [{$collectionName}]?")) {
        $this->warn('Operazione annullata.');

        return SymfonyCommand::SUCCESS;
    }

    try {
        $service = app(QdrantService::class);
        $result = $pointsOnly
            ? $service->clear($collection)
            : $service->reset($collection);
    } catch (\Throwable $e) {
        $this->error("Impossibile {$action} la collezione Qdrant [{$collectionName}]: {$e->getMessage()}");

        return SymfonyCommand::FAILURE;
    }

    if ($pointsOnly) {
        $this->info("Collezione Qdrant svuotata: {$result['collection']}");
        $this->line("Punti eliminati: {$result['deleted']}");

        return SymfonyCommand::SUCCESS;
    }

    $this->info("Collezione Qdrant pulita e ricreata: {$result['collection']}");
    $this->line("Dimensione vettore: {$result['vector_size']}");

    return SymfonyCommand::SUCCESS;
})->purpose('Pulisce e ricrea la collezione Qdrant configurata');
