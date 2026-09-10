<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CorrelationId — assigns a unique ID to each request for tracing.
 *
 * Blueprint §11.3: Correlation ID for all requests.
 * Blueprint §12: Event must carry correlation.
 */
class CorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-Id', Str::uuid()->toString());

        // Bind for use in logging, audit, events
        app()->instance('akru.correlation_id', $correlationId);

        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
