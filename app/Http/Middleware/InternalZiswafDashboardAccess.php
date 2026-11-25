<?php

namespace App\Http\Middleware;

use App\Models\FinancialV2\Distribution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class InternalZiswafDashboardAccess
{
    public const SESSION_KEY = 'internal_ziswaf_dashboard_grant';

    public const DESTINATION_SESSION_KEY = 'internal_ziswaf_distribution_destination';

    /** A destination is navigation state, never an authorization grant. */
    public static function intendedDistribution(Request $request, bool $consume = false): ?string
    {
        $destination = $consume
            ? $request->session()->pull(self::DESTINATION_SESSION_KEY)
            : $request->session()->get(self::DESTINATION_SESSION_KEY);
        $id = is_array($destination) ? ($destination['id'] ?? null) : null;
        if (! is_string($id) || ! Str::isUuid($id) || (int) ($destination['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget(self::DESTINATION_SESSION_KEY);

            return null;
        }

        return $id;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $configuredHash = strtolower(trim((string) config('financial_reporting.internal_ziswaf_dashboard.token_hash')));
        $grant = $request->session()->get(self::SESSION_KEY);
        $valid = preg_match('/^[a-f0-9]{64}$/', $configuredHash)
            && is_array($grant)
            && hash_equals(hash('sha256', $configuredHash), (string) ($grant['fingerprint'] ?? ''))
            && (int) ($grant['expires_at'] ?? 0) >= now()->timestamp;

        if (! $valid) {
            $distribution = $request->route('distribution');
            $id = $distribution instanceof Distribution ? $distribution->getKey() : $distribution;
            abort_unless($request->isMethod('GET') && $request->routeIs('internal.ziswaf.distributions.show')
                && is_string($id) && Str::isUuid($id), 404);
            $request->session()->put(self::DESTINATION_SESSION_KEY, [
                'id' => $id,
                'expires_at' => now()->addMinutes(15)->timestamp,
            ]);
            $response = redirect()->route('internal.ziswaf.preview');
        } else {
            $response = $next($request);
        }
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
