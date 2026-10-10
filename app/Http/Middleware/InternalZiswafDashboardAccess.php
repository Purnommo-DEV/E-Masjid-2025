<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InternalZiswafDashboardAccess
{
    public const SESSION_KEY = 'internal_ziswaf_dashboard_grant';

    public function handle(Request $request, Closure $next): Response
    {
        $configuredHash = strtolower(trim((string) config('financial_reporting.internal_ziswaf_dashboard.token_hash')));
        $grant = $request->session()->get(self::SESSION_KEY);
        $valid = preg_match('/^[a-f0-9]{64}$/', $configuredHash)
            && is_array($grant)
            && hash_equals(hash('sha256', $configuredHash), (string) ($grant['fingerprint'] ?? ''))
            && (int) ($grant['expires_at'] ?? 0) >= now()->timestamp;

        abort_unless($valid, 404);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
