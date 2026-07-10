<?php

namespace Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $qdrantBaseUrl = rtrim((string) config('services.qdrant.url', 'http://127.0.0.1:6333'), '/');

        Http::fake([
            "{$qdrantBaseUrl}/*" => function (Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response(['result' => []], 404);
                }

                return Http::response(['result' => 'ok', 'status' => 'ok'], 200);
            },
        ]);
    }
}
