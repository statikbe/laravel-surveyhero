<?php

namespace Statikbe\Surveyhero\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Statikbe\Surveyhero\SurveyheroConfig;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Surveyhero does not sign its webhook calls, so the only way to authenticate them is a shared secret in the webhook
 * URL. This middleware compares that token against `surveyhero.webhook_secret` and rejects the request otherwise.
 *
 * The token is read from the `token` route parameter, falling back to the `token` query parameter. It fails closed:
 * when no secret is configured, every request is rejected.
 */
class VerifySurveyheroWebhookToken
{
    public const TOKEN_PARAMETER = 'token';

    public function __construct(private readonly SurveyheroConfig $config) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $secret = $this->config->getWebhookSecret();

        if ($secret === null) {
            Log::warning('Surveyhero webhook rejected: no webhook secret is configured (SURVEYHERO_WEBHOOK_SECRET).');

            return $this->forbidden();
        }

        $token = $request->route(self::TOKEN_PARAMETER) ?? $request->query(self::TOKEN_PARAMETER);

        if (! is_string($token) || ! hash_equals($secret, $token)) {
            Log::warning('Surveyhero webhook rejected: missing or invalid token.');

            return $this->forbidden();
        }

        // Keep the secret out of the controller action's arguments.
        $request->route()?->forgetParameter(self::TOKEN_PARAMETER);

        return $next($request);
    }

    private function forbidden(): SymfonyResponse
    {
        return response()->json(['error' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
    }
}
