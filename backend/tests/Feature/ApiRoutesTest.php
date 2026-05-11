<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    #[Test]
    public function api_v1_routes_are_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->all();

        $this->assertContains('api/v1/auth/login', $routes);
        $this->assertContains('api/v1/documents', $routes);
        $this->assertContains('api/v1/documents/{documentId}', $routes);
        $this->assertContains('api/v1/documents/{documentId}/retry', $routes);
        $this->assertContains('api/v1/search/queries', $routes);
        $this->assertContains('api/v1/chat/conversations', $routes);
        $this->assertContains('api/v1/chat/conversations/{conversationId}', $routes);
        $this->assertContains('api/v1/chat/conversations/{conversationId}/archive', $routes);
        $this->assertContains('api/v1/audit-events', $routes);
    }
}
