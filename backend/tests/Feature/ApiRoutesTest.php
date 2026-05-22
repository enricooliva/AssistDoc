<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    #[Test]
    public function api_v1_routes_are_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertContains('api/v1/auth/login', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/auth/options', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/auth/company-account/start', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/auth/password/login', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/auth/mfa/verify', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/auth/password-reset', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/auth/password-reset/complete', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/documents', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/documents/{documentId}', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/documents/{documentId}/retry', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/search/queries', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/chat/conversations', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/chat/conversations/{conversationId}', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/users', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/users/{userId}', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/users/{userId}/status', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/users/{userId}/unlock', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertContains('api/v1/audit-events', $routes->map(fn ($route) => $route->uri())->all());
        $this->assertTrue(
            $routes->contains(fn ($route) => $route->uri() === 'api/v1/documents/{documentId}' && in_array('DELETE', $route->methods(), true))
        );
        $this->assertTrue(
            $routes->contains(fn ($route) => $route->uri() === 'api/v1/chat/conversations/{conversationId}' && in_array('DELETE', $route->methods(), true))
        );
        $this->assertTrue(
            $routes->contains(fn ($route) => $route->uri() === 'api/v1/users/{userId}/unlock' && in_array('POST', $route->methods(), true))
        );
        $this->assertTrue(
            $routes->contains(fn ($route) => $route->uri() === 'api/v1/users/{userId}' && in_array('DELETE', $route->methods(), true))
        );
    }
}
