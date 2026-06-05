<?php

namespace Tests\Feature;

use App\Services\QdrantService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QdrantCleanupCommandTest extends TestCase
{
    #[Test]
    public function it_invokes_the_qdrant_reset_service_when_forced(): void
    {
        $this->mock(QdrantService::class, function ($mock): void {
            $mock->shouldReceive('reset')
                ->once()
                ->with(null)
                ->andReturn([
                    'collection' => config('services.qdrant.collection'),
                    'vector_size' => 1024,
                    'created' => ['result' => 'ok'],
                ]);
        });

        $this->artisan('qdrant:cleanup', ['--force' => true])
            ->expectsOutput('Collezione Qdrant pulita e ricreata: '.config('services.qdrant.collection'))
            ->expectsOutput('Dimensione vettore: 1024')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_invokes_the_qdrant_clear_service_when_points_only_is_requested(): void
    {
        $this->mock(QdrantService::class, function ($mock): void {
            $mock->shouldReceive('clear')
                ->once()
                ->with(null)
                ->andReturn([
                    'collection' => config('services.qdrant.collection'),
                    'deleted' => 12,
                    'chunks' => 1,
                ]);

            $mock->shouldNotReceive('reset');
        });

        $this->artisan('qdrant:cleanup', ['--force' => true, '--points-only' => true])
            ->expectsOutput('Collezione Qdrant svuotata: '.config('services.qdrant.collection'))
            ->expectsOutput('Punti eliminati: 12')
            ->assertExitCode(0);
    }
}
